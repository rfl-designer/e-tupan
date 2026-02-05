<?php

declare(strict_types=1);

namespace App\Domain\Admin\Models;

use App\Domain\Admin\Enums\EmailLogStatus;
use Database\Factories\EmailLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $recipient
 * @property string $subject
 * @property string $mailable_class
 * @property EmailLogStatus $status
 * @property string|null $error_message
 * @property string|null $driver
 * @property int|null $resent_from_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class EmailLog extends Model
{
    /** @use HasFactory<EmailLogFactory> */
    use HasFactory;

    protected static function newFactory(): EmailLogFactory
    {
        return EmailLogFactory::new();
    }

    protected $fillable = [
        'recipient',
        'subject',
        'mailable_class',
        'status',
        'error_message',
        'driver',
        'resent_from_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => EmailLogStatus::class,
        ];
    }

    /**
     * @return BelongsTo<EmailLog, $this>
     */
    public function resentFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'resent_from_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<EmailLog, $this>
     */
    public function resentCopies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'resent_from_id');
    }

    /**
     * @param  Builder<EmailLog>  $query
     */
    public function scopeSent(Builder $query): void
    {
        $query->where('status', EmailLogStatus::Sent);
    }

    /**
     * @param  Builder<EmailLog>  $query
     */
    public function scopeFailed(Builder $query): void
    {
        $query->where('status', EmailLogStatus::Failed);
    }

    /**
     * @param  Builder<EmailLog>  $query
     */
    public function scopeByRecipient(Builder $query, string $email): void
    {
        $query->where('recipient', 'like', "%{$email}%");
    }

    /**
     * @param  Builder<EmailLog>  $query
     */
    public function scopeByMailableClass(Builder $query, string $class): void
    {
        $query->where('mailable_class', $class);
    }

    /**
     * @param  Builder<EmailLog>  $query
     */
    public function scopeRecent(Builder $query, int $days = 90): void
    {
        $query->where('created_at', '>=', now()->subDays($days));
    }

    public function canBeResent(): bool
    {
        return $this->status === EmailLogStatus::Failed
            && $this->created_at->greaterThanOrEqualTo(now()->subDays(7)->startOfDay());
    }
}
