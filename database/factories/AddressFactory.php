<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Domain\Customer\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Address>
     */
    protected $model = Address::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'        => User::factory(),
            'label'          => fake()->randomElement(['Casa', 'Trabalho', 'Outro', null]),
            'recipient_name' => fake()->name(),
            'zipcode'        => fake()->numerify('#####-###'),
            'street'         => fake()->streetName(),
            'number'         => fake()->buildingNumber(),
            'complement'     => fake()->optional()->secondaryAddress(),
            'neighborhood'   => fake()->citySuffix() . ' ' . fake()->lastName(),
            'city'           => fake()->city(),
            'state'          => fake()->randomElement(['SP', 'RJ', 'MG', 'RS', 'PR', 'SC', 'BA', 'PE', 'CE', 'GO']),
            'is_default'     => false,
        ];
    }

    /**
     * Indicate that the address is the default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the address has a specific label.
     */
    public function withLabel(string $label): static
    {
        return $this->state(fn (array $attributes) => [
            'label' => $label,
        ]);
    }

    /**
     * Indicate that the address has no complement.
     */
    public function withoutComplement(): static
    {
        return $this->state(fn (array $attributes) => [
            'complement' => null,
        ]);
    }
}
