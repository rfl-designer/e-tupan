<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Services;

use App\Domain\Checkout\Contracts\PaymentGatewayInterface;
use App\Domain\Checkout\DTOs\{CardData, PaymentResult, RefundResult};
use App\Domain\Checkout\Enums\{PaymentMethod, PaymentStatus};
use App\Domain\Checkout\Models\{Order, Payment};
use Illuminate\Support\Facades\{DB, Log};

class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
    ) {
    }

    /**
     * Process a credit card payment.
     */
    public function processCard(Order $order, CardData $cardData): Payment
    {
        return DB::transaction(function () use ($order, $cardData) {
            // Create payment record
            $payment = $this->createPayment(
                order: $order,
                method: PaymentMethod::CreditCard,
                amount: $this->calculateInstallmentAmount($order->total, $cardData->installments),
            );

            // Store card info (masked)
            $payment->card_brand         = $cardData->cardBrand;
            $payment->card_last_four     = $cardData->lastFourDigits;
            $payment->installments       = $cardData->installments;
            $payment->installment_amount = $this->calculateInstallmentValue($order->total, $cardData->installments);

            try {
                $result = $this->gateway->processCard($order, $cardData);

                $this->updatePaymentFromResult($payment, $result);

                if ($result->isSuccessful() && $result->status === PaymentStatus::Approved) {
                    $order->markAsPaid();
                }
            } catch (\Exception $e) {
                Log::error('Payment processing failed', [
                    'order_id' => $order->id,
                    'error'    => $e->getMessage(),
                ]);

                $payment->status           = PaymentStatus::Failed;
                $payment->gateway_response = ['error' => $e->getMessage()];
            }

            $payment->save();

            return $payment;
        });
    }

    /**
     * Generate a Pix payment.
     */
    public function generatePix(Order $order): Payment
    {
        return DB::transaction(function () use ($order) {
            $payment = $this->createPayment(
                order: $order,
                method: PaymentMethod::Pix,
                amount: $order->total,
            );

            try {
                $pixData = $this->gateway->generatePix($order);

                $payment->gateway_transaction_id = $pixData->transactionId;
                $payment->pix_qr_code            = $pixData->qrCodeBase64;
                $payment->pix_code               = $pixData->copyPasteCode;
                $payment->expires_at             = $pixData->expiresAt;
                $payment->status                 = PaymentStatus::Pending;
                $payment->gateway_response       = $pixData->metadata;
            } catch (\Exception $e) {
                Log::error('Pix generation failed', [
                    'order_id' => $order->id,
                    'error'    => $e->getMessage(),
                ]);

                $payment->status           = PaymentStatus::Failed;
                $payment->gateway_response = ['error' => $e->getMessage()];
            }

            $payment->save();

            return $payment;
        });
    }

    /**
     * Generate a bank slip payment.
     */
    public function generateBankSlip(Order $order): Payment
    {
        return DB::transaction(function () use ($order) {
            $payment = $this->createPayment(
                order: $order,
                method: PaymentMethod::BankSlip,
                amount: $order->total,
            );

            try {
                $bankSlipData = $this->gateway->generateBankSlip($order);

                $payment->gateway_transaction_id = $bankSlipData->transactionId;
                $payment->bank_slip_url          = $bankSlipData->pdfUrl;
                $payment->bank_slip_barcode      = $bankSlipData->digitableLine;
                $payment->expires_at             = $bankSlipData->dueDate->endOfDay();
                $payment->status                 = PaymentStatus::Pending;
                $payment->gateway_response       = $bankSlipData->metadata;
            } catch (\Exception $e) {
                Log::error('Bank slip generation failed', [
                    'order_id' => $order->id,
                    'error'    => $e->getMessage(),
                ]);

                $payment->status           = PaymentStatus::Failed;
                $payment->gateway_response = ['error' => $e->getMessage()];
            }

            $payment->save();

            return $payment;
        });
    }

    /**
     * Check payment status from gateway.
     */
    public function checkStatus(Payment $payment): PaymentStatus
    {
        $status = $this->gateway->checkPaymentStatus($payment);

        if ($status !== $payment->status) {
            $payment->status = $status;

            if ($status === PaymentStatus::Approved) {
                $payment->paid_at = now();
                $payment->order->markAsPaid();
            }

            $payment->save();
        }

        return $status;
    }

    /**
     * Process a refund.
     */
    public function refund(Payment $payment, ?int $amount = null): RefundResult
    {
        if (!$payment->canBeRefunded()) {
            return RefundResult::failed(
                errorCode: 'not_refundable',
                errorMessage: 'Este pagamento nao pode ser reembolsado.',
            );
        }

        $result = $this->gateway->refund($payment, $amount);

        if ($result->isSuccessful()) {
            $payment->refund($amount);
        }

        return $result;
    }

    /**
     * Create a payment record.
     */
    private function createPayment(Order $order, PaymentMethod $method, int $amount): Payment
    {
        return new Payment([
            'order_id' => $order->id,
            'method'   => $method,
            'gateway'  => $this->gateway->getName(),
            'status'   => PaymentStatus::Processing,
            'amount'   => $amount,
        ]);
    }

    /**
     * Update payment from gateway result.
     */
    private function updatePaymentFromResult(Payment $payment, PaymentResult $result): void
    {
        $payment->status                 = $result->status;
        $payment->gateway_transaction_id = $result->transactionId;
        $payment->gateway_response       = $result->metadata;

        if ($result->status === PaymentStatus::Approved) {
            $payment->paid_at = now();
        }
    }

    /**
     * Calculate total amount with installment interest.
     */
    private function calculateInstallmentAmount(int $total, int $installments): int
    {
        $interestFree = config('payment.installments.interest_free', 3);

        if ($installments <= $interestFree) {
            return $total;
        }

        $rate   = config('payment.installments.interest_rate', 1.99) / 100;
        $months = $installments - $interestFree;

        // Simple interest calculation
        return (int) ceil($total * (1 + ($rate * $months)));
    }

    /**
     * Calculate individual installment value.
     */
    private function calculateInstallmentValue(int $total, int $installments): int
    {
        $totalWithInterest = $this->calculateInstallmentAmount($total, $installments);

        return (int) ceil($totalWithInterest / $installments);
    }

    /**
     * Get available installment options for an amount.
     *
     * @return array<int, array{installments: int, value: int, total: int, interest_free: bool}>
     */
    public function getInstallmentOptions(int $amount): array
    {
        $maxInstallments = config('payment.installments.max_installments', 12);
        $minValue        = config('payment.installments.min_value', 500);
        $interestFree    = config('payment.installments.interest_free', 3);

        $options = [];

        for ($i = 1; $i <= $maxInstallments; $i++) {
            $total = $this->calculateInstallmentAmount($amount, $i);
            $value = $this->calculateInstallmentValue($amount, $i);

            if ($value < $minValue) {
                break;
            }

            $options[$i] = [
                'installments'  => $i,
                'value'         => $value,
                'total'         => $total,
                'interest_free' => $i <= $interestFree,
            ];
        }

        return $options;
    }
}
