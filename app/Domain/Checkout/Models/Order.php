<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Models;

use App\Domain\Admin\Models\OrderNote;
use App\Domain\Cart\Models\Cart;
use App\Domain\Checkout\Enums\OrderStatus;
use App\Domain\Checkout\Enums\PaymentStatus;
use App\Domain\Checkout\Events\OrderCreated;
use App\Domain\Checkout\Events\OrderStatusChanged;
use App\Domain\Customer\Models\Address;
use App\Domain\Marketing\Models\Coupon;
use App\Domain\Shipping\Models\Shipment;
use App\Models\User;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\{Builder, Model, SoftDeletes};
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Support\Str;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

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
        'user_id',
        'guest_email',
        'guest_name',
        'guest_cpf',
        'guest_phone',
        'access_token',
        'cart_id',
        'order_number',
        'status',
        'payment_status',
        'shipping_address_id',
        'shipping_recipient_name',
        'shipping_zipcode',
        'shipping_street',
        'shipping_number',
        'shipping_complement',
        'shipping_neighborhood',
        'shipping_city',
        'shipping_state',
        'shipping_method',
        'shipping_carrier',
        'shipping_days',
        'coupon_id',
        'coupon_code',
        'subtotal',
        'shipping_cost',
        'discount',
        'total',
        'tracking_number',
        'notes',
        'metadata',
        'placed_at',
        'paid_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => OrderCreated::class,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status'         => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'subtotal'       => 'integer',
            'shipping_cost'  => 'integer',
            'discount'       => 'integer',
            'total'          => 'integer',
            'shipping_days'  => 'integer',
            'metadata'       => 'array',
            'placed_at'      => 'datetime',
            'paid_at'        => 'datetime',
            'shipped_at'     => 'datetime',
            'delivered_at'   => 'datetime',
            'cancelled_at'   => 'datetime',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }

            if (empty($order->placed_at)) {
                $order->placed_at = now();
            }

            // Generate access token for guest orders
            if ($order->user_id === null && empty($order->access_token)) {
                $order->access_token = self::generateAccessToken();
            }
        });
    }

    /**
     * Generate a unique access token for guest orders.
     */
    public static function generateAccessToken(): string
    {
        return Str::random(64);
    }

    /**
     * Generate a unique order number.
     */
    public static function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-' . strtoupper(Str::random(6));
        } while (self::where('order_number', $number)->exists());

        return $number;
    }

    /**
     * Get the user that owns the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items for the order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the payments for the order.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the notes for the order.
     *
     * @return HasMany<OrderNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(OrderNote::class);
    }

    /**
     * Get the shipping address for the order.
     */
    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    /**
     * Get the coupon for the order.
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Get the cart for the order.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the shipments for the order.
     *
     * @return HasMany<Shipment, $this>
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /**
     * Check if the order is for a guest customer.
     */
    public function isGuest(): bool
    {
        return $this->user_id === null;
    }

    /**
     * Get the customer email (user or guest).
     */
    public function getCustomerEmailAttribute(): ?string
    {
        return $this->user?->email ?? $this->guest_email;
    }

    /**
     * Get the customer name (user or guest).
     */
    public function getCustomerNameAttribute(): ?string
    {
        return $this->user?->name ?? $this->guest_name;
    }

    /**
     * Get the customer CPF (user or guest).
     */
    public function getCustomerCpfAttribute(): ?string
    {
        return $this->user?->cpf ?? $this->guest_cpf;
    }

    /**
     * Get the customer phone (user or guest).
     */
    public function getCustomerPhoneAttribute(): ?string
    {
        return $this->user?->phone ?? $this->guest_phone;
    }

    /**
     * Get the formatted shipping address.
     */
    public function getFormattedShippingAddressAttribute(): string
    {
        $parts = [
            $this->shipping_street,
            $this->shipping_number,
        ];

        if ($this->shipping_complement) {
            $parts[] = $this->shipping_complement;
        }

        $parts[] = $this->shipping_neighborhood;
        $parts[] = $this->shipping_city . '/' . $this->shipping_state;
        $parts[] = $this->shipping_zipcode;

        return implode(', ', array_filter($parts));
    }

    /**
     * Get the subtotal in reais (BRL).
     */
    protected function subtotalInReais(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->subtotal / 100,
        );
    }

    /**
     * Get the shipping cost in reais (BRL).
     */
    protected function shippingCostInReais(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->shipping_cost / 100,
        );
    }

    /**
     * Get the discount in reais (BRL).
     */
    protected function discountInReais(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->discount / 100,
        );
    }

    /**
     * Get the total in reais (BRL).
     */
    protected function totalInReais(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->total / 100,
        );
    }

    /**
     * Check if the order is paid.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Approved;
    }

    /**
     * Check if the order is pending payment.
     */
    public function isPendingPayment(): bool
    {
        return in_array($this->payment_status, [
            PaymentStatus::Pending,
            PaymentStatus::Processing,
        ], true);
    }

    /**
     * Check if the order is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === OrderStatus::Completed;
    }

    /**
     * Check if the order is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::Cancelled;
    }

    /**
     * Check if the order can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return $this->status->canBeCancelled();
    }

    /**
     * Check if the order can be refunded.
     */
    public function canBeRefunded(): bool
    {
        return $this->status->canBeRefunded() && $this->isPaid();
    }

    /**
     * Mark the order as paid.
     */
    public function markAsPaid(): self
    {
        $this->payment_status = PaymentStatus::Approved;
        $this->paid_at        = now();
        $this->save();

        return $this;
    }

    /**
     * Mark the order as processing.
     */
    public function markAsProcessing(): self
    {
        /** @var OrderStatus $oldStatus */
        $oldStatus = $this->status;
        $this->status = OrderStatus::Processing;
        $this->save();

        $this->dispatchStatusChangedEvent($oldStatus, OrderStatus::Processing);

        return $this;
    }

    /**
     * Mark the order as completed.
     */
    public function markAsCompleted(): self
    {
        /** @var OrderStatus $oldStatus */
        $oldStatus = $this->status;
        $this->status = OrderStatus::Completed;
        $this->delivered_at = now();
        $this->save();

        $this->dispatchStatusChangedEvent($oldStatus, OrderStatus::Completed);

        return $this;
    }

    /**
     * Mark the order as shipped.
     */
    public function markAsShipped(?string $trackingNumber = null): self
    {
        /** @var OrderStatus $oldStatus */
        $oldStatus = $this->status;
        $this->status = OrderStatus::Shipped;
        $this->shipped_at = now();

        if ($trackingNumber !== null) {
            $this->tracking_number = $trackingNumber;
        }

        $this->save();

        $this->dispatchStatusChangedEvent($oldStatus, OrderStatus::Shipped);

        return $this;
    }

    /**
     * Cancel the order.
     */
    public function cancel(): self
    {
        /** @var OrderStatus $oldStatus */
        $oldStatus = $this->status;
        $this->status = OrderStatus::Cancelled;
        $this->cancelled_at = now();
        $this->save();

        $this->dispatchStatusChangedEvent($oldStatus, OrderStatus::Cancelled);

        return $this;
    }

    /**
     * Get the latest payment for the order.
     */
    public function latestPayment(): ?Payment
    {
        return $this->payments()->latest()->first();
    }

    /**
     * Get the approved payment for the order.
     */
    public function approvedPayment(): ?Payment
    {
        return $this->payments()
            ->where('status', PaymentStatus::Approved)
            ->first();
    }

    /**
     * Scope a query to only include orders for a specific user.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include orders for a specific email (guest).
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeForGuestEmail(Builder $query, string $email): Builder
    {
        return $query->where('guest_email', $email);
    }

    /**
     * Scope a query to only include orders with a specific status.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeWithStatus(Builder $query, OrderStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include orders with a specific payment status.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeWithPaymentStatus(Builder $query, PaymentStatus $status): Builder
    {
        return $query->where('payment_status', $status);
    }

    /**
     * Scope a query to only include paid orders.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', PaymentStatus::Approved);
    }

    /**
     * Scope a query to only include pending orders.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::Pending);
    }

    /**
     * Get the estimated delivery date.
     */
    public function getEstimatedDeliveryDateAttribute(): ?\Carbon\Carbon
    {
        if ($this->shipping_days === null) {
            return null;
        }

        $baseDate = $this->shipped_at ?? $this->paid_at ?? $this->placed_at;

        if ($baseDate === null) {
            return null;
        }

        return $baseDate->copy()->addWeekdays($this->shipping_days);
    }

    /**
     * Update the order status and dispatch status changed event.
     */
    public function updateStatus(OrderStatus $newStatus): self
    {
        /** @var OrderStatus $oldStatus */
        $oldStatus = $this->status;

        if ($oldStatus === $newStatus) {
            return $this;
        }

        $this->status = $newStatus;
        $this->save();

        $this->dispatchStatusChangedEvent($oldStatus, $newStatus);

        return $this;
    }

    /**
     * Dispatch the OrderStatusChanged event if status actually changed.
     */
    private function dispatchStatusChangedEvent(OrderStatus $oldStatus, OrderStatus $newStatus): void
    {
        if ($oldStatus === $newStatus) {
            return;
        }

        OrderStatusChanged::dispatch($this, $oldStatus, $newStatus);
    }
}
