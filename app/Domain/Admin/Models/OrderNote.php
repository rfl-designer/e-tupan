<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Models;

use App\Domain\Checkout\Models\Order;
use Database\Factories\OrderNoteFactory;
use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderNote extends Model
{
    /** @use HasFactory<OrderNoteFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'admin_id',
        'note',
        'is_customer_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_customer_visible' => 'boolean',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): OrderNoteFactory
    {
        return OrderNoteFactory::new();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Scope to only include customer visible notes.
     *
     * @param  Builder<OrderNote>  $query
     */
    public function scopeCustomerVisible(Builder $query): Builder
    {
        return $query->where('is_customer_visible', true);
    }

    /**
     * Scope to only include internal notes.
     *
     * @param  Builder<OrderNote>  $query
     */
    public function scopeInternalOnly(Builder $query): Builder
    {
        return $query->where('is_customer_visible', false);
    }
}
