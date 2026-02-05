<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Domain\Shipping\Models\{Shipment, ShipmentTracking};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShipmentTracking>
 */
class ShipmentTrackingFactory extends Factory
{
    protected $model = ShipmentTracking::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipment_id'       => Shipment::factory(),
            'event_code'        => $this->faker->randomElement(['BDE', 'OEC', 'LDI', 'RO']),
            'event_description' => $this->faker->randomElement([
                'Objeto postado',
                'Objeto em transito',
                'Objeto encaminhado',
                'Saiu para entrega',
                'Objeto entregue ao destinatario',
            ]),
            'status'   => $this->faker->randomElement(['posted', 'in_transit', 'out_for_delivery', 'delivered']),
            'city'     => $this->faker->city(),
            'state'    => $this->faker->randomElement(['SP', 'RJ', 'MG', 'PR', 'RS']),
            'country'  => 'BR',
            'event_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ];
    }

    /**
     * Tracking event for object posted.
     */
    public function posted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event_code'        => 'BDE',
            'event_description' => 'Objeto postado',
            'status'            => 'posted',
        ]);
    }

    /**
     * Tracking event for in transit.
     */
    public function inTransit(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event_code'        => 'RO',
            'event_description' => 'Objeto em transito',
            'status'            => 'in_transit',
        ]);
    }

    /**
     * Tracking event for out for delivery.
     */
    public function outForDelivery(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event_code'        => 'OEC',
            'event_description' => 'Objeto saiu para entrega ao destinatario',
            'status'            => 'out_for_delivery',
        ]);
    }

    /**
     * Tracking event for delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event_code'        => 'LDI',
            'event_description' => 'Objeto entregue ao destinatario',
            'status'            => 'delivered',
        ]);
    }

    /**
     * Tracking event for returned.
     */
    public function returned(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event_code'        => 'LDE',
            'event_description' => 'Objeto devolvido ao remetente',
            'status'            => 'returned',
        ]);
    }
}
