<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class PaymentTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:test
                            {--gateway=mercadopago : The gateway to test}
                            {--amount=10000 : Test amount in cents}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the payment gateway connection and configuration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $gateway = $this->option('gateway');
        $amount  = (int) $this->option('amount');

        $this->info('Testing payment gateway connection...');
        $this->newLine();

        return match ($gateway) {
            'mercadopago' => $this->testMercadoPago($amount),
            'mock'        => $this->testMock($amount),
            default       => $this->failWithMessage("Unknown gateway: {$gateway}"),
        };
    }

    /**
     * Test Mercado Pago gateway.
     */
    private function testMercadoPago(int $amount): int
    {
        $this->info('Gateway: Mercado Pago');
        $this->newLine();

        // Check configuration
        $this->info('Checking configuration...');

        $accessToken = config('payment.gateways.mercadopago.access_token');
        $publicKey   = config('payment.gateways.mercadopago.public_key');
        $sandbox     = config('payment.gateways.mercadopago.sandbox', true);

        if (empty($accessToken)) {
            return $this->failWithMessage('MERCADOPAGO_ACCESS_TOKEN is not configured');
        }

        if (empty($publicKey)) {
            return $this->failWithMessage('MERCADOPAGO_PUBLIC_KEY is not configured');
        }

        $this->line('  Access Token: ' . substr($accessToken, 0, 20) . '...');
        $this->line('  Public Key: ' . substr($publicKey, 0, 20) . '...');
        $this->line('  Sandbox Mode: ' . ($sandbox ? 'Yes' : 'No'));
        $this->newLine();

        // Test API connection
        $this->info('Testing API connection...');

        try {
            $response = Http::baseUrl('https://api.mercadopago.com')
                ->withToken($accessToken)
                ->acceptJson()
                ->timeout(10)
                ->get('/v1/payment_methods');

            if ($response->failed()) {
                return $this->failWithMessage('API connection failed: ' . $response->status());
            }

            $paymentMethods = $response->json();
            $this->line('  Connection: <fg=green>OK</>');
            $this->line('  Payment methods available: ' . count($paymentMethods));
            $this->newLine();

            // List available payment methods
            $this->info('Available payment methods:');
            $creditCards = collect($paymentMethods)
                ->where('payment_type_id', 'credit_card')
                ->pluck('name')
                ->take(5);

            foreach ($creditCards as $card) {
                $this->line("  - {$card}");
            }

            $hasPix    = collect($paymentMethods)->contains('id', 'pix');
            $hasBoleto = collect($paymentMethods)->contains('id', 'bolbradesco');

            $this->line('  - PIX: ' . ($hasPix ? '<fg=green>Available</>' : '<fg=red>Not available</>'));
            $this->line('  - Boleto: ' . ($hasBoleto ? '<fg=green>Available</>' : '<fg=red>Not available</>'));
            $this->newLine();

        } catch (\Exception $e) {
            return $this->failWithMessage('API connection error: ' . $e->getMessage());
        }

        // Test installments API
        $this->info('Testing installments API...');

        try {
            $amountInReais = $amount / 100;

            $response = Http::baseUrl('https://api.mercadopago.com')
                ->withToken($accessToken)
                ->acceptJson()
                ->timeout(10)
                ->get('/v1/payment_methods/installments', [
                    'amount'            => $amountInReais,
                    'payment_method_id' => 'visa',
                ]);

            if ($response->successful()) {
                $installments = $response->json();
                $payerCosts   = $installments[0]['payer_costs'] ?? [];
                $this->line('  Installments API: <fg=green>OK</>');
                $this->line('  Available installments for R$ ' . number_format($amountInReais, 2, ',', '.') . ': ' . count($payerCosts));
            } else {
                $this->line('  Installments API: <fg=yellow>Warning</> (status ' . $response->status() . ')');
            }
        } catch (\Exception $e) {
            $this->line('  Installments API: <fg=yellow>Warning</> (' . $e->getMessage() . ')');
        }

        $this->newLine();
        $this->info('<fg=green>All tests passed!</>');

        return Command::SUCCESS;
    }

    /**
     * Test mock gateway.
     */
    private function testMock(int $amount): int
    {
        $this->info('Gateway: Mock');
        $this->newLine();

        $this->line('  Mock gateway is always available for testing.');
        $this->line('  Test amount: R$ ' . number_format($amount / 100, 2, ',', '.'));
        $this->newLine();

        $this->info('<fg=green>Mock gateway is ready!</>');

        return Command::SUCCESS;
    }

    /**
     * Display failure message and return failure code.
     */
    private function failWithMessage(string $message): int
    {
        $this->error($message);

        return Command::FAILURE;
    }
}
