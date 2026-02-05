<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Domain\Catalog\Enums\{ProductStatus, ProductType};
use App\Domain\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Catalog\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name  = fake()->unique()->words(rand(2, 5), true);
        $price = fake()->numberBetween(1000, 100000); // R$ 10,00 a R$ 1.000,00

        return [
            'name'              => ucfirst($name),
            'slug'              => \Illuminate\Support\Str::slug($name),
            'short_description' => fake()->optional()->sentence(),
            'description'       => fake()->optional()->paragraphs(3, true),
            'type'              => ProductType::Simple,
            'status'            => ProductStatus::Active,
            'price'             => $price,
            'sale_price'        => null,
            'sale_start_at'     => null,
            'sale_end_at'       => null,
            'cost'              => fake()->optional()->numberBetween(500, $price - 100),
            'sku'               => fake()->unique()->regexify('[A-Z]{3}-[0-9]{4}'),
            'stock_quantity'    => fake()->numberBetween(0, 100),
            'manage_stock'      => true,
            'allow_backorders'  => false,
            'notify_low_stock'  => true,
            'weight'            => fake()->optional()->randomFloat(3, 0.1, 50),
            'length'            => fake()->optional()->randomFloat(2, 1, 100),
            'width'             => fake()->optional()->randomFloat(2, 1, 100),
            'height'            => fake()->optional()->randomFloat(2, 1, 100),
            'meta_title'        => null,
            'meta_description'  => null,
            'created_by'        => null,
            'updated_by'        => null,
        ];
    }

    /**
     * Indicate that the product is a simple product.
     */
    public function simple(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::Simple,
        ]);
    }

    /**
     * Indicate that the product is a variable product.
     */
    public function variable(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::Variable,
        ]);
    }

    /**
     * Indicate that the product is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Active,
        ]);
    }

    /**
     * Indicate that the product is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Draft,
        ]);
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Inactive,
        ]);
    }

    /**
     * Indicate that the product is on sale.
     */
    public function onSale(?int $salePrice = null, ?\DateTime $startAt = null, ?\DateTime $endAt = null): static
    {
        return $this->state(function (array $attributes) use ($salePrice, $startAt, $endAt) {
            $price               = $attributes['price'] ?? 10000;
            $calculatedSalePrice = $salePrice ?? (int) ($price * 0.8); // 20% off by default

            return [
                'sale_price'    => $calculatedSalePrice,
                'sale_start_at' => $startAt,
                'sale_end_at'   => $endAt,
            ];
        });
    }

    /**
     * Indicate that the product is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity'   => 0,
            'manage_stock'     => true,
            'allow_backorders' => false,
        ]);
    }

    /**
     * Indicate that the product has low stock.
     */
    public function lowStock(int $quantity = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => $quantity,
            'manage_stock'   => true,
        ]);
    }

    /**
     * Indicate that the product does not manage stock.
     */
    public function unlimitedStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'manage_stock' => false,
        ]);
    }

    /**
     * Set specific stock quantity.
     */
    public function withStock(int $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => $quantity,
            'manage_stock'   => true,
        ]);
    }
}
