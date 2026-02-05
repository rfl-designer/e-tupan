<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Services;

use App\Domain\Checkout\DTOs\InstallmentOption;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Cache, Http, Log};

class InstallmentService
{
    private const CACHE_TTL_SECONDS = 300; // 5 minutes

    private const BASE_URL = 'https://api.mercadopago.com';

    public function __construct()
    {
    }

    /**
     * Get installment options for a given amount and card brand.
     *
     * @return Collection<int, InstallmentOption>
     */
    public function getInstallments(int $amount, ?string $cardBrand = null): Collection
    {
        $cacheKey = $this->getCacheKey($amount, $cardBrand);

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($amount, $cardBrand) {
            return $this->fetchInstallments($amount, $cardBrand);
        });
    }

    /**
     * Fetch installments from payment gateway API.
     *
     * @return Collection<int, InstallmentOption>
     */
    private function fetchInstallments(int $amount, ?string $cardBrand): Collection
    {
        try {
            $gateway = config('payment.default', 'mock');

            if ($gateway !== 'mercadopago') {
                return $this->calculateLocalInstallments($amount);
            }

            $amountInReais = $amount / 100;
            $accessToken   = config('payment.gateways.mercadopago.access_token');

            $queryParams = [
                'amount' => $amountInReais,
                'locale' => 'pt-BR',
            ];

            if ($cardBrand !== null) {
                $paymentMethodId                  = $this->mapCardBrandToPaymentMethodId($cardBrand);
                $queryParams['payment_method_id'] = $paymentMethodId;
            }

            $response = Http::baseUrl(self::BASE_URL)
                ->withToken($accessToken)
                ->acceptJson()
                ->timeout(10)
                ->get('/v1/payment_methods/installments', $queryParams);

            if ($response->failed()) {
                Log::warning('Failed to fetch installments from Mercado Pago', [
                    'status' => $response->status(),
                    'body'   => $response->json(),
                ]);

                return $this->calculateLocalInstallments($amount);
            }

            $data = $response->json();

            return $this->parseInstallmentsResponse($data, $amount);
        } catch (\Exception $e) {
            Log::error('Exception fetching installments', [
                'error'      => $e->getMessage(),
                'amount'     => $amount,
                'card_brand' => $cardBrand,
            ]);

            return $this->calculateLocalInstallments($amount);
        }
    }

    /**
     * Parse installments response from Mercado Pago.
     *
     * @param  array<int, array<string, mixed>>  $response
     * @return Collection<int, InstallmentOption>
     */
    private function parseInstallmentsResponse(array $response, int $amount): Collection
    {
        $installments        = collect();
        $maxInstallments     = $this->getMaxInstallments();
        $minInstallmentValue = $this->getMinInstallmentValue();

        foreach ($response as $paymentMethod) {
            $payerCosts = $paymentMethod['payer_costs'] ?? [];

            foreach ($payerCosts as $payerCost) {
                $quantity = (int) ($payerCost['installments'] ?? 1);

                if ($quantity > $maxInstallments) {
                    continue;
                }

                $installmentAmount = (int) (($payerCost['installment_amount'] ?? 0) * 100);

                if ($installmentAmount < $minInstallmentValue) {
                    continue;
                }

                $option = InstallmentOption::fromMercadoPago($payerCost, $amount);

                $installments->put($quantity, $option);
            }
        }

        return $installments->sortBy('quantity')->values();
    }

    /**
     * Calculate installments locally when API is unavailable.
     *
     * @return Collection<int, InstallmentOption>
     */
    private function calculateLocalInstallments(int $amount): Collection
    {
        $installments        = collect();
        $maxInstallments     = $this->getMaxInstallments();
        $minInstallmentValue = $this->getMinInstallmentValue();
        $interestFreeCount   = config('payment.installments.interest_free', 3);
        $monthlyInterestRate = config('payment.installments.interest_rate', 1.99) / 100;

        for ($quantity = 1; $quantity <= $maxInstallments; $quantity++) {
            $hasInterest  = $quantity > $interestFreeCount;
            $totalAmount  = $amount;
            $interestRate = 0.0;

            if ($hasInterest) {
                // Calculate compound interest
                $factor       = pow(1 + $monthlyInterestRate, $quantity);
                $totalAmount  = (int) round($amount * $factor);
                $interestRate = $monthlyInterestRate * 100;
            }

            $installmentAmount = (int) ceil($totalAmount / $quantity);

            if ($installmentAmount < $minInstallmentValue) {
                continue;
            }

            $cft = null;

            if ($hasInterest) {
                $cft = (($totalAmount / $amount) - 1) * (12 / $quantity) * 100;
                $cft = round($cft, 2);
            }

            $message = $this->buildInstallmentMessage($quantity, $installmentAmount, $totalAmount, $hasInterest);

            $installments->push(new InstallmentOption(
                quantity: $quantity,
                amount: $amount,
                installmentAmount: $installmentAmount,
                totalAmount: $totalAmount,
                interestRate: $interestRate,
                hasInterest: $hasInterest,
                cft: $cft,
                message: $message,
            ));
        }

        return $installments;
    }

    /**
     * Build display message for installment option.
     */
    private function buildInstallmentMessage(
        int $quantity,
        int $installmentAmount,
        int $totalAmount,
        bool $hasInterest,
    ): string {
        $formattedInstallment = number_format($installmentAmount / 100, 2, ',', '.');

        if ($quantity === 1) {
            return "1x de R$ {$formattedInstallment} a vista";
        }

        if (!$hasInterest) {
            return "{$quantity}x de R$ {$formattedInstallment} sem juros";
        }

        $formattedTotal = number_format($totalAmount / 100, 2, ',', '.');

        return "{$quantity}x de R$ {$formattedInstallment} (R$ {$formattedTotal})";
    }

    /**
     * Get cache key for installments.
     */
    private function getCacheKey(int $amount, ?string $cardBrand): string
    {
        $brand = $cardBrand ?? 'all';

        return "installments:{$amount}:{$brand}";
    }

    /**
     * Map card brand to Mercado Pago payment method ID.
     */
    private function mapCardBrandToPaymentMethodId(string $cardBrand): string
    {
        return match (strtolower($cardBrand)) {
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
     * Get maximum number of installments allowed.
     */
    private function getMaxInstallments(): int
    {
        return (int) config('payment.installments.max_installments', 12);
    }

    /**
     * Get minimum installment value in cents.
     */
    private function getMinInstallmentValue(): int
    {
        return (int) config('payment.installments.min_value', 500);
    }

    /**
     * Clear installments cache.
     */
    public function clearCache(?int $amount = null, ?string $cardBrand = null): void
    {
        if ($amount !== null) {
            $cacheKey = $this->getCacheKey($amount, $cardBrand);
            Cache::forget($cacheKey);

            return;
        }

        // Clear all installments cache
        Cache::flush();
    }
}
