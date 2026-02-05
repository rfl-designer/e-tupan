<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Domain\Checkout\Enums\{OrderStatus, PaymentStatus};
use App\Domain\Checkout\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal     = fake()->numberBetween(5000, 100000); // R$ 50,00 a R$ 1.000,00
        $shippingCost = fake()->numberBetween(0, 5000); // R$ 0,00 a R$ 50,00
        $discount     = fake()->numberBetween(0, (int) ($subtotal * 0.3)); // 0% a 30% de desconto

        return [
            'user_id'                 => User::factory(),
            'guest_email'             => null,
            'guest_name'              => null,
            'guest_cpf'               => null,
            'guest_phone'             => null,
            'cart_id'                 => null,
            'order_number'            => Order::generateOrderNumber(),
            'status'                  => OrderStatus::Pending,
            'payment_status'          => PaymentStatus::Pending,
            'shipping_address_id'     => null,
            'shipping_recipient_name' => fake()->name(),
            'shipping_zipcode'        => fake()->numerify('#####-###'),
            'shipping_street'         => fake()->streetName(),
            'shipping_number'         => fake()->buildingNumber(),
            'shipping_complement'     => fake()->optional()->secondaryAddress(),
            'shipping_neighborhood'   => fake()->citySuffix() . ' ' . fake()->firstName(),
            'shipping_city'           => fake()->city(),
            'shipping_state'          => fake()->stateAbbr(),
            'shipping_method'         => fake()->randomElement(['pac', 'sedex', 'expresso']),
            'shipping_carrier'        => fake()->randomElement(['Correios', 'Jadlog', 'Total Express']),
            'shipping_days'           => fake()->numberBetween(1, 15),
            'coupon_id'               => null,
            'coupon_code'             => null,
            'subtotal'                => $subtotal,
            'shipping_cost'           => $shippingCost,
            'discount'                => $discount,
            'total'                   => $subtotal + $shippingCost - $discount,
            'tracking_number'         => null,
            'notes'                   => null,
            'metadata'                => null,
            'placed_at'               => now(),
            'paid_at'                 => null,
            'shipped_at'              => null,
            'delivered_at'            => null,
            'cancelled_at'            => null,
        ];
    }

    /**
     * Indicate that the order is for a guest customer.
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id'     => null,
            'guest_email' => fake()->safeEmail(),
            'guest_name'  => fake()->name(),
            'guest_cpf'   => fake()->numerify('###.###.###-##'),
            'guest_phone' => fake()->numerify('(##) #####-####'),
        ]);
    }

    /**
     * Indicate that the order is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'         => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Pending,
        ]);
    }

    /**
     * Indicate that the order is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'         => OrderStatus::Processing,
            'payment_status' => PaymentStatus::Approved,
            'paid_at'        => now()->subHours(fake()->numberBetween(1, 24)),
        ]);
    }

    /**
     * Indicate that the order is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => PaymentStatus::Approved,
            'paid_at'        => now()->subHours(fake()->numberBetween(1, 48)),
        ]);
    }

    /**
     * Indicate that the order is completed.
     */
    public function completed(): static
    {
        $paidAt      = now()->subDays(fake()->numberBetween(3, 10));
        $shippedAt   = $paidAt->copy()->addDays(1);
        $deliveredAt = $shippedAt->copy()->addDays(fake()->numberBetween(1, 7));

        return $this->state(fn (array $attributes) => [
            'status'          => OrderStatus::Completed,
            'payment_status'  => PaymentStatus::Approved,
            'paid_at'         => $paidAt,
            'shipped_at'      => $shippedAt,
            'delivered_at'    => $deliveredAt,
            'tracking_number' => fake()->regexify('[A-Z]{2}[0-9]{9}[A-Z]{2}'),
        ]);
    }

    /**
     * Indicate that the order is cancelled.
     */
    public function cancelled(?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => OrderStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Indicate that the order is refunded.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'         => OrderStatus::Refunded,
            'payment_status' => PaymentStatus::Refunded,
            'paid_at'        => now()->subDays(5),
        ]);
    }

    /**
     * Indicate that the order is shipped.
     */
    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'          => OrderStatus::Processing,
            'payment_status'  => PaymentStatus::Approved,
            'paid_at'         => now()->subDays(2),
            'shipped_at'      => now()->subDay(),
            'tracking_number' => fake()->regexify('[A-Z]{2}[0-9]{9}[A-Z]{2}'),
        ]);
    }

    /**
     * Indicate that the order has free shipping.
     */
    public function freeShipping(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = $attributes['subtotal'];
            $discount = $attributes['discount'];

            return [
                'shipping_cost' => 0,
                'total'         => $subtotal - $discount,
            ];
        });
    }

    /**
     * Indicate that the order has a coupon applied.
     */
    public function withCoupon(string $code = 'DESCONTO10', int $discount = 1000): static
    {
        return $this->state(function (array $attributes) use ($code, $discount) {
            $subtotal     = $attributes['subtotal'];
            $shippingCost = $attributes['shipping_cost'];

            return [
                'coupon_code' => $code,
                'discount'    => $discount,
                'total'       => $subtotal + $shippingCost - $discount,
            ];
        });
    }

    /**
     * Set specific values for the order.
     */
    public function withValues(int $subtotal, int $shippingCost = 0, int $discount = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'subtotal'      => $subtotal,
            'shipping_cost' => $shippingCost,
            'discount'      => $discount,
            'total'         => $subtotal + $shippingCost - $discount,
        ]);
    }
}
