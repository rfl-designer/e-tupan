<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\DTOs;

readonly class LabelResult
{
    public function __construct(
        public bool $success,
        public ?string $labelUrl = null,
        public ?string $trackingNumber = null,
        public ?string $shipmentId = null,
        public ?string $errorMessage = null,
        public ?string $errorCode = null,
    ) {
    }

    /**
     * Create successful result.
     */
    public static function success(
        string $labelUrl,
        string $trackingNumber,
        string $shipmentId,
    ): self {
        return new self(
            success: true,
            labelUrl: $labelUrl,
            trackingNumber: $trackingNumber,
            shipmentId: $shipmentId,
        );
    }

    /**
     * Create failed result.
     */
    public static function failure(string $errorMessage, ?string $errorCode = null): self
    {
        return new self(
            success: false,
            errorMessage: $errorMessage,
            errorCode: $errorCode,
        );
    }
}
