<?php

declare(strict_types = 1);

namespace App\Domain\Institutional\Livewire;

use App\Domain\Institutional\Actions\SendContactEmailAction;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\{Computed, Locked};
use Livewire\Component;

class ContactForm extends Component
{
    #[Locked]
    public ?string $origin = null;

    public string $name = '';

    public string $company = '';

    public string $role = '';

    public string $location = '';

    public string $email = '';

    public string $phone = '';

    public string $topic = '';

    public string $message = '';

    public string $successMessage = '';

    public function mount(?string $origin = null): void
    {
        $this->origin = $origin !== null && config("institutional.contact_forms.{$origin}") !== null
            ? $origin
            : null;

        $this->topic = $this->topics[0] ?? '';
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function topics(): array
    {
        return config("institutional.contact_forms.{$this->formKey()}.topics", []);
    }

    #[Computed]
    public function hasExtendedFields(): bool
    {
        return $this->origin !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:120'],
            'company'  => ['nullable', 'string', 'max:160'],
            'role'     => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'email'    => ['required', 'email', 'max:160'],
            'phone'    => ['nullable', 'string', 'max:30'],
            'topic'    => ['required', 'string', Rule::in($this->topics)],
            'message'  => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required'    => 'Informe seu nome completo.',
            'email.required'   => 'Informe um e-mail válido para contato.',
            'email.email'      => 'Informe um e-mail válido para contato.',
            'topic.required'   => 'Selecione o tipo de solicitação.',
            'topic.in'         => 'Selecione um tipo de solicitação válido.',
            'message.required' => 'Descreva sua necessidade para nossa equipe.',
        ];
    }

    public function submit(): void
    {
        $payload = $this->validate();

        app(SendContactEmailAction::class)->execute([
            ...$payload,
            'origin'  => $this->origin,
            'subject' => config("institutional.contact_forms.{$this->formKey()}.subject"),
        ]);

        $this->reset(['name', 'company', 'role', 'location', 'email', 'phone', 'message']);
        $this->topic          = $this->topics[0] ?? '';
        $this->successMessage = 'Mensagem enviada. Nossa equipe entrará em contato em breve.';
    }

    public function render(): View
    {
        return view('livewire.institutional.contact-form');
    }

    private function formKey(): string
    {
        return $this->origin ?? 'default';
    }
}
