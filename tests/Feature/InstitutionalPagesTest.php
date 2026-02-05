<?php

declare(strict_types = 1);

use App\Domain\Institutional\Livewire\ContactForm;
use App\Domain\Institutional\Mail\ContactFormMail;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('renders institutional pages', function () {
    $this->get('/')->assertSuccessful();
    $this->get('/sobre')->assertSuccessful();
    $this->get('/contato')->assertSuccessful();
    $this->get('/blog')->assertSuccessful();
});

it('renders blog and division details', function () {
    $this->get('/blog/engenharia-clinica-seguranca')->assertSuccessful();
    $this->get('/solucoes/cirurgica')->assertSuccessful();
});

it('returns not found for invalid slugs', function () {
    $this->get('/blog/nao-existe')->assertNotFound();
    $this->get('/solucoes/nao-existe')->assertNotFound();
});

it('sends contact email', function () {
    Mail::fake();

    config(['mail.from.address' => 'contato@tupan.test']);

    Livewire::test(ContactForm::class)
        ->set('name', 'Maria Oliveira')
        ->set('company', 'Hospital Santa Maria')
        ->set('email', 'maria@hospital.com')
        ->set('topic', 'Consultoria Tecnica em Produtos')
        ->set('message', 'Precisamos de suporte para nossa equipe tecnica.')
        ->call('submit')
        ->assertSet('successMessage', 'Mensagem enviada. Nossa equipe entrara em contato em breve.');

    Mail::assertSent(ContactFormMail::class, function (ContactFormMail $mail) {
        return $mail->name === 'Maria Oliveira'
            && $mail->email === 'maria@hospital.com'
            && $mail->topic === 'Consultoria Tecnica em Produtos';
    });
});
