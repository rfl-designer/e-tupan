<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Domain\Cart\Models\{Cart, CartItem};
use App\Domain\Catalog\Models\{Product, ProductVariant};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartItem>
 */
class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cart_id'    => Cart::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'quantity'   => fake()->numberBetween(1, 5),
            'unit_price' => fake()->numberBetween(1000, 50000), // R$ 10,00 - R$ 500,00
            'sale_price' => null,
        ];
    }

    /**
     * Indicate that the item belongs to a specific cart.
     */
    public function forCart(Cart $cart): static
    {
        return $this->state(fn (array $attributes) => [
            'cart_id' => $cart->id,
        ]);
    }

    /**
     * Indicate that the item is for a specific product.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
            'unit_price' => $product->price,
            'sale_price' => $product->isOnSale() ? $product->sale_price : null,
        ]);
    }

    /**
     * Indicate that the item is for a specific variant.
     */
    public function forVariant(ProductVariant $variant): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'unit_price' => $variant->getEffectivePrice(),
            'sale_price' => null,
        ]);
    }

    /**
     * Indicate that the item has a specific quantity.
     */
    public function withQuantity(int $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
        ]);
    }

    /**
     * Indicate that the item is on sale.
     */
    public function onSale(?int $salePrice = null): static
    {
        return $this->state(function (array $attributes) use ($salePrice) {
            $unitPrice           = $attributes['unit_price'] ?? 10000;
            $calculatedSalePrice = $salePrice ?? (int) ($unitPrice * 0.8); // 20% off by default

            return [
                'sale_price' => $calculatedSalePrice,
            ];
        });
    }

    /**
     * Indicate that the item has a specific price.
     */
    public function withPrice(int $unitPrice, ?int $salePrice = null): static
    {
        return $this->state(fn (array $attributes) => [
            'unit_price' => $unitPrice,
            'sale_price' => $salePrice,
        ]);
    }
}
