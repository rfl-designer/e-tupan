<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Domain\Checkout\Models\{Order, Payment, PaymentLog};
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentLog>
 */
class PaymentLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PaymentLog>
     */
    protected $model = PaymentLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $actions  = ['process_card', 'generate_pix', 'generate_bank_slip', 'check_status', 'refund', 'webhook'];
        $statuses = ['success', 'failed', 'error'];

        return [
            'payment_id'     => null,
            'order_id'       => null,
            'gateway'        => 'mercadopago',
            'action'         => $this->faker->randomElement($actions),
            'status'         => $this->faker->randomElement($statuses),
            'transaction_id' => $this->faker->uuid(),
            'request_data'   => [
                'amount' => $this->faker->numberBetween(1000, 100000),
                'method' => $this->faker->randomElement(['credit_card', 'pix', 'bank_slip']),
            ],
            'response_data' => [
                'id'     => $this->faker->numberBetween(100000, 999999),
                'status' => $this->faker->randomElement(['approved', 'pending', 'rejected']),
            ],
            'error_message'    => null,
            'ip_address'       => $this->faker->ipv4(),
            'user_agent'       => $this->faker->userAgent(),
            'response_time_ms' => $this->faker->numberBetween(50, 2000),
        ];
    }

    /**
     * Indicate that the log is for a payment.
     */
    public function forPayment(Payment $payment): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_id' => $payment->id,
            'order_id'   => $payment->order_id,
        ]);
    }

    /**
     * Indicate that the log is for an order.
     */
    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes): array => [
            'order_id' => $order->id,
        ]);
    }

    /**
     * Indicate that the log represents a success.
     */
    public function success(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status'        => 'success',
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the log represents a failure.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status'        => 'failed',
            'error_message' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the log represents an error.
     */
    public function error(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status'        => 'error',
            'error_message' => 'Gateway connection error: ' . $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the log is for a card payment action.
     */
    public function processCard(): static
    {
        return $this->state(fn (array $attributes): array => [
            'action'       => 'process_card',
            'request_data' => [
                'amount'         => $this->faker->numberBetween(1000, 100000),
                'method'         => 'credit_card',
                'card_last_four' => $this->faker->numerify('####'),
                'card_brand'     => $this->faker->randomElement(['visa', 'mastercard', 'amex']),
                'installments'   => $this->faker->randomElement([1, 2, 3, 6, 12]),
            ],
        ]);
    }

    /**
     * Indicate that the log is for a Pix generation.
     */
    public function generatePix(): static
    {
        return $this->state(fn (array $attributes): array => [
            'action'       => 'generate_pix',
            'request_data' => [
                'amount' => $this->faker->numberBetween(1000, 100000),
                'method' => 'pix',
            ],
            'response_data' => [
                'id'      => $this->faker->numberBetween(100000, 999999),
                'status'  => 'pending',
                'qr_code' => 'PIX_QR_CODE_' . Str::random(20),
            ],
        ]);
    }

    /**
     * Indicate that the log is for a webhook.
     */
    public function webhook(): static
    {
        return $this->state(fn (array $attributes): array => [
            'action'       => 'webhook',
            'request_data' => [
                'type'   => 'payment',
                'action' => 'payment.updated',
            ],
        ]);
    }

    /**
     * Indicate that the log is old (for cleanup tests).
     */
    public function old(int $days = 100): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_at' => now()->subDays($days),
            'updated_at' => now()->subDays($days),
        ]);
    }
}
