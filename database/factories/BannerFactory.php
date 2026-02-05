<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Domain\Admin\Models\Admin;
use App\Domain\Marketing\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    protected $model = Banner::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title'         => fake()->words(3, true),
            'image_desktop' => 'banners/desktop/large/' . fake()->uuid() . '.webp',
            'image_mobile'  => null,
            'link'          => fake()->optional()->url(),
            'alt_text'      => fake()->optional()->sentence(4),
            'position'      => 0,
            'is_active'     => true,
            'starts_at'     => null,
            'ends_at'       => null,
            'created_by'    => null,
            'updated_by'    => null,
        ];
    }

    /**
     * Indicate that the banner is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the banner is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the banner has a mobile image.
     */
    public function withMobileImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'image_mobile' => 'banners/mobile/large/' . fake()->uuid() . '.webp',
        ]);
    }

    /**
     * Indicate that the banner has an internal link.
     */
    public function withInternalLink(string $path = '/categoria/promocoes'): static
    {
        return $this->state(fn (array $attributes) => [
            'link' => $path,
        ]);
    }

    /**
     * Indicate that the banner has an external link.
     */
    public function withExternalLink(string $url = 'https://example.com'): static
    {
        return $this->state(fn (array $attributes) => [
            'link' => $url,
        ]);
    }

    /**
     * Indicate that the banner has no link.
     */
    public function withoutLink(): static
    {
        return $this->state(fn (array $attributes) => [
            'link' => null,
        ]);
    }

    /**
     * Indicate that the banner is within a valid date range.
     */
    public function valid(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'ends_at'   => now()->addMonth(),
        ]);
    }

    /**
     * Indicate that the banner is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subMonth(),
            'ends_at'   => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the banner is scheduled for the future.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->addWeek(),
            'ends_at'   => now()->addMonth(),
        ]);
    }

    /**
     * Indicate that the banner starts immediately (no start date).
     */
    public function startsImmediately(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => null,
        ]);
    }

    /**
     * Indicate that the banner runs indefinitely (no end date).
     */
    public function runsIndefinitely(): static
    {
        return $this->state(fn (array $attributes) => [
            'ends_at' => null,
        ]);
    }

    /**
     * Set specific start and end dates for the banner.
     */
    public function withPeriod(?\DateTimeInterface $startsAt, ?\DateTimeInterface $endsAt): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => $startsAt,
            'ends_at'   => $endsAt,
        ]);
    }

    /**
     * Set a specific position for the banner.
     */
    public function atPosition(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }

    /**
     * Indicate that the banner was created by an admin.
     */
    public function createdBy(?Admin $admin = null): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $admin !== null ? $admin->id : Admin::factory(),
        ]);
    }

    /**
     * Create a banner that should be displayed (active and within period).
     */
    public function displayable(): static
    {
        return $this->active()->valid();
    }
}
