<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Gateways;

use App\Domain\Checkout\Contracts\PaymentGatewayInterface;
use App\Domain\Checkout\DTOs\{BankSlipData, CardData, PaymentResult, PixData, RefundResult};
use App\Domain\Checkout\Enums\PaymentStatus;
use App\Domain\Checkout\Models\{Order, Payment};
use Illuminate\Support\Str;

class MockPaymentGateway implements PaymentGatewayInterface
{
    /**
     * Test card numbers for different scenarios.
     */
    private const TEST_CARDS = [
        '4111111111111111' => 'approved',
        '4000000000000002' => 'declined',
        '4000000000000119' => 'processing',
        '4000000000000069' => 'insufficient_funds',
    ];

    public function processCard(Order $order, CardData $cardData): PaymentResult
    {
        // Simulate API delay
        usleep(500000); // 0.5 seconds

        $transactionId = 'mock_' . Str::uuid()->toString();

        // Check for test card scenarios based on token prefix
        if (str_starts_with($cardData->token, 'test_declined')) {
            return PaymentResult::declined(
                errorCode: 'card_declined',
                errorMessage: 'O cartao foi recusado. Por favor, use outro cartao.',
                metadata: ['gateway' => 'mock'],
            );
        }

        if (str_starts_with($cardData->token, 'test_insufficient')) {
            return PaymentResult::declined(
                errorCode: 'insufficient_funds',
                errorMessage: 'Saldo insuficiente no cartao.',
                metadata: ['gateway' => 'mock'],
            );
        }

        if (str_starts_with($cardData->token, 'test_processing')) {
            return PaymentResult::pending(
                transactionId: $transactionId,
                metadata: [
                    'gateway'      => 'mock',
                    'card_brand'   => $cardData->cardBrand,
                    'last_four'    => $cardData->lastFourDigits,
                    'installments' => $cardData->installments,
                ],
            );
        }

        // Default: approved
        return PaymentResult::success(
            transactionId: $transactionId,
            status: PaymentStatus::Approved,
            metadata: [
                'gateway'      => 'mock',
                'card_brand'   => $cardData->cardBrand,
                'last_four'    => $cardData->lastFourDigits,
                'installments' => $cardData->installments,
            ],
        );
    }

    public function generatePix(Order $order): PixData
    {
        // Simulate API delay
        usleep(300000); // 0.3 seconds

        $transactionId = 'mock_pix_' . Str::uuid()->toString();

        // Generate a mock QR code (in production this would come from the gateway)
        $qrCodeContent = sprintf(
            '00020126580014BR.GOV.BCB.PIX0136%s5204000053039865802BR5925%s6009SAO PAULO62070503***6304',
            Str::uuid()->toString(),
            Str::limit($order->customerName, 25),
        );

        return new PixData(
            transactionId: $transactionId,
            qrCode: $qrCodeContent,
            qrCodeBase64: base64_encode($qrCodeContent),
            copyPasteCode: $qrCodeContent,
            amount: $order->total,
            expiresAt: now()->addMinutes(30),
            metadata: ['gateway' => 'mock'],
        );
    }

    public function generateBankSlip(Order $order): BankSlipData
    {
        // Simulate API delay
        usleep(300000); // 0.3 seconds

        $transactionId = 'mock_boleto_' . Str::uuid()->toString();

        // Generate mock barcode (47 digits for bank slips)
        $barcode = sprintf(
            '%s%s%s%s%s',
            '23793',
            str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT),
            str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT),
            str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT),
            str_pad((string) random_int(0, 999999999999999999), 18, '0', STR_PAD_LEFT),
        );

        // Format as digitable line
        $digitableLine = sprintf(
            '%s.%s %s.%s %s.%s %s %s',
            substr($barcode, 0, 5),
            substr($barcode, 5, 5),
            substr($barcode, 10, 5),
            substr($barcode, 15, 6),
            substr($barcode, 21, 5),
            substr($barcode, 26, 6),
            substr($barcode, 32, 1),
            substr($barcode, 33, 14),
        );

        return new BankSlipData(
            transactionId: $transactionId,
            barcode: $barcode,
            digitableLine: $digitableLine,
            pdfUrl: route('checkout.index') . '?mock_boleto=' . $transactionId,
            amount: $order->total,
            dueDate: now()->addDays(3),
            metadata: ['gateway' => 'mock'],
        );
    }

    public function checkPaymentStatus(Payment $payment): PaymentStatus
    {
        // For mock gateway, return the current status
        // In production, this would query the gateway API
        return $payment->status;
    }

    public function refund(Payment $payment, ?int $amount = null): RefundResult
    {
        // Simulate API delay
        usleep(300000); // 0.3 seconds

        $refundAmount = $amount ?? $payment->amount;
        $refundId     = 'mock_refund_' . Str::uuid()->toString();

        // Simulate occasional refund failures
        if (random_int(1, 10) === 1) {
            return RefundResult::failed(
                errorCode: 'refund_failed',
                errorMessage: 'Nao foi possivel processar o reembolso. Tente novamente.',
                metadata: ['gateway' => 'mock'],
            );
        }

        return RefundResult::success(
            refundId: $refundId,
            amount: $refundAmount,
            metadata: ['gateway' => 'mock'],
        );
    }

    public function getName(): string
    {
        return 'Mock Payment Gateway';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function validateWebhookSignature(string $payload, string $signature): bool
    {
        // For mock gateway, accept any signature starting with 'mock_'
        return str_starts_with($signature, 'mock_');
    }

    public function parseWebhookPayload(string $payload): array
    {
        $data = json_decode($payload, true);

        return [
            'transaction_id' => $data['transaction_id'] ?? '',
            'status'         => PaymentStatus::tryFrom($data['status'] ?? '') ?? PaymentStatus::Pending,
            'metadata'       => $data['metadata'] ?? [],
        ];
    }
}
