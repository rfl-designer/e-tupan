<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Domain\Catalog\Enums\AttributeType;
use App\Domain\Catalog\Models\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Catalog\Models\Attribute>
 */
class AttributeFactory extends Factory
{
    protected $model = Attribute::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name'     => ucfirst($name),
            'slug'     => \Illuminate\Support\Str::slug($name),
            'type'     => AttributeType::Select,
            'position' => fake()->numberBetween(0, 100),
        ];
    }

    /**
     * Indicate that the attribute is a color type.
     */
    public function color(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Cor',
            'slug' => 'cor',
            'type' => AttributeType::Color,
        ]);
    }

    /**
     * Indicate that the attribute is a size type.
     */
    public function size(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Tamanho',
            'slug' => 'tamanho',
            'type' => AttributeType::Select,
        ]);
    }

    /**
     * Indicate that the attribute is a text type.
     */
    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AttributeType::Text,
        ]);
    }
}
