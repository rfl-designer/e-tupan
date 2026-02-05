<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Domain\Checkout\Enums\{PaymentMethod, PaymentStatus};
use App\Domain\Checkout\Models\{Order, Payment};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id'                 => Order::factory(),
            'method'                   => PaymentMethod::CreditCard,
            'gateway'                  => 'mercado_pago',
            'status'                   => PaymentStatus::Pending,
            'amount'                   => fake()->numberBetween(5000, 100000),
            'installments'             => 1,
            'gateway_payment_id'       => null,
            'gateway_transaction_id'   => null,
            'gateway_response'         => null,
            'card_last_four'           => null,
            'card_brand'               => null,
            'pix_qr_code'              => null,
            'pix_qr_code_base64'       => null,
            'pix_code'                 => null,
            'bank_slip_url'            => null,
            'bank_slip_barcode'        => null,
            'bank_slip_digitable_line' => null,
            'expires_at'               => null,
            'paid_at'                  => null,
            'refunded_amount'          => 0,
            'refunded_at'              => null,
        ];
    }

    /**
     * Indicate that the payment is via credit card.
     */
    public function creditCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'method'         => PaymentMethod::CreditCard,
            'card_last_four' => fake()->numerify('####'),
            'card_brand'     => fake()->randomElement(['visa', 'mastercard', 'elo', 'amex']),
        ]);
    }

    /**
     * Indicate that the payment is via Pix.
     */
    public function pix(): static
    {
        return $this->state(fn (array $attributes) => [
            'method'      => PaymentMethod::Pix,
            'pix_qr_code' => 'https://example.com/qrcode/' . fake()->uuid(),
            'pix_code'    => '00020126580014br.gov.bcb.pix0136' . fake()->uuid(),
            'expires_at'  => now()->addMinutes(30),
        ]);
    }

    /**
     * Indicate that the payment is via bank slip.
     */
    public function bankSlip(): static
    {
        return $this->state(fn (array $attributes) => [
            'method'                   => PaymentMethod::BankSlip,
            'bank_slip_url'            => 'https://example.com/boleto/' . fake()->uuid() . '.pdf',
            'bank_slip_barcode'        => fake()->numerify('########################################'),
            'bank_slip_digitable_line' => fake()->numerify('#####.##### #####.###### #####.###### # ##############'),
            'expires_at'               => now()->addDays(3),
        ]);
    }

    /**
     * Indicate that the payment is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'                 => PaymentStatus::Approved,
            'gateway_payment_id'     => 'PAY_' . fake()->uuid(),
            'gateway_transaction_id' => 'TXN_' . fake()->uuid(),
            'paid_at'                => now(),
        ]);
    }

    /**
     * Indicate that the payment is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Pending,
        ]);
    }

    /**
     * Indicate that the payment is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Processing,
        ]);
    }

    /**
     * Indicate that the payment is declined.
     */
    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'           => PaymentStatus::Declined,
            'gateway_response' => ['error' => 'insufficient_funds'],
        ]);
    }

    /**
     * Indicate that the payment failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'           => PaymentStatus::Failed,
            'gateway_response' => ['error' => 'gateway_error'],
        ]);
    }

    /**
     * Indicate that the payment is refunded.
     */
    public function refunded(?int $amount = null): static
    {
        return $this->state(function (array $attributes) use ($amount) {
            return [
                'status'          => PaymentStatus::Refunded,
                'paid_at'         => now()->subDays(5),
                'refunded_amount' => $amount ?? $attributes['amount'],
                'refunded_at'     => now(),
            ];
        });
    }

    /**
     * Indicate that the payment is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'     => PaymentStatus::Pending,
            'expires_at' => now()->subHours(1),
        ]);
    }

    /**
     * Set installments for credit card payment.
     */
    public function withInstallments(int $installments): static
    {
        return $this->state(fn (array $attributes) => [
            'method'       => PaymentMethod::CreditCard,
            'installments' => $installments,
        ]);
    }

    /**
     * Set specific amount for the payment.
     */
    public function withAmount(int $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }

    /**
     * Create payment for an existing order.
     */
    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $order->id,
            'amount'   => $order->total,
        ]);
    }
}
