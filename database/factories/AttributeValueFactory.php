<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Domain\Catalog\Models\{Attribute, AttributeValue};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Catalog\Models\AttributeValue>
 */
class AttributeValueFactory extends Factory
{
    protected $model = AttributeValue::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attribute_id' => Attribute::factory(),
            'value'        => fake()->word(),
            'color_hex'    => null,
            'position'     => fake()->numberBetween(0, 100),
        ];
    }

    /**
     * Indicate that the value has a color hex code.
     */
    public function withColor(?string $hex = null): static
    {
        return $this->state(fn (array $attributes) => [
            'color_hex' => $hex ?? fake()->hexColor(),
        ]);
    }

    /**
     * Create a size value.
     */
    public function size(string $size = 'M'): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => $size,
        ]);
    }

    /**
     * Create a color value with hex.
     */
    public function colorValue(string $name, string $hex): static
    {
        return $this->state(fn (array $attributes) => [
            'value'     => $name,
            'color_hex' => $hex,
        ]);
    }
}
