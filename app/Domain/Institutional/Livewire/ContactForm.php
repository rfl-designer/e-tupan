<?php

declare(strict_types = 1);

namespace App\Domain\Institutional\Livewire;

use App\Domain\Institutional\Actions\SendContactEmailAction;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ContactForm extends Component
{
    public string $name = '';

    public string $company = '';

    public string $email = '';

    public string $topic = 'Consultoria Técnica em Produtos';

    public string $message = '';

    public string $successMessage = '';

    /**
     * @return array<string, array<int, string>|string>
     */
    protected function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:160'],
            'email'   => ['required', 'email', 'max:160'],
            'topic'   => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:2000'],
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
            'message.required' => 'Descreva sua necessidade para nossa equipe.',
        ];
    }

    public function submit(): void
    {
        $payload = $this->validate();

        app(SendContactEmailAction::class)->execute($payload);

        $this->reset(['name', 'company', 'email', 'message']);
        $this->topic          = 'Consultoria Técnica em Produtos';
        $this->successMessage = 'Mensagem enviada. Nossa equipe entrará em contato em breve.';
    }

    public function render(): View
    {
        return view('livewire.institutional.contact-form');
    }
}
