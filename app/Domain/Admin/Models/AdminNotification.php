<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Models;

use App\Domain\Admin\Enums\NotificationType;
use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminNotification extends Model
{
    use HasUuids;

    protected $fillable = [
        'admin_id',
        'type',
        'title',
        'message',
        'icon',
        'link',
        'data',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'type'    => NotificationType::class,
            'data'    => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    /**
     * Scope to only include unread notifications.
     *
     * @param  Builder<AdminNotification>  $query
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope to only include read notifications.
     *
     * @param  Builder<AdminNotification>  $query
     */
    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope to filter by admin.
     *
     * @param  Builder<AdminNotification>  $query
     */
    public function scopeForAdmin(Builder $query, Admin $admin): Builder
    {
        return $query->where('admin_id', $admin->id);
    }

    /**
     * Scope to filter by type.
     *
     * @param  Builder<AdminNotification>  $query
     */
    public function scopeOfType(Builder $query, NotificationType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by recent days.
     *
     * @param  Builder<AdminNotification>  $query
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }
}
