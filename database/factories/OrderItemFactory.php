<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Domain\Catalog\Models\{Product, ProductVariant};
use App\Domain\Checkout\Models\{Order, OrderItem};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = fake()->numberBetween(1000, 50000); // R$ 10,00 a R$ 500,00
        $quantity  = fake()->numberBetween(1, 5);

        return [
            'order_id'     => Order::factory(),
            'product_id'   => Product::factory(),
            'variant_id'   => null,
            'product_name' => fake()->words(rand(2, 5), true),
            'product_sku'  => fake()->regexify('[A-Z]{3}-[0-9]{4}'),
            'variant_name' => null,
            'variant_sku'  => null,
            'quantity'     => $quantity,
            'unit_price'   => $unitPrice,
            'sale_price'   => null,
            'subtotal'     => $unitPrice * $quantity,
        ];
    }

    /**
     * Indicate that the item has a variant.
     */
    public function withVariant(): static
    {
        return $this->state(function (array $attributes) {
            $variant = ProductVariant::factory()->create([
                'product_id' => $attributes['product_id'],
            ]);

            return [
                'variant_id'   => $variant->id,
                'variant_name' => fake()->randomElement(['P', 'M', 'G', 'GG']) . ' - ' . fake()->safeColorName(),
                'variant_sku'  => fake()->regexify('[A-Z]{3}-[0-9]{4}-[A-Z]{2}'),
            ];
        });
    }

    /**
     * Indicate that the item is on sale.
     */
    public function onSale(?int $salePrice = null): static
    {
        return $this->state(function (array $attributes) use ($salePrice) {
            $unitPrice           = $attributes['unit_price'];
            $calculatedSalePrice = $salePrice ?? (int) ($unitPrice * 0.8); // 20% off by default
            $quantity            = $attributes['quantity'];

            return [
                'sale_price' => $calculatedSalePrice,
                'subtotal'   => $calculatedSalePrice * $quantity,
            ];
        });
    }

    /**
     * Set specific quantity for the item.
     */
    public function withQuantity(int $quantity): static
    {
        return $this->state(function (array $attributes) use ($quantity) {
            $effectivePrice = $attributes['sale_price'] ?? $attributes['unit_price'];

            return [
                'quantity' => $quantity,
                'subtotal' => $effectivePrice * $quantity,
            ];
        });
    }

    /**
     * Set specific unit price for the item.
     */
    public function withPrice(int $unitPrice): static
    {
        return $this->state(function (array $attributes) use ($unitPrice) {
            $quantity = $attributes['quantity'];

            return [
                'unit_price' => $unitPrice,
                'subtotal'   => $unitPrice * $quantity,
            ];
        });
    }

    /**
     * Create item for an existing product.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'product_sku'  => $product->sku,
            'unit_price'   => $product->price,
            'sale_price'   => $product->sale_price,
        ]);
    }

    /**
     * Create item for an existing product variant.
     */
    public function forVariant(ProductVariant $variant): static
    {
        return $this->state(function (array $attributes) use ($variant) {
            $product = $variant->product;

            return [
                'product_id'   => $product->id,
                'variant_id'   => $variant->id,
                'product_name' => $product->name,
                'product_sku'  => $product->sku,
                'variant_name' => $variant->attributeValues->pluck('value')->join(' / '),
                'variant_sku'  => $variant->sku,
                'unit_price'   => $variant->price ?? $product->price,
            ];
        });
    }
}
