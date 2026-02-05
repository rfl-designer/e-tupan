<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Domain\Marketing\Models\{Coupon, CouponUsage};
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CouponUsage>
 */
class CouponUsageFactory extends Factory
{
    protected $model = CouponUsage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'coupon_id'       => Coupon::factory(),
            'user_id'         => null,
            'order_id'        => null,
            'discount_amount' => fake()->numberBetween(500, 5000),
        ];
    }

    /**
     * Indicate that the usage is by a user.
     */
    public function forUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user?->id ?? User::factory(),
        ]);
    }

    /**
     * Indicate that the usage is for a specific coupon.
     */
    public function forCoupon(Coupon $coupon): static
    {
        return $this->state(fn (array $attributes) => [
            'coupon_id' => $coupon->id,
        ]);
    }

    /**
     * Set the discount amount (in cents).
     */
    public function withDiscount(int $amountInCents): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_amount' => $amountInCents,
        ]);
    }
}
