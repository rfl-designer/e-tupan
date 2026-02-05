<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Http\Controllers;

use App\Domain\Checkout\Enums\PaymentStatus;
use App\Domain\Checkout\Factories\PaymentGatewayFactory;
use App\Domain\Checkout\Models\Payment;
use Illuminate\Http\{Request, Response};
use Illuminate\Support\Facades\Log;

class PaymentWebhookController
{
    /**
     * Handle MercadoPago webhook.
     */
    public function mercadopago(Request $request): Response
    {
        return $this->handleWebhook($request, 'mercadopago');
    }

    /**
     * Handle mock gateway webhook (for testing).
     */
    public function mock(Request $request): Response
    {
        return $this->handleWebhook($request, 'mock');
    }

    /**
     * Process webhook from specified gateway.
     */
    private function handleWebhook(Request $request, string $gatewayName): Response
    {
        try {
            $gateway   = PaymentGatewayFactory::make($gatewayName);
            $payload   = $request->getContent();
            $signature = $request->header('x-signature', '');

            // Validate webhook signature
            if (!$gateway->validateWebhookSignature($payload, $signature)) {
                Log::warning('Payment webhook signature validation failed', [
                    'gateway' => $gatewayName,
                    'ip'      => $request->ip(),
                ]);

                return response('Invalid signature', 401);
            }

            // Parse the webhook payload
            $data = $gateway->parseWebhookPayload($payload);

            $transactionId = $data['transaction_id'] ?? null;
            $status        = $data['status'] ?? null;
            $metadata      = $data['metadata'] ?? [];

            if (empty($transactionId)) {
                Log::warning('Payment webhook missing transaction ID', [
                    'gateway' => $gatewayName,
                    'payload' => $payload,
                ]);

                return response('Missing transaction ID', 400);
            }

            // Find the payment by gateway transaction ID
            $payment = Payment::query()
                ->where('gateway_transaction_id', $transactionId)
                ->first();

            if (!$payment) {
                // Payment not found - this could be a new payment notification
                // or an old/test transaction. Log and acknowledge.
                Log::info('Payment webhook received for unknown transaction', [
                    'gateway'        => $gatewayName,
                    'transaction_id' => $transactionId,
                    'status'         => $status?->value ?? $status,
                ]);

                return response('OK', 200);
            }

            // Process the status update
            $this->processStatusUpdate($payment, $status, $metadata);

            Log::info('Payment webhook processed successfully', [
                'gateway'        => $gatewayName,
                'payment_id'     => $payment->id,
                'transaction_id' => $transactionId,
                'old_status'     => $payment->getOriginal('status'),
                'new_status'     => $status?->value ?? $status,
            ]);

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Payment webhook processing failed', [
                'gateway' => $gatewayName,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            // Return 200 to prevent webhook retries for processing errors
            // The gateway will retry if we return 5xx errors
            return response('Error processing webhook', 200);
        }
    }

    /**
     * Process a payment status update.
     */
    private function processStatusUpdate(Payment $payment, PaymentStatus $status, array $metadata): void
    {
        // Don't process if status hasn't changed
        if ($payment->status === $status) {
            return;
        }

        // Don't downgrade from final status
        if ($payment->status->isFinal() && !$status->isFinal()) {
            Log::warning('Attempted to downgrade payment from final status', [
                'payment_id'     => $payment->id,
                'current_status' => $payment->status->value,
                'new_status'     => $status->value,
            ]);

            return;
        }

        $previousStatus  = $payment->status;
        $payment->status = $status;

        // Handle status-specific actions
        switch ($status) {
            case PaymentStatus::Approved:
                $payment->paid_at = now();
                $payment->order->markAsPaid();

                break;

            case PaymentStatus::Refunded:
                if (!$payment->refunded_at) {
                    $payment->refunded_at     = now();
                    $payment->refunded_amount = $payment->amount;
                }

                break;

            case PaymentStatus::Cancelled:
            case PaymentStatus::Declined:
            case PaymentStatus::Failed:
                // Could trigger order cancellation or notification here
                break;
        }

        // Store webhook metadata
        $existingMetadata          = $payment->gateway_response ?? [];
        $payment->gateway_response = array_merge($existingMetadata, [
            'webhook_update' => [
                'previous_status' => $previousStatus->value,
                'new_status'      => $status->value,
                'updated_at'      => now()->toIso8601String(),
                'metadata'        => $metadata,
            ],
        ]);

        $payment->save();

        // Dispatch events for status changes
        $this->dispatchStatusChangeEvents($payment, $previousStatus, $status);
    }

    /**
     * Dispatch events for payment status changes.
     */
    private function dispatchStatusChangeEvents(Payment $payment, PaymentStatus $previousStatus, PaymentStatus $newStatus): void
    {
        // This could dispatch domain events like:
        // - PaymentApproved
        // - PaymentDeclined
        // - PaymentRefunded
        // For now, we'll just log the transition
        Log::info('Payment status changed', [
            'payment_id' => $payment->id,
            'order_id'   => $payment->order_id,
            'from'       => $previousStatus->value,
            'to'         => $newStatus->value,
        ]);
    }
}
