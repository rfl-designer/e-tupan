<?php declare(strict_types = 1);

namespace App\Domain\Customer\Models;

use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuthLog extends Model
{
    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'authenticatable_type',
        'authenticatable_id',
        'email',
        'event',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata'   => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (AuthLog $model): void {
            $model->created_at = $model->freshTimestamp();
        });
    }

    /**
     * Get the authenticatable entity (User or Admin).
     */
    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by event type.
     *
     * @param  Builder<AuthLog>  $query
     */
    public function scopeByEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    /**
     * Scope to filter by email.
     *
     * @param  Builder<AuthLog>  $query
     */
    public function scopeByEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', $email);
    }

    /**
     * Scope to filter by authenticatable.
     *
     * @param  Builder<AuthLog>  $query
     */
    public function scopeForAuthenticatable(Builder $query, Model $authenticatable): Builder
    {
        return $query->where('authenticatable_type', get_class($authenticatable))
            ->where('authenticatable_id', $authenticatable->getKey());
    }
}
