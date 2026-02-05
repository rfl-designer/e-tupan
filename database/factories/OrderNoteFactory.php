<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Domain\Admin\Models\{Admin, OrderNote};
use App\Domain\Checkout\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderNote>
 */
class OrderNoteFactory extends Factory
{
    protected $model = OrderNote::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id'            => Order::factory(),
            'admin_id'            => Admin::factory(),
            'note'                => fake()->sentence(),
            'is_customer_visible' => false,
        ];
    }

    /**
     * Indicate that the note is visible to the customer.
     */
    public function customerVisible(): static
    {
        return $this->state(['is_customer_visible' => true]);
    }

    /**
     * Indicate that the note is internal only (not visible to customer).
     */
    public function internalOnly(): static
    {
        return $this->state(['is_customer_visible' => false]);
    }
}
