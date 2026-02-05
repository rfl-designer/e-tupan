<?php

declare(strict_types = 1);

use App\Domain\Checkout\Jobs\CleanOldPaymentLogsJob;
use App\Domain\Checkout\Models\PaymentLog;
use App\Domain\Checkout\Services\PaymentLogService;
use Illuminate\Support\Facades\Queue;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->logService = new PaymentLogService();
});

describe('PaymentLogService', function () {
    it('logs a payment action', function () {
        $log = $this->logService->log('process_card', 'success', [
            'gateway'        => 'mercadopago',
            'order_id'       => 'order-123',
            'transaction_id' => 'txn-456',
            'request'        => ['amount' => 10000],
            'response'       => ['status' => 'approved'],
        ]);

        expect($log)->toBeInstanceOf(PaymentLog::class);
        expect($log->action)->toBe('process_card');
        expect($log->status)->toBe('success');
        expect($log->gateway)->toBe('mercadopago');
        expect($log->order_id)->toBe('order-123');
        expect($log->transaction_id)->toBe('txn-456');
    });

    it('logs success action', function () {
        $log = $this->logService->logSuccess('generate_pix', [
            'gateway'  => 'mercadopago',
            'order_id' => 'order-123',
        ]);

        expect($log->status)->toBe('success');
        expect($log->action)->toBe('generate_pix');
    });

    it('logs failure action', function () {
        $log = $this->logService->logFailure('process_card', [
            'gateway'       => 'mercadopago',
            'error_message' => 'Insufficient funds',
        ]);

        expect($log->status)->toBe('failed');
        expect($log->error_message)->toBe('Insufficient funds');
    });

    it('logs error action', function () {
        $log = $this->logService->logError('check_status', [
            'gateway'       => 'mercadopago',
            'error_message' => 'Connection timeout',
        ]);

        expect($log->status)->toBe('error');
        expect($log->error_message)->toBe('Connection timeout');
    });

    it('sanitizes card number in request data', function () {
        $log = $this->logService->log('process_card', 'success', [
            'request' => [
                'card_number' => '4111111111111234',
                'amount'      => 10000,
            ],
        ]);

        // Card number should be masked or redacted
        expect($log->request_data['card_number'])->toMatch('/^\*{4}.*1234$|^\[REDACTED\]$/');
        expect($log->request_data['amount'])->toBe(10000);
    });

    it('sanitizes CVV in request data', function () {
        $log = $this->logService->log('process_card', 'success', [
            'request' => [
                'cvv'           => '123',
                'cvc'           => '456',
                'security_code' => '789',
            ],
        ]);

        expect($log->request_data['cvv'])->toBe('[REDACTED]');
        expect($log->request_data['cvc'])->toBe('[REDACTED]');
        expect($log->request_data['security_code'])->toBe('[REDACTED]');
    });

    it('sanitizes token in request data', function () {
        $log = $this->logService->log('process_card', 'success', [
            'request' => [
                'token'        => 'secret_token_123',
                'access_token' => 'bearer_token_456',
            ],
        ]);

        expect($log->request_data['token'])->toBe('[REDACTED]');
        expect($log->request_data['access_token'])->toBe('[REDACTED]');
    });

    it('sanitizes nested sensitive data', function () {
        $log = $this->logService->log('process_card', 'success', [
            'request' => [
                'payer' => [
                    'card_number' => '5555555555554444',
                    'name'        => 'John Doe',
                ],
            ],
        ]);

        // Card number should be masked or redacted
        expect($log->request_data['payer']['card_number'])->toMatch('/^\*{4}.*4444$|^\[REDACTED\]$/');
        expect($log->request_data['payer']['name'])->toBe('John Doe');
    });

    it('retrieves logs by payment id', function () {
        $paymentId = (string) \Illuminate\Support\Str::uuid();

        PaymentLog::factory()->count(3)->create(['payment_id' => $paymentId]);
        PaymentLog::factory()->count(2)->create();

        $logs = $this->logService->getByPayment($paymentId);

        expect($logs)->toHaveCount(3);
    });

    it('retrieves logs by order id', function () {
        $orderId = (string) \Illuminate\Support\Str::uuid();

        PaymentLog::factory()->count(4)->create(['order_id' => $orderId]);
        PaymentLog::factory()->count(2)->create();

        $logs = $this->logService->getByOrder($orderId);

        expect($logs)->toHaveCount(4);
    });

    it('filters logs by gateway', function () {
        PaymentLog::factory()->count(3)->create(['gateway' => 'mercadopago']);
        PaymentLog::factory()->count(2)->create(['gateway' => 'mock']);

        $logs = $this->logService->getFiltered(['gateway' => 'mercadopago']);

        expect($logs->total())->toBe(3);
    });

    it('filters logs by status', function () {
        PaymentLog::factory()->count(3)->success()->create();
        PaymentLog::factory()->count(2)->failed()->create();

        $logs = $this->logService->getFiltered(['status' => 'success']);

        expect($logs->total())->toBe(3);
    });

    it('filters logs by action', function () {
        PaymentLog::factory()->count(3)->processCard()->create();
        PaymentLog::factory()->count(2)->generatePix()->create();

        $logs = $this->logService->getFiltered(['action' => 'process_card']);

        expect($logs->total())->toBe(3);
    });

    it('filters logs by date range', function () {
        PaymentLog::factory()->create(['created_at' => now()->subDays(5)]);
        PaymentLog::factory()->create(['created_at' => now()->subDays(3)]);
        PaymentLog::factory()->create(['created_at' => now()->subDay()]);

        $logs = $this->logService->getFiltered([
            'date_from' => now()->subDays(4)->format('Y-m-d'),
            'date_to'   => now()->subDays(2)->format('Y-m-d'),
        ]);

        expect($logs->total())->toBe(1);
    });

    it('searches logs by transaction id', function () {
        PaymentLog::factory()->create(['transaction_id' => 'TXN-12345']);
        PaymentLog::factory()->create(['transaction_id' => 'TXN-67890']);

        $logs = $this->logService->getFiltered(['search' => '12345']);

        expect($logs->total())->toBe(1);
    });

    it('cleans up old logs', function () {
        PaymentLog::factory()->count(5)->old(100)->create();
        PaymentLog::factory()->count(3)->create();

        $deleted = $this->logService->cleanup(90);

        expect($deleted)->toBe(5);
        expect(PaymentLog::count())->toBe(3);
    });

    it('gets statistics for last 30 days', function () {
        PaymentLog::factory()->count(10)->success()->create();
        PaymentLog::factory()->count(5)->failed()->create();

        $stats = $this->logService->getStatistics(30);

        expect($stats['total'])->toBe(15);
        expect($stats['successful'])->toBe(10);
        expect($stats['failed'])->toBe(5);
        expect($stats['success_rate'])->toBe(66.67);
    });
});

