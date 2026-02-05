<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Contracts;

use App\Domain\Checkout\DTOs\{BankSlipData, CardData, PaymentResult, PixData, RefundResult};
use App\Domain\Checkout\Enums\PaymentStatus;
use App\Domain\Checkout\Models\{Order, Payment};

interface PaymentGatewayInterface
{
    /**
     * Process a credit card payment.
     */
    public function processCard(Order $order, CardData $cardData): PaymentResult;

    /**
     * Generate a Pix payment.
     */
    public function generatePix(Order $order): PixData;

    /**
     * Generate a bank slip (boleto) payment.
     */
    public function generateBankSlip(Order $order): BankSlipData;

    /**
     * Check the current status of a payment.
     */
    public function checkPaymentStatus(Payment $payment): PaymentStatus;

    /**
     * Process a refund for a payment.
     */
    public function refund(Payment $payment, ?int $amount = null): RefundResult;

    /**
     * Get the gateway name.
     */
    public function getName(): string;

    /**
     * Check if the gateway is available/configured.
     */
    public function isAvailable(): bool;

    /**
     * Validate a webhook signature.
     */
    public function validateWebhookSignature(string $payload, string $signature): bool;

    /**
     * Parse webhook payload and return normalized data.
     *
     * @return array{transaction_id: string, status: PaymentStatus, metadata: array<string, mixed>}
     */
    public function parseWebhookPayload(string $payload): array;
}
