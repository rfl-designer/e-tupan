<?php

declare(strict_types = 1);

namespace App\Domain\Payment\Services;

class InstallmentCalculator
{
    private int $interestFreeInstallments;

    private float $interestRate;

    private int $maxInstallments;

    private int $minInstallmentValue;

    public function __construct()
    {
        $this->interestFreeInstallments = (int) config('payment.installments.interest_free', 3);
        $this->interestRate             = (float) config('payment.installments.interest_rate', 1.99);
        $this->maxInstallments          = (int) config('payment.installments.max_installments', 12);
        $this->minInstallmentValue      = (int) config('payment.installments.min_value', 500);
    }

    /**
     * Calculate all available installment options for a given price.
     *
     * @return array<int, array{installments: int, value: int, total: int, interest_free: bool}>
     */
    public function calculate(int $priceInCents): array
    {
        $options = [];

        for ($i = 1; $i <= $this->maxInstallments; $i++) {
            $installmentValue = $this->calculateInstallmentValue($priceInCents, $i);

            if ($installmentValue < $this->minInstallmentValue) {
                break;
            }

            $isInterestFree = $i <= $this->interestFreeInstallments;
            $total          = $isInterestFree ? $priceInCents : $installmentValue * $i;

            $options[] = [
                'installments'  => $i,
                'value'         => $installmentValue,
                'total'         => $total,
                'interest_free' => $isInterestFree,
            ];
        }

        return $options;
    }

    /**
     * Get the best installment option to display (max interest-free or max with interest).
     *
     * @return array{installments: int, value: int, total: int, interest_free: bool}|null
     */
    public function getBestOption(int $priceInCents): ?array
    {
        $options = $this->calculate($priceInCents);

        if (empty($options)) {
            return null;
        }

        // First, find the max interest-free option
        $interestFreeOptions = array_filter($options, fn ($opt) => $opt['interest_free']);

        if (!empty($interestFreeOptions)) {
            return end($interestFreeOptions);
        }

        // If no interest-free option, return the max available
        return end($options);
    }

    /**
     * Get a display-friendly installment summary.
     *
     * @return array{max_interest_free: array{installments: int, value: int}|null, max_with_interest: array{installments: int, value: int}|null}
     */
    public function getDisplaySummary(int $priceInCents): array
    {
        $options = $this->calculate($priceInCents);

        $maxInterestFree = null;
        $maxWithInterest = null;

        foreach ($options as $option) {
            if ($option['interest_free']) {
                $maxInterestFree = [
                    'installments' => $option['installments'],
                    'value'        => $option['value'],
                ];
            } else {
                $maxWithInterest = [
                    'installments' => $option['installments'],
                    'value'        => $option['value'],
                ];
            }
        }

        return [
            'max_interest_free' => $maxInterestFree,
            'max_with_interest' => $maxWithInterest,
        ];
    }

    /**
     * Calculate the installment value for a given number of installments.
     */
    private function calculateInstallmentValue(int $priceInCents, int $installments): int
    {
        if ($installments <= $this->interestFreeInstallments) {
            return (int) ceil($priceInCents / $installments);
        }

        // Calculate with compound interest (Price Table)
        $monthlyRate = $this->interestRate / 100;
        $coefficient = ($monthlyRate * pow(1 + $monthlyRate, $installments)) /
                       (pow(1 + $monthlyRate, $installments) - 1);

        return (int) ceil($priceInCents * $coefficient);
    }
}