describe('PaymentLog Model', function () {
    it('has correct casts', function () {
        $log = PaymentLog::factory()->create([
            'request_data'  => ['key' => 'value'],
            'response_data' => ['status' => 'approved'],
        ]);

        expect($log->request_data)->toBeArray();
        expect($log->response_data)->toBeArray();
        expect($log->response_time_ms)->toBeInt();
    });

    it('has success scope', function () {
        PaymentLog::factory()->count(3)->success()->create();
        PaymentLog::factory()->count(2)->failed()->create();

        expect(PaymentLog::successful()->count())->toBe(3);
    });

    it('has failed scope', function () {
        PaymentLog::factory()->count(3)->success()->create();
        PaymentLog::factory()->count(2)->failed()->create();
        PaymentLog::factory()->count(1)->error()->create();

        expect(PaymentLog::failed()->count())->toBe(3);
    });

    it('has older than scope', function () {
        PaymentLog::factory()->old(100)->create();
        PaymentLog::factory()->create();

        expect(PaymentLog::olderThan(90)->count())->toBe(1);
    });

    it('has from last days scope', function () {
        PaymentLog::factory()->old(100)->create();
        PaymentLog::factory()->create();

        expect(PaymentLog::fromLastDays(30)->count())->toBe(1);
    });

    it('checks if is success', function () {
        $successLog = PaymentLog::factory()->success()->create();
        $failedLog  = PaymentLog::factory()->failed()->create();

        expect($successLog->isSuccess())->toBeTrue();
        expect($failedLog->isSuccess())->toBeFalse();
    });

    it('checks if is failure', function () {
        $successLog = PaymentLog::factory()->success()->create();
        $failedLog  = PaymentLog::factory()->failed()->create();
        $errorLog   = PaymentLog::factory()->error()->create();

        expect($successLog->isFailure())->toBeFalse();
        expect($failedLog->isFailure())->toBeTrue();
        expect($errorLog->isFailure())->toBeTrue();
    });
});

describe('CleanOldPaymentLogsJob', function () {
    it('can be dispatched', function () {
        Queue::fake();

        CleanOldPaymentLogsJob::dispatch();

        Queue::assertPushed(CleanOldPaymentLogsJob::class);
    });

    it('cleans old logs when executed', function () {
        PaymentLog::factory()->count(5)->old(100)->create();
        PaymentLog::factory()->count(3)->create();

        $job = new CleanOldPaymentLogsJob();
        $job->handle(new PaymentLogService());

        expect(PaymentLog::count())->toBe(3);
    });

    it('respects custom days parameter', function () {
        PaymentLog::factory()->old(40)->create();
        PaymentLog::factory()->old(20)->create();
        PaymentLog::factory()->create();

        $job = new CleanOldPaymentLogsJob(30);
        $job->handle(new PaymentLogService());

        expect(PaymentLog::count())->toBe(2);
    });
});
