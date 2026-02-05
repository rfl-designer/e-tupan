<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Gateways;

use App\Domain\Checkout\Contracts\PaymentGatewayInterface;
use App\Domain\Checkout\DTOs\{BankSlipData, CardData, PaymentResult, PixData, RefundResult};
use App\Domain\Checkout\Enums\PaymentStatus;
use App\Domain\Checkout\Models\{Order, Payment};
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\{Http, Log};

class MercadoPagoGateway implements PaymentGatewayInterface
{
    private const BASE_URL = 'https://api.mercadopago.com';

    private string $accessToken;

    private string $publicKey;

    private bool $sandbox;

    public function __construct()
    {
        $config = config('payment.gateways.mercadopago');

        $this->accessToken = $config['access_token'] ?? '';
        $this->publicKey   = $config['public_key'] ?? '';
        $this->sandbox     = $config['sandbox'] ?? true;
    }

    public function processCard(Order $order, CardData $cardData): PaymentResult
    {
        try {
            $response = $this->client()->post('/v1/payments', [
                'transaction_amount'   => $order->total / 100,
                'token'                => $cardData->token,
                'description'          => "Pedido #{$order->order_number}",
                'installments'         => $cardData->installments,
                'payment_method_id'    => $this->mapCardBrand($cardData->cardBrand),
                'payer'                => $this->buildPayerData($order),
                'statement_descriptor' => config('app.name'),
                'external_reference'   => $order->id,
                'notification_url'     => $this->getWebhookUrl(),
            ]);

            if ($response->failed()) {
                Log::error('MercadoPago card payment failed', [
                    'order_id' => $order->id,
                    'status'   => $response->status(),
                    'body'     => $response->json(),
                ]);

                return $this->handlePaymentError($response->json());
            }

            $data = $response->json();

            return $this->mapPaymentResponse($data);
        } catch (\Exception $e) {
            Log::error('MercadoPago card payment exception', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            return PaymentResult::failed(
                errorCode: 'gateway_error',
                errorMessage: 'Erro ao processar pagamento. Tente novamente.',
                metadata: ['exception' => $e->getMessage()],
            );
        }
    }

    public function generatePix(Order $order): PixData
    {
        try {
            $response = $this->client()->post('/v1/payments', [
                'transaction_amount' => $order->total / 100,
                'description'        => "Pedido #{$order->order_number}",
                'payment_method_id'  => 'pix',
                'payer'              => $this->buildPayerData($order),
                'external_reference' => $order->id,
                'notification_url'   => $this->getWebhookUrl(),
            ]);

            if ($response->failed()) {
                Log::error('MercadoPago PIX generation failed', [
                    'order_id' => $order->id,
                    'status'   => $response->status(),
                    'body'     => $response->json(),
                ]);

                throw new \RuntimeException('Falha ao gerar PIX: ' . $response->json('message', 'Erro desconhecido'));
            }

            $data    = $response->json();
            $pixData = $data['point_of_interaction']['transaction_data'] ?? [];

            $expirationMinutes = config('payment.methods.pix.expiration_minutes', 30);

            return new PixData(
                transactionId: (string) $data['id'],
                qrCode: $pixData['qr_code'] ?? '',
                qrCodeBase64: $pixData['qr_code_base64'] ?? '',
                copyPasteCode: $pixData['qr_code'] ?? '',
                amount: $order->total,
                expiresAt: now()->addMinutes($expirationMinutes),
                metadata: [
                    'gateway'    => 'mercadopago',
                    'payment_id' => $data['id'],
                    'status'     => $data['status'],
                ],
            );
        } catch (\Exception $e) {
            Log::error('MercadoPago PIX exception', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function generateBankSlip(Order $order): BankSlipData
    {
        try {
            $daysToExpire = config('payment.methods.bank_slip.days_to_expire', 3);
            $dueDate      = now()->addDays($daysToExpire);

            $response = $this->client()->post('/v1/payments', [
                'transaction_amount' => $order->total / 100,
                'description'        => "Pedido #{$order->order_number}",
                'payment_method_id'  => 'bolbradesco',
                'payer'              => $this->buildPayerData($order),
                'external_reference' => $order->id,
                'date_of_expiration' => $dueDate->toIso8601String(),
                'notification_url'   => $this->getWebhookUrl(),
            ]);

            if ($response->failed()) {
                Log::error('MercadoPago bank slip generation failed', [
                    'order_id' => $order->id,
                    'status'   => $response->status(),
                    'body'     => $response->json(),
                ]);

                throw new \RuntimeException('Falha ao gerar boleto: ' . $response->json('message', 'Erro desconhecido'));
            }

            $data               = $response->json();
            $transactionDetails = $data['transaction_details'] ?? [];

            return new BankSlipData(
                transactionId: (string) $data['id'],
                barcode: $transactionDetails['barcode']['content'] ?? '',
                digitableLine: $transactionDetails['digitable_line'] ?? '',
                pdfUrl: $transactionDetails['external_resource_url'] ?? '',
                amount: $order->total,
                dueDate: $dueDate,
                metadata: [
                    'gateway'    => 'mercadopago',
                    'payment_id' => $data['id'],
                    'status'     => $data['status'],
                ],
            );
        } catch (\Exception $e) {
            Log::error('MercadoPago bank slip exception', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function checkPaymentStatus(Payment $payment): PaymentStatus
    {
        try {
            $response = $this->client()->get("/v1/payments/{$payment->gateway_transaction_id}");

            if ($response->failed()) {
                Log::error('MercadoPago status check failed', [
                    'payment_id' => $payment->id,
                    'gateway_id' => $payment->gateway_transaction_id,
                    'status'     => $response->status(),
                ]);

                return $payment->status;
            }

            $data = $response->json();

            return $this->mapStatus($data['status'] ?? '');
        } catch (\Exception $e) {
            Log::error('MercadoPago status check exception', [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);

            return $payment->status;
        }
    }

    public function refund(Payment $payment, ?int $amount = null): RefundResult
    {
        try {
            $payload = [];

            if ($amount !== null) {
                $payload['amount'] = $amount / 100;
            }

            $response = $this->client()->post(
                "/v1/payments/{$payment->gateway_transaction_id}/refunds",
                $payload,
            );

            if ($response->failed()) {
                Log::error('MercadoPago refund failed', [
                    'payment_id' => $payment->id,
                    'status'     => $response->status(),
                    'body'       => $response->json(),
                ]);

                return RefundResult::failed(
                    errorCode: 'refund_failed',
                    errorMessage: $response->json('message', 'Falha ao processar reembolso'),
                    metadata: ['response' => $response->json()],
                );
            }

            $data = $response->json();

            return RefundResult::success(
                refundId: (string) $data['id'],
                amount: (int) (($data['amount'] ?? 0) * 100),
                metadata: [
                    'gateway'      => 'mercadopago',
                    'status'       => $data['status'],
                    'date_created' => $data['date_created'] ?? null,
                ],
            );
        } catch (\Exception $e) {
            Log::error('MercadoPago refund exception', [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);

            return RefundResult::failed(
                errorCode: 'gateway_error',
                errorMessage: 'Erro ao processar reembolso. Tente novamente.',
                metadata: ['exception' => $e->getMessage()],
            );
        }
    }

    public function getName(): string
    {
        return 'mercadopago';
    }

    public function isAvailable(): bool
    {
        return !empty($this->accessToken) && !empty($this->publicKey);
    }

    public function validateWebhookSignature(string $payload, string $signature): bool
    {
        $webhookSecret = config('payment.gateways.mercadopago.webhook_secret');

        if (empty($webhookSecret)) {
            Log::warning('MercadoPago webhook secret not configured');

            return false;
        }

        // Check for empty signature
        if (empty($signature)) {
            return false;
        }

        // MercadoPago uses x-signature header with ts= and v1= parts
        // Format: ts=timestamp,v1=signature
        $parts = [];

        foreach (explode(',', $signature) as $part) {
            $partPieces = explode('=', $part, 2);

            if (count($partPieces) === 2) {
                $parts[$partPieces[0]] = $partPieces[1];
            }
        }

        $timestamp   = $parts['ts'] ?? '';
        $v1Signature = $parts['v1'] ?? '';

        if (empty($timestamp) || empty($v1Signature)) {
            return false;
        }

        // Check signature age (5 minute tolerance)
        $tolerance = config('payment.webhook.tolerance', 300);

        if (abs(time() - (int) $timestamp) > $tolerance) {
            Log::warning('MercadoPago webhook signature expired');

            return false;
        }

        // Compute expected signature
        $data   = json_decode($payload, true);
        $dataId = $data['data']['id'] ?? '';

        $manifest = sprintf('id:%s;request-id:%s;ts:%s;', $dataId, request()->header('x-request-id', ''), $timestamp);

        $expectedSignature = hash_hmac('sha256', $manifest, $webhookSecret);

        return hash_equals($expectedSignature, $v1Signature);
    }

    public function parseWebhookPayload(string $payload): array
    {
        $data = json_decode($payload, true);

        $action    = $data['action'] ?? '';
        $paymentId = $data['data']['id'] ?? '';

        // Fetch payment details from API for complete data
        if (!empty($paymentId)) {
            try {
                $response = $this->client()->get("/v1/payments/{$paymentId}");

                if ($response->successful()) {
                    $paymentData = $response->json();

                    return [
                        'transaction_id' => (string) $paymentId,
                        'status'         => $this->mapStatus($paymentData['status'] ?? ''),
                        'metadata'       => [
                            'action'             => $action,
                            'payment_type'       => $paymentData['payment_type_id'] ?? '',
                            'external_reference' => $paymentData['external_reference'] ?? '',
                            'status_detail'      => $paymentData['status_detail'] ?? '',
                        ],
                    ];
                }
            } catch (\Exception $e) {
                Log::error('MercadoPago webhook payment fetch failed', [
                    'payment_id' => $paymentId,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return [
            'transaction_id' => (string) $paymentId,
            'status'         => PaymentStatus::Pending,
            'metadata'       => ['action' => $action],
        ];
    }

    /**
     * Get the public key for frontend integration.
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Check if sandbox mode is enabled.
     */
    public function isSandbox(): bool
    {
        return $this->sandbox;
    }

    /**
     * Get the webhook URL for payment notifications.
     */
    private function getWebhookUrl(): string
    {
        try {
            return route('webhooks.mercadopago');
        } catch (\Exception) {
            // Fallback for tests or when route is not defined
            return config('app.url') . '/webhooks/mercadopago';
        }
    }

    /**
     * Get HTTP client configured with authentication.
     */
    private function client(): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->withToken($this->accessToken)
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    /**
     * Build payer data from order.
     *
     * @return array<string, mixed>
     */
    private function buildPayerData(Order $order): array
    {
        $payer = [
            'email'      => $order->customerEmail,
            'first_name' => $this->extractFirstName($order->customerName),
            'last_name'  => $this->extractLastName($order->customerName),
        ];

        // Add identification (CPF) if available
        if ($order->guest_cpf) {
            $payer['identification'] = [
                'type'   => 'CPF',
                'number' => preg_replace('/\D/', '', $order->guest_cpf),
            ];
        }

        // Add phone if available
        if ($order->guest_phone) {
            $phone          = preg_replace('/\D/', '', $order->guest_phone);
            $payer['phone'] = [
                'area_code' => substr($phone, 0, 2),
                'number'    => substr($phone, 2),
            ];
        }

        // Add address if available
        if ($order->shipping_zipcode) {
            $payer['address'] = [
                'zip_code'      => preg_replace('/\D/', '', $order->shipping_zipcode),
                'street_name'   => $order->shipping_street,
                'street_number' => $order->shipping_number,
                'neighborhood'  => $order->shipping_neighborhood,
                'city'          => $order->shipping_city,
                'federal_unit'  => $order->shipping_state,
            ];
        }

        return $payer;
    }

    /**
     * Extract first name from full name.
     */
    private function extractFirstName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));

        return $parts[0] ?? '';
    }

    /**
     * Extract last name from full name.
     */
    private function extractLastName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));

        if (count($parts) <= 1) {
            return '';
        }

        return implode(' ', array_slice($parts, 1));
    }

    /**
     * Map card brand to MercadoPago payment method ID.
     */
    private function mapCardBrand(?string $brand): string
    {
        return match (strtolower($brand ?? '')) {
            'visa' => 'visa',
            'mastercard', 'master' => 'master',
            'amex', 'american express' => 'amex',
            'elo'       => 'elo',
            'hipercard' => 'hipercard',
            'diners', 'diners club' => 'diners',
            default => 'visa',
        };
    }

    /**
     * Map MercadoPago status to internal PaymentStatus.
     */
    private function mapStatus(string $status): PaymentStatus
    {
        return match ($status) {
            'approved' => PaymentStatus::Approved,
            'pending', 'in_process', 'in_mediation' => PaymentStatus::Pending,
            'authorized'   => PaymentStatus::Processing,
            'rejected'     => PaymentStatus::Declined,
            'cancelled'    => PaymentStatus::Cancelled,
            'refunded'     => PaymentStatus::Refunded,
            'charged_back' => PaymentStatus::Refunded,
            default        => PaymentStatus::Failed,
        };
    }

    /**
     * Handle payment error response.
     *
     * @param  array<string, mixed>  $response
     */
    private function handlePaymentError(array $response): PaymentResult
    {
        $status       = $response['status'] ?? '';
        $statusDetail = $response['status_detail'] ?? '';
        $message      = $this->getErrorMessage($statusDetail);

        if ($status === 'rejected') {
            return PaymentResult::declined(
                errorCode: $statusDetail,
                errorMessage: $message,
                metadata: ['response' => $response],
            );
        }

        return PaymentResult::failed(
            errorCode: $statusDetail ?: 'unknown_error',
            errorMessage: $message,
            metadata: ['response' => $response],
        );
    }

    /**
     * Map MercadoPago payment response to PaymentResult.
     *
     * @param  array<string, mixed>  $data
     */
    private function mapPaymentResponse(array $data): PaymentResult
    {
        $status        = $this->mapStatus($data['status'] ?? '');
        $transactionId = (string) ($data['id'] ?? '');

        $metadata = [
            'gateway'            => 'mercadopago',
            'payment_type_id'    => $data['payment_type_id'] ?? '',
            'status_detail'      => $data['status_detail'] ?? '',
            'authorization_code' => $data['authorization_code'] ?? null,
            'date_approved'      => $data['date_approved'] ?? null,
        ];

        if ($status === PaymentStatus::Approved) {
            return PaymentResult::success(
                transactionId: $transactionId,
                status: $status,
                metadata: $metadata,
            );
        }

        if ($status === PaymentStatus::Pending || $status === PaymentStatus::Processing) {
            return PaymentResult::pending(
                transactionId: $transactionId,
                metadata: $metadata,
            );
        }

        return PaymentResult::declined(
            errorCode: $data['status_detail'] ?? 'declined',
            errorMessage: $this->getErrorMessage($data['status_detail'] ?? ''),
            metadata: $metadata,
        );
    }

    /**
     * Get user-friendly error message.
     */
    private function getErrorMessage(string $statusDetail): string
    {
        return match ($statusDetail) {
            'cc_rejected_bad_filled_card_number'   => 'Numero do cartao invalido.',
            'cc_rejected_bad_filled_date'          => 'Data de validade invalida.',
            'cc_rejected_bad_filled_other'         => 'Dados do cartao invalidos.',
            'cc_rejected_bad_filled_security_code' => 'Codigo de seguranca invalido.',
            'cc_rejected_blacklist'                => 'Cartao nao autorizado. Use outro cartao.',
            'cc_rejected_call_for_authorize'       => 'Pagamento recusado. Ligue para a operadora do cartao.',
            'cc_rejected_card_disabled'            => 'Cartao desativado. Entre em contato com a operadora.',
            'cc_rejected_card_error'               => 'Erro no cartao. Tente novamente.',
            'cc_rejected_duplicated_payment'       => 'Pagamento duplicado. Verifique sua fatura.',
            'cc_rejected_high_risk'                => 'Pagamento recusado por motivos de seguranca.',
            'cc_rejected_insufficient_amount'      => 'Saldo insuficiente.',
            'cc_rejected_invalid_installments'     => 'Numero de parcelas invalido.',
            'cc_rejected_max_attempts'             => 'Limite de tentativas excedido. Tente mais tarde.',
            'cc_rejected_other_reason'             => 'Pagamento recusado. Tente outro cartao.',
            default                                => 'Pagamento recusado. Verifique os dados e tente novamente.',
        };
    }
}
