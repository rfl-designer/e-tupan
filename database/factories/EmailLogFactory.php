<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Admin\Enums\EmailLogStatus;
use App\Domain\Admin\Models\EmailLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailLog>
 */
class EmailLogFactory extends Factory
{
    protected $model = EmailLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipient' => fake()->safeEmail(),
            'subject' => fake()->sentence(),
            'mailable_class' => fake()->randomElement([
                'App\\Domain\\Admin\\Mail\\TestEmailConfiguration',
                'App\\Domain\\Checkout\\Mail\\OrderConfirmation',
                'App\\Domain\\Checkout\\Mail\\OrderShipped',
                'App\\Domain\\Admin\\Mail\\PasswordReset',
            ]),
            'status' => fake()->randomElement(EmailLogStatus::cases()),
            'error_message' => null,
            'driver' => fake()->randomElement(['smtp', 'mailgun', 'ses', 'postmark', 'resend', 'log']),
        ];
    }

    /**
     * Indicate that the email was sent successfully.
     */
    public function sent(): static
    {
        return $this->state([
            'status' => EmailLogStatus::Sent,
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the email failed to send.
     */
    public function failed(): static
    {
        return $this->state([
            'status' => EmailLogStatus::Failed,
            'error_message' => fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the email is pending.
     */
    public function pending(): static
    {
        return $this->state([
            'status' => EmailLogStatus::Pending,
            'error_message' => null,
        ]);
    }

    /**
     * Mark as resent from another log.
     */
    public function resentFrom(EmailLog $originalLog): static
    {
        return $this->state([
            'resent_from_id' => $originalLog->id,
        ]);
    }

    /**
     * Create an old log entry (older than retention period).
     */
    public function old(int $days = 91): static
    {
        return $this->state([
            'created_at' => now()->subDays($days),
            'updated_at' => now()->subDays($days),
        ]);
    }
}
