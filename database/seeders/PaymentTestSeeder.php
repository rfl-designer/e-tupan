<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Domain\Checkout\Models\PaymentLog;
use Illuminate\Database\Seeder;

class PaymentTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates sample payment logs for testing and development.
     */
    public function run(): void
    {
        $this->command->info('Creating payment logs...');

        // Successful card payments
        PaymentLog::factory()
            ->count(10)
            ->processCard()
            ->success()
            ->create([
                'gateway' => 'mercadopago',
            ]);

        // Failed card payments
        PaymentLog::factory()
            ->count(5)
            ->processCard()
            ->failed()
            ->create([
                'gateway'       => 'mercadopago',
                'error_message' => 'cc_rejected_insufficient_amount - Saldo insuficiente',
            ]);

        // Pending PIX payments
        PaymentLog::factory()
            ->count(3)
            ->generatePix()
            ->success()
            ->create([
                'gateway' => 'mercadopago',
            ]);

        // Bank slip generations
        PaymentLog::factory()
            ->count(3)
            ->create([
                'gateway' => 'mercadopago',
                'action'  => 'generate_bank_slip',
                'status'  => 'success',
            ]);

        // Webhook events
        PaymentLog::factory()
            ->count(5)
            ->webhook()
            ->create([
                'gateway' => 'mercadopago',
                'status'  => 'success',
            ]);

        // Refund operations
        PaymentLog::factory()
            ->count(2)
            ->create([
                'gateway' => 'mercadopago',
                'action'  => 'refund',
                'status'  => 'success',
            ]);

        // Failed refunds
        PaymentLog::factory()
            ->create([
                'gateway'       => 'mercadopago',
                'action'        => 'refund',
                'status'        => 'failed',
                'error_message' => 'Refund not allowed for this payment',
            ]);

        // Gateway errors
        PaymentLog::factory()
            ->count(2)
            ->error()
            ->create([
                'gateway'       => 'mercadopago',
                'action'        => 'process_card',
                'error_message' => 'Gateway connection timeout after 30 seconds',
            ]);

        // Old logs (for cleanup testing)
        PaymentLog::factory()
            ->count(5)
            ->old(100)
            ->create([
                'gateway' => 'mercadopago',
                'action'  => 'process_card',
            ]);

        $this->command->info('Payment logs created successfully!');
        $this->command->info('Total logs: ' . PaymentLog::count());
    }
}
