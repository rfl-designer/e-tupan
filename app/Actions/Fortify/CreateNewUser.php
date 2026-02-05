<?php declare(strict_types = 1);

namespace App\Actions\Fortify;

use App\Domain\Customer\Notifications\WelcomeNotification;
use App\Models\User;
use App\Rules\CpfRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'cpf' => [
                'nullable',
                'string',
                'size:14',
                new CpfRule(),
                Rule::unique(User::class),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^\(\d{2}\) \d{4,5}-\d{4}$/',
            ],
            'password' => $this->passwordRules(),
        ], $this->customMessages())->validate();

        $user = User::create([
            'name'     => $input['name'],
            'email'    => $input['email'],
            'cpf'      => $input['cpf'] ?? null,
            'phone'    => $input['phone'] ?? null,
            'password' => $input['password'],
        ]);

        $user->notify(new WelcomeNotification($user));

        return $user;
    }

    /**
     * Get custom validation messages in Portuguese.
     *
     * @return array<string, string>
     */
    protected function customMessages(): array
    {
        return [
            'cpf.size'    => 'O CPF deve estar no formato 000.000.000-00.',
            'cpf.unique'  => 'Este CPF já está cadastrado.',
            'phone.regex' => 'O telefone deve estar no formato (00) 00000-0000.',
            'phone.max'   => 'O telefone não pode ter mais de 20 caracteres.',
        ];
    }
}
