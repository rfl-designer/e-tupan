<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Models;

use App\Domain\Checkout\Models\Order;
use App\Domain\Shipping\Enums\ShipmentStatus;
use Database\Factories\ShipmentFactory;
use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Shipment extends Model
{
    /** @use HasFactory<ShipmentFactory> */
    use HasFactory;

    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'quote_id',
        'cart_id',
        'shipment_id',
        'carrier_code',
        'carrier_name',
        'service_code',
        'service_name',
        'shipping_cost',
        'insurance_cost',
        'delivery_days_min',
        'delivery_days_max',
        'estimated_delivery_at',
        'tracking_number',
        'tracking_url',
        'label_url',
        'label_generated_at',
        'recipient_name',
        'recipient_phone',
        'recipient_email',
        'recipient_document',
        'address_zipcode',
        'address_street',
        'address_number',
        'address_complement',
        'address_neighborhood',
        'address_city',
        'address_state',
        'weight',
        'height',
        'width',
        'length',
        'status',
        'posted_at',
        'delivered_at',
        'cancelled_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status'                => ShipmentStatus::class,
            'shipping_cost'         => 'integer',
            'insurance_cost'        => 'integer',
            'delivery_days_min'     => 'integer',
            'delivery_days_max'     => 'integer',
            'weight'                => 'decimal:3',
            'height'                => 'integer',
            'width'                 => 'integer',
            'length'                => 'integer',
            'estimated_delivery_at' => 'date',
            'label_generated_at'    => 'datetime',
            'posted_at'             => 'datetime',
            'delivered_at'          => 'datetime',
            'cancelled_at'          => 'datetime',
        ];
    }

    protected static function newFactory(): ShipmentFactory
    {
        return ShipmentFactory::new();
    }

    /**
     * Get the order for this shipment.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get tracking events for this shipment.
     */
    public function trackings(): HasMany
    {
        return $this->hasMany(ShipmentTracking::class);
    }

    /**
     * Get shipping cost in reais.
     */
    protected function shippingCostInReais(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->shipping_cost / 100,
        );
    }

    /**
     * Get formatted shipping address.
     */
    protected function formattedAddress(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $parts = [
                    $this->address_street,
                    $this->address_number,
                ];

                if ($this->address_complement) {
                    $parts[] = $this->address_complement;
                }

                $parts[] = $this->address_neighborhood;
                $parts[] = $this->address_city . '/' . $this->address_state;
                $parts[] = $this->address_zipcode;

                return implode(', ', array_filter($parts));
            },
        );
    }

    /**
     * Get delivery days description.
     */
    protected function deliveryDaysDescription(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->delivery_days_min === $this->delivery_days_max) {
                    return "{$this->delivery_days_min} dias uteis";
                }

                return "{$this->delivery_days_min} a {$this->delivery_days_max} dias uteis";
            },
        );
    }

    /**
     * Check if shipment can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return $this->status->canBeCancelled();
    }

    /**
     * Check if shipment can generate label.
     */
    public function canGenerateLabel(): bool
    {
        return $this->status->canGenerateLabel();
    }

    /**
     * Check if shipment is trackable.
     */
    public function isTrackable(): bool
    {
        return $this->status->isTrackable() && $this->tracking_number !== null;
    }

    /**
     * Check if shipment has label.
     */
    public function hasLabel(): bool
    {
        return $this->label_url !== null;
    }

    /**
     * Mark shipment as added to ME cart.
     */
    public function markAsCartAdded(string $cartId): self
    {
        $this->cart_id = $cartId;
        $this->status  = ShipmentStatus::CartAdded;
        $this->save();

        return $this;
    }

    /**
     * Mark shipment as purchased.
     */
    public function markAsPurchased(string $shipmentId): self
    {
        $this->shipment_id = $shipmentId;
        $this->status      = ShipmentStatus::Purchased;
        $this->save();

        return $this;
    }

    /**
     * Mark shipment as label generated.
     */
    public function markAsLabelGenerated(string $labelUrl, string $trackingNumber): self
    {
        $this->label_url          = $labelUrl;
        $this->tracking_number    = $trackingNumber;
        $this->label_generated_at = now();
        $this->status             = ShipmentStatus::Generated;
        $this->save();

        return $this;
    }

    /**
     * Mark shipment as posted.
     */
    public function markAsPosted(): self
    {
        $this->status    = ShipmentStatus::Posted;
        $this->posted_at = now();
        $this->save();

        return $this;
    }

    /**
     * Mark shipment as delivered.
     */
    public function markAsDelivered(): self
    {
        $this->status       = ShipmentStatus::Delivered;
        $this->delivered_at = now();
        $this->save();

        return $this;
    }

    /**
     * Mark shipment as cancelled.
     */
    public function markAsCancelled(): self
    {
        $this->status       = ShipmentStatus::Cancelled;
        $this->cancelled_at = now();
        $this->save();

        return $this;
    }

    /**
     * Scope to pending shipments.
     *
     * @param  Builder<Shipment>  $query
     * @return Builder<Shipment>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ShipmentStatus::Pending);
    }

    /**
     * Scope to shipments awaiting label generation.
     *
     * @param  Builder<Shipment>  $query
     * @return Builder<Shipment>
     */
    public function scopeAwaitingLabel(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ShipmentStatus::CartAdded,
            ShipmentStatus::Purchased,
        ]);
    }

    /**
     * Scope to shipments in transit.
     *
     * @param  Builder<Shipment>  $query
     * @return Builder<Shipment>
     */
    public function scopeInTransit(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ShipmentStatus::Posted,
            ShipmentStatus::InTransit,
            ShipmentStatus::OutForDelivery,
        ]);
    }

    /**
     * Scope to delivered shipments.
     *
     * @param  Builder<Shipment>  $query
     * @return Builder<Shipment>
     */
    public function scopeDelivered(Builder $query): Builder
    {
        return $query->where('status', ShipmentStatus::Delivered);
    }

    /**
     * Scope to shipments delivered today.
     *
     * @param  Builder<Shipment>  $query
     * @return Builder<Shipment>
     */
    public function scopeDeliveredToday(Builder $query): Builder
    {
        return $query->where('status', ShipmentStatus::Delivered)
            ->whereDate('delivered_at', today());
    }

    /**
     * Scope to delayed shipments.
     *
     * @param  Builder<Shipment>  $query
     * @return Builder<Shipment>
     */
    public function scopeDelayed(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ShipmentStatus::Posted,
            ShipmentStatus::InTransit,
            ShipmentStatus::OutForDelivery,
        ])->where('estimated_delivery_at', '<', today());
    }
}
