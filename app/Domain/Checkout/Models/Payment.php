<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Models;

use App\Domain\Checkout\Enums\{PaymentMethod, PaymentStatus};
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * The primary key type.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'method',
        'gateway',
        'status',
        'amount',
        'installments',
        'installment_amount',
        'gateway_payment_id',
        'gateway_transaction_id',
        'gateway_response',
        'card_last_four',
        'card_brand',
        'pix_qr_code',
        'pix_qr_code_base64',
        'pix_code',
        'bank_slip_url',
        'bank_slip_barcode',
        'bank_slip_digitable_line',
        'expires_at',
        'paid_at',
        'refunded_amount',
        'refunded_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'method'             => PaymentMethod::class,
            'status'             => PaymentStatus::class,
            'amount'             => 'integer',
            'installments'       => 'integer',
            'installment_amount' => 'integer',
            'gateway_response'   => 'array',
            'refunded_amount'    => 'integer',
            'expires_at'         => 'datetime',
            'paid_at'            => 'datetime',
            'refunded_at'        => 'datetime',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PaymentFactory
    {
        return PaymentFactory::new();
    }

    /**
     * Get the order for the payment.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the amount in reais (BRL).
     */
    protected function amountInReais(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->amount / 100,
        );
    }

    /**
     * Get the refunded amount in reais (BRL).
     */
    protected function refundedAmountInReais(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->refunded_amount / 100,
        );
    }

    /**
     * Get the installment amount in reais (BRL).
     */
    protected function installmentAmountInReais(): Attribute
    {
        return Attribute::make(
            get: fn (): float => ($this->installment_amount ?? ($this->amount / $this->installments)) / 100,
        );
    }

    /**
     * Check if the payment is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === PaymentStatus::Approved;
    }

    /**
     * Check if the payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }

    /**
     * Check if the payment is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === PaymentStatus::Processing;
    }

    /**
     * Check if the payment failed.
     */
    public function isFailed(): bool
    {
        return in_array($this->status, [
            PaymentStatus::Declined,
            PaymentStatus::Failed,
        ], true);
    }

    /**
     * Check if the payment can be refunded.
     */
    public function canBeRefunded(): bool
    {
        return $this->status->canBeRefunded();
    }

    /**
     * Check if the payment is expired.
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if the payment is awaiting confirmation (async methods).
     */
    public function isAwaitingConfirmation(): bool
    {
        return $this->method->isAsynchronous() && $this->isPending();
    }

    /**
     * Check if payment is credit card.
     */
    public function isCreditCard(): bool
    {
        return $this->method === PaymentMethod::CreditCard;
    }

    /**
     * Check if payment is Pix.
     */
    public function isPix(): bool
    {
        return $this->method === PaymentMethod::Pix;
    }

    /**
     * Check if payment is bank slip.
     */
    public function isBankSlip(): bool
    {
        return $this->method === PaymentMethod::BankSlip;
    }

    /**
     * Mark the payment as approved.
     */
    public function markAsApproved(): self
    {
        $this->status  = PaymentStatus::Approved;
        $this->paid_at = now();
        $this->save();

        return $this;
    }

    /**
     * Mark the payment as declined.
     */
    public function markAsDeclined(): self
    {
        $this->status = PaymentStatus::Declined;
        $this->save();

        return $this;
    }

    /**
     * Mark the payment as failed.
     */
    public function markAsFailed(): self
    {
        $this->status = PaymentStatus::Failed;
        $this->save();

        return $this;
    }

    /**
     * Mark the payment as processing.
     */
    public function markAsProcessing(): self
    {
        $this->status = PaymentStatus::Processing;
        $this->save();

        return $this;
    }

    /**
     * Refund the payment.
     */
    public function refund(?int $amount = null): self
    {
        $this->status          = PaymentStatus::Refunded;
        $this->refunded_amount = $amount ?? $this->amount;
        $this->refunded_at     = now();
        $this->save();

        return $this;
    }

    /**
     * Get the display card info (brand + last 4 digits).
     */
    public function getCardDisplayAttribute(): ?string
    {
        if ($this->card_brand === null || $this->card_last_four === null) {
            return null;
        }

        return ucfirst($this->card_brand) . ' **** ' . $this->card_last_four;
    }

    /**
     * Get the installment display text.
     */
    public function getInstallmentDisplayAttribute(): string
    {
        if ($this->installments <= 1) {
            return 'A vista';
        }

        $installmentAmount = number_format(($this->amount / $this->installments) / 100, 2, ',', '.');

        return "{$this->installments}x de R$ {$installmentAmount}";
    }

    /**
     * Scope a query to only include payments with a specific status.
     *
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopeWithStatus(Builder $query, PaymentStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include approved payments.
     *
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Approved);
    }

    /**
     * Scope a query to only include pending payments.
     *
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Pending);
    }

    /**
     * Scope a query to only include payments with a specific method.
     *
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopeWithMethod(Builder $query, PaymentMethod $method): Builder
    {
        return $query->where('method', $method);
    }

    /**
     * Scope a query to only include expired payments.
     *
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->where('status', PaymentStatus::Pending);
    }
}
