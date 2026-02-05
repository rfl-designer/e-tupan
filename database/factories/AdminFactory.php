<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Domain\Admin\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Admin>
 */
class AdminFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Admin>
     */
    protected $model = Admin::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'                      => fake()->name(),
            'email'                     => fake()->unique()->safeEmail(),
            'email_verified_at'         => now(),
            'password'                  => static::$password ??= Hash::make('password'),
            'role'                      => 'operator',
            'is_active'                 => true,
            'remember_token'            => Str::random(10),
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
            'last_login_at'             => null,
            'last_login_ip'             => null,
        ];
    }

    /**
     * Indicate that the admin has the master role.
     */
    public function master(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'master',
        ]);
    }

    /**
     * Indicate that the admin is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the admin's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the admin has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            // Must be at least 16 base32 characters for Google2FA
            'two_factor_secret'         => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_recovery_codes' => encrypt(json_encode([
                'recovery-code-1',
                'recovery-code-2',
                'recovery-code-3',
                'recovery-code-4',
            ])),
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
