<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Domain\Catalog\Models\{Product, ProductImage};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Catalog\Models\ProductImage>
 */
class ProductImageFactory extends Factory
{
    protected $model = ProductImage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'variant_id' => null,
            'path'       => 'products/' . fake()->uuid() . '.jpg',
            'alt_text'   => fake()->optional()->sentence(3),
            'position'   => fake()->numberBetween(0, 10),
            'is_primary' => false,
        ];
    }

    /**
     * Indicate that the image is the primary image.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }

    /**
     * Indicate that the image belongs to a variant.
     */
    public function forVariant(int $variantId): static
    {
        return $this->state(fn (array $attributes) => [
            'variant_id' => $variantId,
        ]);
    }
}
