<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\DTOs;

readonly class RefundResult
{
    public function __construct(
        public bool $success,
        public ?string $refundId = null,
        public ?int $amount = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public array $metadata = [],
    ) {
    }

    /**
     * Create a successful refund result.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function success(
        string $refundId,
        int $amount,
        array $metadata = [],
    ): self {
        return new self(
            success: true,
            refundId: $refundId,
            amount: $amount,
            metadata: $metadata,
        );
    }

    /**
     * Create a failed refund result.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function failed(
        string $errorCode,
        string $errorMessage,
        array $metadata = [],
    ): self {
        return new self(
            success: false,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            metadata: $metadata,
        );
    }

    /**
     * Check if the refund was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Get amount formatted in BRL.
     */
    public function getFormattedAmount(): ?string
    {
        if ($this->amount === null) {
            return null;
        }

        return 'R$ ' . number_format($this->amount / 100, 2, ',', '.');
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success'       => $this->success,
            'refund_id'     => $this->refundId,
            'amount'        => $this->amount,
            'error_code'    => $this->errorCode,
            'error_message' => $this->errorMessage,
            'metadata'      => $this->metadata,
        ];
    }
}
