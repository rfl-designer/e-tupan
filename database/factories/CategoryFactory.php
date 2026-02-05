<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Domain\Catalog\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Catalog\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(rand(1, 3), true);

        return [
            'parent_id'        => null,
            'name'             => ucfirst($name),
            'slug'             => \Illuminate\Support\Str::slug($name),
            'description'      => fake()->optional()->paragraph(),
            'image'            => null,
            'meta_title'       => fake()->optional()->sentence(),
            'meta_description' => fake()->optional()->sentence(),
            'position'         => fake()->numberBetween(0, 100),
            'is_active'        => true,
        ];
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the category has a parent.
     */
    public function withParent(?Category $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent?->id ?? Category::factory(),
        ]);
    }

    /**
     * Create a category with children.
     */
    public function withChildren(int $count = 3): static
    {
        return $this->afterCreating(function (Category $category) use ($count) {
            Category::factory()->count($count)->create([
                'parent_id' => $category->id,
            ]);
        });
    }
}
