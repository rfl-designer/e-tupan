<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Domain\Marketing\Enums\CouponType;
use App\Domain\Marketing\Models\Coupon;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code'                 => strtoupper(fake()->unique()->lexify('??????')),
            'name'                 => fake()->words(3, true),
            'description'          => fake()->optional()->sentence(),
            'type'                 => fake()->randomElement(CouponType::cases()),
            'value'                => fake()->numberBetween(5, 30),
            'minimum_order_value'  => null,
            'maximum_discount'     => null,
            'usage_limit'          => null,
            'usage_limit_per_user' => null,
            'starts_at'            => null,
            'expires_at'           => null,
            'is_active'            => true,
            'times_used'           => 0,
            'created_by'           => null,
        ];
    }

    /**
     * Indicate that the coupon is a percentage discount.
     */
    public function percentage(int $percentage = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'type'  => CouponType::Percentage,
            'value' => $percentage,
        ]);
    }

    /**
     * Indicate that the coupon is a fixed discount (value in cents).
     */
    public function fixed(int $valueInCents = 1000): static
    {
        return $this->state(fn (array $attributes) => [
            'type'  => CouponType::Fixed,
            'value' => $valueInCents,
        ]);
    }

    /**
     * Indicate that the coupon provides free shipping.
     */
    public function freeShipping(): static
    {
        return $this->state(fn (array $attributes) => [
            'type'  => CouponType::FreeShipping,
            'value' => null,
        ]);
    }

    /**
     * Indicate that the coupon is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the coupon is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the coupon has a minimum order value (in cents).
     */
    public function withMinimumOrder(int $valueInCents = 10000): static
    {
        return $this->state(fn (array $attributes) => [
            'minimum_order_value' => $valueInCents,
        ]);
    }

    /**
     * Indicate that the coupon has a maximum discount (in cents).
     */
    public function withMaximumDiscount(int $valueInCents = 5000): static
    {
        return $this->state(fn (array $attributes) => [
            'maximum_discount' => $valueInCents,
        ]);
    }

    /**
     * Indicate that the coupon has a usage limit.
     */
    public function withUsageLimit(int $limit = 100): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit' => $limit,
        ]);
    }

    /**
     * Indicate that the coupon has a per-user usage limit.
     */
    public function withUsageLimitPerUser(int $limit = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit_per_user' => $limit,
        ]);
    }

    /**
     * Indicate that the coupon is within a valid date range.
     */
    public function valid(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active'  => true,
            'starts_at'  => now()->subDay(),
            'expires_at' => now()->addMonth(),
        ]);
    }

    /**
     * Indicate that the coupon is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at'  => now()->subMonth(),
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the coupon starts in the future.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at'  => now()->addWeek(),
            'expires_at' => now()->addMonth(),
        ]);
    }

    /**
     * Indicate that the coupon has reached its usage limit.
     */
    public function exhausted(): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit' => 10,
            'times_used'  => 10,
        ]);
    }

    /**
     * Indicate that the coupon was created by a user.
     */
    public function createdBy(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user?->id ?? User::factory(),
        ]);
    }

    /**
     * Create a coupon with a specific code.
     */
    public function withCode(string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => strtoupper($code),
        ]);
    }
}
