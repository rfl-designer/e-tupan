<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Livewire;

use App\Models\User;
use App\Rules\ValidCpf;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CheckoutIdentification extends Component
{
    /**
     * Guest email.
     */
    #[Validate]
    public string $email = '';

    /**
     * Guest name.
     */
    #[Validate('required|string|min:3|max:255')]
    public string $name = '';

    /**
     * Guest CPF.
     */
    #[Validate]
    public string $cpf = '';

    /**
     * Guest phone.
     */
    #[Validate('nullable|string|regex:/^\(\d{2}\) \d{4,5}-\d{4}$/')]
    public string $phone = '';

    /**
     * Whether to show login form.
     */
    public bool $showLoginForm = false;

    /**
     * Login email.
     */
    public string $loginEmail = '';

    /**
     * Login password.
     */
    public string $loginPassword = '';

    /**
     * Whether an account exists for the email.
     */
    public bool $existingAccount = false;

    /**
     * Mount the component.
     *
     * @param  array<string, string>  $guestData
     */
    public function mount(array $guestData = []): void
    {
        $this->email = $guestData['email'] ?? '';
        $this->name  = $guestData['name'] ?? '';
        $this->cpf   = $guestData['cpf'] ?? '';
        $this->phone = $guestData['phone'] ?? '';
    }

    /**
     * Define validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $emailRule = app()->environment('production')
            ? 'email:rfc,dns'
            : 'email:rfc';

        return [
            'email' => ['required', $emailRule, 'max:255'],
            'cpf'   => ['required', 'string', 'size:14', new ValidCpf()],
        ];
    }

    /**
     * Define validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'O e-mail e obrigatorio.',
            'email.email'    => 'Informe um e-mail valido.',
            'name.required'  => 'O nome e obrigatorio.',
            'name.min'       => 'O nome deve ter pelo menos 3 caracteres.',
            'cpf.required'   => 'O CPF e obrigatorio.',
            'cpf.size'       => 'O CPF deve ter 14 caracteres (com pontuacao).',
            'phone.regex'    => 'Informe o telefone no formato (99) 99999-9999.',
        ];
    }

    /**
     * Check if email exists when field is updated.
     */
    public function updatedEmail(): void
    {
        $this->validateOnly('email');

        if (empty($this->email)) {
            $this->existingAccount = false;

            return;
        }

        $this->existingAccount = User::query()
            ->where('email', $this->email)
            ->exists();

        if ($this->existingAccount) {
            $this->loginEmail = $this->email;
        }
    }

    /**
     * Format CPF as user types.
     */
    public function updatedCpf(): void
    {
        // Remove non-numeric characters
        $cpf = preg_replace('/\D/', '', $this->cpf);

        // Format with mask
        if (strlen($cpf) >= 11) {
            $cpf       = substr($cpf, 0, 11);
            $this->cpf = sprintf(
                '%s.%s.%s-%s',
                substr($cpf, 0, 3),
                substr($cpf, 3, 3),
                substr($cpf, 6, 3),
                substr($cpf, 9, 2),
            );
        } elseif (strlen($cpf) >= 6) {
            $this->cpf = sprintf(
                '%s.%s.%s',
                substr($cpf, 0, 3),
                substr($cpf, 3, 3),
                substr($cpf, 6),
            );
        } elseif (strlen($cpf) >= 3) {
            $this->cpf = sprintf(
                '%s.%s',
                substr($cpf, 0, 3),
                substr($cpf, 3),
            );
        }
    }

    /**
     * Format phone as user types.
     */
    public function updatedPhone(): void
    {
        // Remove non-numeric characters
        $phone = preg_replace('/\D/', '', $this->phone);

        // Format with mask
        if (strlen($phone) >= 11) {
            $phone       = substr($phone, 0, 11);
            $this->phone = sprintf(
                '(%s) %s-%s',
                substr($phone, 0, 2),
                substr($phone, 2, 5),
                substr($phone, 7, 4),
            );
        } elseif (strlen($phone) >= 10) {
            $this->phone = sprintf(
                '(%s) %s-%s',
                substr($phone, 0, 2),
                substr($phone, 2, 4),
                substr($phone, 6),
            );
        } elseif (strlen($phone) >= 2) {
            $this->phone = sprintf(
                '(%s) %s',
                substr($phone, 0, 2),
                substr($phone, 2),
            );
        }
    }

    /**
     * Toggle login form visibility.
     */
    public function toggleLoginForm(): void
    {
        $this->showLoginForm = !$this->showLoginForm;
        $this->loginEmail    = $this->email;
    }

    /**
     * Attempt to login.
     */
    public function login(): void
    {
        $this->validate([
            'loginEmail'    => 'required|email',
            'loginPassword' => 'required',
        ], [
            'loginEmail.required'    => 'O e-mail e obrigatorio.',
            'loginEmail.email'       => 'Informe um e-mail valido.',
            'loginPassword.required' => 'A senha e obrigatoria.',
        ]);

        if (!auth()->attempt(['email' => $this->loginEmail, 'password' => $this->loginPassword])) {
            $this->addError('loginPassword', 'E-mail ou senha incorretos.');

            return;
        }

        session()->regenerate();

        $this->dispatch('checkout-user-logged-in');
    }

    /**
     * Continue as guest.
     */
    public function continueAsGuest(): void
    {
        $this->validate();

        $this->dispatch('guest-data-submitted', [
            'email' => $this->email,
            'name'  => $this->name,
            'cpf'   => $this->cpf,
            'phone' => $this->phone,
        ]);
    }

    public function render(): View
    {
        return view('livewire.checkout.checkout-identification');
    }
}
