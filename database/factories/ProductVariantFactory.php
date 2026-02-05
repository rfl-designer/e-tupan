<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Domain\Catalog\Models\{Product, ProductVariant};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Catalog\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id'     => Product::factory()->variable(),
            'sku'            => fake()->unique()->regexify('[A-Z]{3}-[0-9]{4}-[A-Z]{2}'),
            'price'          => null, // Inherits from parent product
            'stock_quantity' => fake()->numberBetween(0, 50),
            'weight'         => null,
            'length'         => null,
            'width'          => null,
            'height'         => null,
            'is_active'      => true,
        ];
    }

    /**
     * Indicate that the variant has a custom price.
     */
    public function withPrice(int $price): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $price,
        ]);
    }

    /**
     * Indicate that the variant is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the variant is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
        ]);
    }

    /**
     * Indicate that the variant has dimensions.
     */
    public function withDimensions(): static
    {
        return $this->state(fn (array $attributes) => [
            'weight' => fake()->randomFloat(3, 0.1, 10),
            'length' => fake()->randomFloat(2, 1, 50),
            'width'  => fake()->randomFloat(2, 1, 50),
            'height' => fake()->randomFloat(2, 1, 50),
        ]);
    }
}
