<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Models;

use App\Domain\Admin\Enums\ActivityAction;
use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Relations\{BelongsTo, MorphTo};

class ActivityLog extends Model
{
    protected $fillable = [
        'admin_id',
        'admin_name',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'action'     => ActivityAction::class,
            'properties' => 'array',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by subject.
     *
     * @param  Builder<ActivityLog>  $query
     */
    public function scopeForSubject(Builder $query, Model $subject): Builder
    {
        return $query->where('subject_type', $subject::class)
            ->where('subject_id', $subject->getKey());
    }

    /**
     * Scope to filter by admin.
     *
     * @param  Builder<ActivityLog>  $query
     */
    public function scopeByAdmin(Builder $query, Admin $admin): Builder
    {
        return $query->where('admin_id', $admin->id);
    }

    /**
     * Scope to filter by action.
     *
     * @param  Builder<ActivityLog>  $query
     */
    public function scopeByAction(Builder $query, ActivityAction $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by recent days.
     *
     * @param  Builder<ActivityLog>  $query
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
