<?php declare(strict_types = 1);

namespace App\Domain\Admin\Models;

use Database\Factories\AdminFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class Admin extends Authenticatable
{
    /** @use HasFactory<AdminFactory> */
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'password'                => 'hashed',
            'is_active'               => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at'           => 'datetime',
        ];
    }

    /**
     * Check if the admin has the master role.
     */
    public function isMaster(): bool
    {
        return $this->role === 'master';
    }

    /**
     * Check if the admin has the operator role.
     */
    public function isOperator(): bool
    {
        return $this->role === 'operator';
    }

    /**
     * Get the admin's initials.
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): AdminFactory
    {
        return AdminFactory::new();
    }
}
