<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\DTOs;

readonly class InstallmentOption
{
    public function __construct(
        public int $quantity,
        public int $amount,
        public int $installmentAmount,
        public int $totalAmount,
        public float $interestRate,
        public bool $hasInterest,
        public ?float $cft = null,
        public ?string $message = null,
    ) {
    }

    /**
     * Create an installment option from Mercado Pago API response.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromMercadoPago(array $data, int $originalAmount): self
    {
        $quantity          = (int) ($data['installments'] ?? 1);
        $installmentAmount = (int) (($data['installment_amount'] ?? 0) * 100);
        $totalAmount       = (int) (($data['total_amount'] ?? 0) * 100);
        $interestRate      = (float) ($data['installment_rate'] ?? 0);
        $hasInterest       = $interestRate > 0;

        $cft = null;

        if ($hasInterest && $originalAmount > 0) {
            $cft = self::calculateCft($originalAmount, $totalAmount, $quantity);
        }

        $message = self::buildMessage($quantity, $installmentAmount, $totalAmount, $hasInterest);

        return new self(
            quantity: $quantity,
            amount: $originalAmount,
            installmentAmount: $installmentAmount,
            totalAmount: $totalAmount,
            interestRate: $interestRate,
            hasInterest: $hasInterest,
            cft: $cft,
            message: $message,
        );
    }

    /**
     * Calculate CFT (Custo Financeiro Total) - Total Financial Cost.
     * Uses IRR (Internal Rate of Return) calculation.
     */
    private static function calculateCft(int $principal, int $total, int $periods): float
    {
        if ($periods <= 1 || $principal <= 0) {
            return 0.0;
        }

        $monthlyPayment = $total / $periods;
        $rate           = ($total - $principal) / $principal / $periods;

        // Simple approximation for CFT
        // CFT = ((total / principal) - 1) * (12 / periods) * 100
        $cft = (($total / $principal) - 1) * (12 / $periods) * 100;

        return round($cft, 2);
    }

    /**
     * Build display message for installment option.
     */
    private static function buildMessage(
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
     * Convert to array for API response.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'quantity'           => $this->quantity,
            'amount'             => $this->amount,
            'installment_amount' => $this->installmentAmount,
            'total_amount'       => $this->totalAmount,
            'interest_rate'      => $this->interestRate,
            'has_interest'       => $this->hasInterest,
            'message'            => $this->message,
        ];

        if ($this->cft !== null) {
            $data['cft'] = $this->cft;
        }

        return $data;
    }
}
