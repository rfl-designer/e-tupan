<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\DTOs;

use App\Domain\Checkout\Enums\PaymentStatus;

readonly class PaymentResult
{
    public function __construct(
        public bool $success,
        public PaymentStatus $status,
        public ?string $transactionId = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public array $metadata = [],
    ) {
    }

    /**
     * Create a successful payment result.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function success(
        string $transactionId,
        PaymentStatus $status = PaymentStatus::Approved,
        array $metadata = [],
    ): self {
        return new self(
            success: true,
            status: $status,
            transactionId: $transactionId,
            metadata: $metadata,
        );
    }

    /**
     * Create a pending payment result.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function pending(
        string $transactionId,
        array $metadata = [],
    ): self {
        return new self(
            success: true,
            status: PaymentStatus::Processing,
            transactionId: $transactionId,
            metadata: $metadata,
        );
    }

    /**
     * Create a failed payment result.
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
            status: PaymentStatus::Failed,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            metadata: $metadata,
        );
    }

    /**
     * Create a declined payment result.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function declined(
        string $errorCode,
        string $errorMessage,
        array $metadata = [],
    ): self {
        return new self(
            success: false,
            status: PaymentStatus::Declined,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            metadata: $metadata,
        );
    }

    /**
     * Check if the payment was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Check if the payment is pending/processing.
     */
    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Processing || $this->status === PaymentStatus::Pending;
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success'        => $this->success,
            'status'         => $this->status->value,
            'transaction_id' => $this->transactionId,
            'error_code'     => $this->errorCode,
            'error_message'  => $this->errorMessage,
            'metadata'       => $this->metadata,
        ];
    }
}
