<?php declare(strict_types = 1);

namespace App\Domain\Customer\Models;

use App\Models\User;
use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'zipcode',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'is_default',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::saving(function (Address $address): void {
            if ($address->is_default) {
                static::query()
                    ->where('user_id', $address->user_id)
                    ->where('id', '!=', $address->id ?? 0)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Get the user that owns the address.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): AddressFactory
    {
        return AddressFactory::new();
    }

    /**
     * Scope to filter default addresses.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Address>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Address>
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get the full address as a formatted string.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = [
            $this->street,
            $this->number,
        ];

        if ($this->complement) {
            $parts[] = $this->complement;
        }

        $parts[] = $this->neighborhood;
        $parts[] = $this->city . '/' . $this->state;
        $parts[] = $this->zipcode;

        return implode(', ', $parts);
    }
}
