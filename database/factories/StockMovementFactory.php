<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Domain\Admin\Models\Admin;
use App\Domain\Catalog\Models\Product;
use App\Domain\Inventory\Enums\MovementType;
use App\Domain\Inventory\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantityBefore = $this->faker->numberBetween(0, 100);
        $quantity       = $this->faker->numberBetween(-20, 50);
        $quantityAfter  = max(0, $quantityBefore + $quantity);

        return [
            'stockable_type'  => Product::class,
            'stockable_id'    => Product::factory(),
            'movement_type'   => $this->faker->randomElement(MovementType::cases()),
            'quantity'        => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after'  => $quantityAfter,
            'reference_type'  => null,
            'reference_id'    => null,
            'notes'           => $this->faker->optional()->sentence(),
            'created_by'      => null,
        ];
    }

    /**
     * Configure the model as a manual entry.
     */
    public function manualEntry(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => MovementType::ManualEntry,
            'quantity'      => abs($attributes['quantity'] ?? $this->faker->numberBetween(1, 50)),
        ]);
    }

    /**
     * Configure the model as a manual exit.
     */
    public function manualExit(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => MovementType::ManualExit,
            'quantity'      => -abs($attributes['quantity'] ?? $this->faker->numberBetween(1, 20)),
        ]);
    }

    /**
     * Configure the model as an adjustment.
     */
    public function adjustment(): static
    {
        return $this->state(fn () => [
            'movement_type' => MovementType::Adjustment,
        ]);
    }

    /**
     * Configure the model as a sale.
     */
    public function sale(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => MovementType::Sale,
            'quantity'      => -abs($attributes['quantity'] ?? $this->faker->numberBetween(1, 10)),
        ]);
    }

    /**
     * Configure the model as a refund.
     */
    public function refund(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => MovementType::Refund,
            'quantity'      => abs($attributes['quantity'] ?? $this->faker->numberBetween(1, 10)),
        ]);
    }

    /**
     * Configure the model with a creator.
     */
    public function withCreator(?Admin $admin = null): static
    {
        return $this->state(fn () => [
            'created_by' => $admin?->id ?? Admin::factory(),
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
}
