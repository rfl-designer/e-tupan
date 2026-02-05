<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Models;

use Database\Factories\ShipmentTrackingFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentTracking extends Model
{
    /** @use HasFactory<ShipmentTrackingFactory> */
    use HasFactory;

    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'shipment_id',
        'event_code',
        'event_description',
        'status',
        'city',
        'state',
        'country',
        'notes',
        'raw_data',
        'event_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'event_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ShipmentTrackingFactory
    {
        return ShipmentTrackingFactory::new();
    }

    /**
     * Get the shipment for this tracking event.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Get formatted location.
     */
    protected function formattedLocation(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                $parts = array_filter([$this->city, $this->state]);

                if (empty($parts)) {
                    return null;
                }

                return implode('/', $parts);
            },
        );
    }

    /**
     * Get formatted event date.
     */
    protected function formattedEventDate(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->event_at->format('d/m/Y H:i'),
        );
    }

    /**
     * Check if this is a delivery event.
     */
    public function isDeliveryEvent(): bool
    {
        return in_array(strtolower($this->status), ['delivered', 'entregue']);
    }

    /**
     * Check if this is a problem event.
     */
    public function isProblemEvent(): bool
    {
        $problemStatuses = ['returned', 'undelivered', 'exception', 'devolvido', 'problema'];

        return in_array(strtolower($this->status), $problemStatuses);
    }
}
