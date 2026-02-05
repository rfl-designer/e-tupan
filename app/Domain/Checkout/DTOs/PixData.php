<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\DTOs;

use Carbon\Carbon;

readonly class PixData
{
    public function __construct(
        public string $transactionId,
        public string $qrCode,
        public string $qrCodeBase64,
        public string $copyPasteCode,
        public int $amount,
        public Carbon $expiresAt,
        public array $metadata = [],
    ) {
    }

    /**
     * Create from array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            transactionId: $data['transaction_id'],
            qrCode: $data['qr_code'],
            qrCodeBase64: $data['qr_code_base64'],
            copyPasteCode: $data['copy_paste_code'],
            amount: (int) $data['amount'],
            expiresAt: Carbon::parse($data['expires_at']),
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Check if the Pix has expired.
     */
    public function isExpired(): bool
    {
        return $this->expiresAt->isPast();
    }

    /**
     * Get time remaining until expiration.
     */
    public function getRemainingTime(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return (int) now()->diffInSeconds($this->expiresAt);
    }

    /**
     * Get amount formatted in BRL.
     */
    public function getFormattedAmount(): string
    {
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
            'transaction_id'  => $this->transactionId,
            'qr_code'         => $this->qrCode,
            'qr_code_base64'  => $this->qrCodeBase64,
            'copy_paste_code' => $this->copyPasteCode,
            'amount'          => $this->amount,
            'expires_at'      => $this->expiresAt->toIso8601String(),
            'metadata'        => $this->metadata,
        ];
    }
}
