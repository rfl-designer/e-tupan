<?php declare(strict_types = 1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'                      => fake()->name(),
            'email'                     => fake()->unique()->safeEmail(),
            'cpf'                       => $this->generateValidCpf(),
            'phone'                     => $this->generateBrazilianPhone(),
            'is_active'                 => true,
            'email_verified_at'         => now(),
            'password'                  => static::$password ??= Hash::make('password'),
            'remember_token'            => Str::random(10),
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ];
    }

    /**
     * Generate a valid CPF with mask (000.000.000-00).
     */
    protected function generateValidCpf(): string
    {
        $n = [];

        for ($i = 0; $i < 9; $i++) {
            $n[$i] = random_int(0, 9);
        }

        // Calculate first verification digit
        $sum = 0;

        for ($i = 0, $weight = 10; $i < 9; $i++, $weight--) {
            $sum += $n[$i] * $weight;
        }
        $remainder = $sum % 11;
        $n[9]      = $remainder < 2 ? 0 : 11 - $remainder;

        // Calculate second verification digit
        $sum = 0;

        for ($i = 0, $weight = 11; $i < 10; $i++, $weight--) {
            $sum += $n[$i] * $weight;
        }
        $remainder = $sum % 11;
        $n[10]     = $remainder < 2 ? 0 : 11 - $remainder;

        return sprintf(
            '%d%d%d.%d%d%d.%d%d%d-%d%d',
            $n[0],
            $n[1],
            $n[2],
            $n[3],
            $n[4],
            $n[5],
            $n[6],
            $n[7],
            $n[8],
            $n[9],
            $n[10],
        );
    }

    /**
     * Generate a Brazilian phone number with mask ((00) 00000-0000).
     */
    protected function generateBrazilianPhone(): string
    {
        $ddd = fake()->randomElement([
            '11', '12', '13', '14', '15', '16', '17', '18', '19', // SP
            '21', '22', '24', // RJ
            '27', '28', // ES
            '31', '32', '33', '34', '35', '37', '38', // MG
            '41', '42', '43', '44', '45', '46', // PR
            '47', '48', '49', // SC
            '51', '53', '54', '55', // RS
            '61', // DF
            '62', '64', // GO
            '63', // TO
            '65', '66', // MT
            '67', // MS
            '68', // AC
            '69', // RO
            '71', '73', '74', '75', '77', // BA
            '79', // SE
            '81', '87', // PE
            '82', // AL
            '83', // PB
            '84', // RN
            '85', '88', // CE
            '86', '89', // PI
            '91', '93', '94', // PA
            '92', '97', // AM
            '95', // RR
            '96', // AP
            '98', '99', // MA
        ]);

        // Mobile phones in Brazil start with 9
        $firstDigit      = '9';
        $remainingDigits = fake()->numerify('####-####');

        return sprintf('(%s) %s%s', $ddd, $firstDigit, $remainingDigits);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret'         => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at'   => now(),
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the user has no CPF.
     */
    public function withoutCpf(): static
    {
        return $this->state(fn (array $attributes) => [
            'cpf' => null,
        ]);
    }

    /**
     * Indicate that the user has no phone.
     */
    public function withoutPhone(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => null,
        ]);
    }
}
