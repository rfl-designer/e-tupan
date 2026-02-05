<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Domain\Cart\Enums\CartStatus;
use App\Domain\Cart\Models\Cart;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
{
    protected $model = Cart::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'          => null,
            'session_id'       => fake()->uuid(),
            'coupon_id'        => null,
            'shipping_zipcode' => null,
            'shipping_method'  => null,
            'shipping_cost'    => null,
            'shipping_days'    => null,
            'status'           => CartStatus::Active,
            'subtotal'         => 0,
            'discount'         => 0,
            'total'            => 0,
            'last_activity_at' => now(),
            'abandoned_at'     => null,
        ];
    }

    /**
     * Indicate that the cart belongs to a user.
     */
    public function forUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id'    => $user?->id ?? User::factory(),
            'session_id' => null,
        ]);
    }

    /**
     * Indicate that the cart belongs to a session (guest).
     */
    public function forSession(?string $sessionId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id'    => null,
            'session_id' => $sessionId ?? fake()->uuid(),
        ]);
    }

    /**
     * Indicate that the cart is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => CartStatus::Active,
            'abandoned_at' => null,
        ]);
    }

    /**
     * Indicate that the cart is abandoned.
     */
    public function abandoned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => CartStatus::Abandoned,
            'abandoned_at' => now(),
        ]);
    }

    /**
     * Indicate that the cart is converted.
     */
    public function converted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CartStatus::Converted,
        ]);
    }

    /**
     * Indicate that the cart has shipping calculated.
     */
    public function withShipping(int $cost = 2500, int $days = 5, string $method = 'PAC'): static
    {
        return $this->state(fn (array $attributes) => [
            'shipping_zipcode' => fake()->regexify('[0-9]{5}-[0-9]{3}'),
            'shipping_method'  => $method,
            'shipping_cost'    => $cost,
            'shipping_days'    => $days,
        ]);
    }

    /**
     * Indicate that the cart has a discount applied.
     */
    public function withDiscount(int $discount = 1000): static
    {
        return $this->state(fn (array $attributes) => [
            'discount' => $discount,
        ]);
    }

    /**
     * Indicate that the cart has old activity (for abandonment testing).
     */
    public function withOldActivity(int $hoursAgo = 48): static
    {
        return $this->state(fn (array $attributes) => [
            'last_activity_at' => now()->subHours($hoursAgo),
        ]);
    }
}
