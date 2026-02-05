<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Models;

use Database\Factories\PaymentLogFactory;
use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLog extends Model
{
    /** @use HasFactory<PaymentLogFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'payment_id',
        'order_id',
        'gateway',
        'action',
        'status',
        'transaction_id',
        'request_data',
        'response_data',
        'error_message',
        'ip_address',
        'user_agent',
        'response_time_ms',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request_data'     => 'array',
            'response_data'    => 'array',
            'response_time_ms' => 'integer',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PaymentLogFactory
    {
        return PaymentLogFactory::new();
    }

    /**
     * Get the payment that owns the log.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the order that owns the log.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Check if the log represents a success.
     */
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if the log represents a failure.
     */
    public function isFailure(): bool
    {
        return in_array($this->status, ['failed', 'error'], true);
    }

    /**
     * Scope a query to only include logs for a specific payment.
     *
     * @param  Builder<PaymentLog>  $query
     * @return Builder<PaymentLog>
     */
    public function scopeForPayment(Builder $query, string $paymentId): Builder
    {
        return $query->where('payment_id', $paymentId);
    }

    /**
     * Scope a query to only include logs for a specific order.
     *
     * @param  Builder<PaymentLog>  $query
     * @return Builder<PaymentLog>
     */
    public function scopeForOrder(Builder $query, string $orderId): Builder
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Scope a query to only include logs for a specific gateway.
     *
     * @param  Builder<PaymentLog>  $query
     * @return Builder<PaymentLog>
     */
    public function scopeForGateway(Builder $query, string $gateway): Builder
    {
        return $query->where('gateway', $gateway);
    }

    /**
     * Scope a query to only include logs for a specific action.
     *
     * @param  Builder<PaymentLog>  $query
     * @return Builder<PaymentLog>
     */
    public function scopeForAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to only include logs with a specific status.
     *
     * @param  Builder<PaymentLog>  $query
     * @return Builder<PaymentLog>
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include failed logs.
     *
     * @param  Builder<PaymentLog>  $query
     * @return Builder<PaymentLog>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->whereIn('status', ['failed', 'error']);
    }

    /**
     * Scope a query to only include successful logs.
     *
     * @param  Builder<PaymentLog>  $query
     * @return Builder<PaymentLog>
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope a query to only include logs older than a given number of days.
     *
     * @param  Builder<PaymentLog>  $query
     * @return Builder<PaymentLog>
     */
    public function scopeOlderThan(Builder $query, int $days): Builder
    {
        return $query->where('created_at', '<', now()->subDays($days));
    }

    /**
     * Scope a query to only include logs from the last N days.
     *
     * @param  Builder<PaymentLog>  $query
     * @return Builder<PaymentLog>
     */
    public function scopeFromLastDays(Builder $query, int $days): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
