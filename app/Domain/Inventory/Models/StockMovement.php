<?php

declare(strict_types = 1);

namespace App\Domain\Inventory\Models;

use App\Domain\Admin\Models\Admin;
use App\Domain\Inventory\Enums\MovementType;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, MorphTo};

class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): StockMovementFactory
    {
        return StockMovementFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'stockable_type',
        'stockable_id',
        'movement_type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'movement_type'   => MovementType::class,
            'quantity'        => 'integer',
            'quantity_before' => 'integer',
            'quantity_after'  => 'integer',
        ];
    }

    /**
     * Get the stockable item (Product or ProductVariant).
     */
    public function stockable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the reference item (Order, Cart, etc.).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the admin who created the movement.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /**
     * Scope to filter by movement type.
     */
    public function scopeOfType(Builder $query, MovementType $type): Builder
    {
        return $query->where('movement_type', $type);
    }

    /**
     * Scope to filter by stockable.
     */
    public function scopeForStockable(Builder $query, string $type, int $id): Builder
    {
        return $query->where('stockable_type', $type)
            ->where('stockable_id', $id);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange(Builder $query, ?string $from = null, ?string $to = null): Builder
    {
        if ($from !== null) {
            $query->where('created_at', '>=', $from);
        }

        if ($to !== null) {
            $query->where('created_at', '<=', $to);
        }

        return $query;
    }

    /**
     * Scope to order by most recent first.
     */
    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }
}
