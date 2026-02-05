<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Domain\Catalog\Models\Product;
use App\Domain\Inventory\Models\StockReservation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StockReservation>
 */
class StockReservationFactory extends Factory
{
    protected $model = StockReservation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stockable_type' => Product::class,
            'stockable_id'   => Product::factory(),
            'quantity'       => $this->faker->numberBetween(1, 10),
            'cart_id'        => Str::uuid()->toString(),
            'expires_at'     => now()->addMinutes(30),
            'converted_at'   => null,
        ];
    }

    /**
     * Configure the model as expired.
     */
    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subMinutes(5),
        ]);
    }

    /**
     * Configure the model as converted to sale.
     */
    public function converted(): static
    {
        return $this->state(fn () => [
            'converted_at' => now(),
        ]);
    }

    /**
     * Configure the model as active (not expired and not converted).
     */
    public function active(): static
    {
        return $this->state(fn () => [
            'expires_at'   => now()->addMinutes(30),
            'converted_at' => null,
        ]);
    }

    /**
     * Configure the model with a specific cart ID.
     */
    public function forCart(string $cartId): static
    {
        return $this->state(fn () => [
            'cart_id' => $cartId,
        ]);
    }

    /**
     * Configure the model for a specific stockable.
     */
    public function forStockable(string $type, int $id): static
    {
        return $this->state(fn () => [
            'stockable_type' => $type,
            'stockable_id'   => $id,
        ]);
    }

    /**
     * Configure the model with a specific TTL.
     */
    public function withTtl(int $minutes): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->addMinutes($minutes),
        ]);
    }
}
