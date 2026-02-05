<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Domain\Checkout\Models\Order;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id'             => Order::factory(),
            'carrier_code'         => $this->faker->randomElement(['correios_pac', 'correios_sedex', 'jadlog_package']),
            'carrier_name'         => 'Correios',
            'service_code'         => (string) $this->faker->randomNumber(5),
            'service_name'         => $this->faker->randomElement(['PAC', 'SEDEX', 'Jadlog Package']),
            'shipping_cost'        => $this->faker->numberBetween(1500, 5000),
            'insurance_cost'       => 0,
            'delivery_days_min'    => $this->faker->numberBetween(2, 5),
            'delivery_days_max'    => $this->faker->numberBetween(5, 10),
            'recipient_name'       => $this->faker->name(),
            'recipient_phone'      => $this->faker->phoneNumber(),
            'recipient_email'      => $this->faker->email(),
            'recipient_document'   => $this->faker->numerify('###.###.###-##'),
            'address_zipcode'      => $this->faker->numerify('########'),
            'address_street'       => $this->faker->streetName(),
            'address_number'       => $this->faker->buildingNumber(),
            'address_neighborhood' => $this->faker->word(),
            'address_city'         => $this->faker->city(),
            'address_state'        => $this->faker->randomElement(['SP', 'RJ', 'MG', 'PR', 'RS']),
            'weight'               => $this->faker->randomFloat(3, 0.1, 30),
            'height'               => $this->faker->numberBetween(2, 50),
            'width'                => $this->faker->numberBetween(11, 50),
            'length'               => $this->faker->numberBetween(16, 100),
            'status'               => ShipmentStatus::Pending,
        ];
    }

    /**
     * Shipment with pending status.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ShipmentStatus::Pending,
        ]);
    }

    /**
     * Shipment with cart added status.
     */
    public function cartAdded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'cart_id' => $this->faker->uuid(),
            'status'  => ShipmentStatus::CartAdded,
        ]);
    }

    /**
     * Shipment with purchased status.
     */
    public function purchased(): static
    {
        return $this->state(fn (array $attributes): array => [
            'cart_id'     => $this->faker->uuid(),
            'shipment_id' => $this->faker->uuid(),
            'status'      => ShipmentStatus::Purchased,
        ]);
    }

    /**
     * Shipment with label generated.
     */
    public function withLabel(): static
    {
        return $this->state(fn (array $attributes): array => [
            'cart_id'            => $this->faker->uuid(),
            'shipment_id'        => $this->faker->uuid(),
            'label_url'          => $this->faker->url(),
            'tracking_number'    => strtoupper($this->faker->bothify('??###############')),
            'label_generated_at' => now(),
            'status'             => ShipmentStatus::Generated,
        ]);
    }

    /**
     * Shipment posted.
     */
    public function posted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'cart_id'            => $this->faker->uuid(),
            'shipment_id'        => $this->faker->uuid(),
            'label_url'          => $this->faker->url(),
            'tracking_number'    => strtoupper($this->faker->bothify('??###############')),
            'label_generated_at' => now()->subDay(),
            'posted_at'          => now(),
            'status'             => ShipmentStatus::Posted,
        ]);
    }

    /**
     * Shipment in transit.
     */
    public function inTransit(): static
    {
        return $this->state(fn (array $attributes): array => [
            'cart_id'               => $this->faker->uuid(),
            'shipment_id'           => $this->faker->uuid(),
            'label_url'             => $this->faker->url(),
            'tracking_number'       => strtoupper($this->faker->bothify('??###############')),
            'label_generated_at'    => now()->subDays(2),
            'posted_at'             => now()->subDay(),
            'estimated_delivery_at' => now()->addDays(3),
            'status'                => ShipmentStatus::InTransit,
        ]);
    }

    /**
     * Shipment out for delivery.
     */
    public function outForDelivery(): static
    {
        return $this->state(fn (array $attributes): array => [
            'cart_id'               => $this->faker->uuid(),
            'shipment_id'           => $this->faker->uuid(),
            'label_url'             => $this->faker->url(),
            'tracking_number'       => strtoupper($this->faker->bothify('??###############')),
            'label_generated_at'    => now()->subDays(3),
            'posted_at'             => now()->subDays(2),
            'estimated_delivery_at' => now(),
            'status'                => ShipmentStatus::OutForDelivery,
        ]);
    }

    /**
     * Shipment delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes): array => [
            'cart_id'            => $this->faker->uuid(),
            'shipment_id'        => $this->faker->uuid(),
            'label_url'          => $this->faker->url(),
            'tracking_number'    => strtoupper($this->faker->bothify('??###############')),
            'label_generated_at' => now()->subDays(5),
            'posted_at'          => now()->subDays(4),
            'delivered_at'       => now(),
            'status'             => ShipmentStatus::Delivered,
        ]);
    }

    /**
     * Shipment returned.
     */
    public function returned(): static
    {
        return $this->state(fn (array $attributes): array => [
            'cart_id'            => $this->faker->uuid(),
            'shipment_id'        => $this->faker->uuid(),
            'label_url'          => $this->faker->url(),
            'tracking_number'    => strtoupper($this->faker->bothify('??###############')),
            'label_generated_at' => now()->subDays(7),
            'posted_at'          => now()->subDays(6),
            'status'             => ShipmentStatus::Returned,
        ]);
    }

    /**
     * Shipment cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'cancelled_at' => now(),
            'status'       => ShipmentStatus::Cancelled,
        ]);
    }

    /**
     * Delayed shipment.
     */
    public function delayed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'cart_id'               => $this->faker->uuid(),
            'shipment_id'           => $this->faker->uuid(),
            'label_url'             => $this->faker->url(),
            'tracking_number'       => strtoupper($this->faker->bothify('??###############')),
            'label_generated_at'    => now()->subDays(10),
            'posted_at'             => now()->subDays(9),
            'estimated_delivery_at' => now()->subDays(2),
            'status'                => ShipmentStatus::InTransit,
        ]);
    }
}
