<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Http\Controllers;

use App\Domain\Shipping\Jobs\ProcessTrackingWebhookJob;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Log;

class TrackingWebhookController
{
    /**
     * Handle incoming webhook from Melhor Envio.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Tracking webhook received', [
            'event'       => $payload['event'] ?? 'unknown',
            'shipment_id' => $payload['shipment_id'] ?? null,
        ]);

        // Verify webhook signature if configured
        if (!$this->verifySignature($request)) {
            Log::warning('Tracking webhook signature verification failed');

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        // Dispatch job to process the webhook asynchronously
        ProcessTrackingWebhookJob::dispatch($payload);

        return response()->json(['status' => 'received']);
    }

    /**
     * Verify webhook signature.
     */
    protected function verifySignature(Request $request): bool
    {
        $secret = config('services.melhor_envio.webhook_secret');

        // If no secret is configured, skip verification (development mode)
        if (empty($secret)) {
            return true;
        }

        $signature = $request->header('X-Melhor-Envio-Signature');

        if (!$signature) {
            return false;
        }

        $payload           = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
