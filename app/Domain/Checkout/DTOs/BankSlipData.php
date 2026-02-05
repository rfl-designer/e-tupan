<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\DTOs;

use Carbon\Carbon;

readonly class BankSlipData
{
    public function __construct(
        public string $transactionId,
        public string $barcode,
        public string $digitableLine,
        public string $pdfUrl,
        public int $amount,
        public Carbon $dueDate,
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
            barcode: $data['barcode'],
            digitableLine: $data['digitable_line'],
            pdfUrl: $data['pdf_url'],
            amount: (int) $data['amount'],
            dueDate: Carbon::parse($data['due_date']),
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Check if the bank slip has expired.
     */
    public function isExpired(): bool
    {
        return $this->dueDate->endOfDay()->isPast();
    }

    /**
     * Get days until due date.
     */
    public function getDaysUntilDue(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return (int) now()->diffInDays($this->dueDate->endOfDay());
    }

    /**
     * Get amount formatted in BRL.
     */
    public function getFormattedAmount(): string
    {
        return 'R$ ' . number_format($this->amount / 100, 2, ',', '.');
    }

    /**
     * Get formatted due date.
     */
    public function getFormattedDueDate(): string
    {
        return $this->dueDate->format('d/m/Y');
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'barcode'        => $this->barcode,
            'digitable_line' => $this->digitableLine,
            'pdf_url'        => $this->pdfUrl,
            'amount'         => $this->amount,
            'due_date'       => $this->dueDate->toDateString(),
            'metadata'       => $this->metadata,
        ];
    }
}
