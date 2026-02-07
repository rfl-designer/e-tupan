<?php

declare(strict_types = 1);

use App\Domain\Institutional\Livewire\ContactForm;
use App\Domain\Institutional\Mail\ContactFormMail;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('renders institutional pages', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('Tupan Care')
        ->assertDontSee('Marcas Próprias & Importação');
    $this->get('/sobre')->assertSuccessful();
    $this->get('/contato')->assertSuccessful();
    $this->get('/blog')->assertSuccessful();
});

it('renders blog and division details', function () {
    $this->get('/blog/engenharia-clinica-seguranca')->assertSuccessful();
    $this->get('/solucoes/cirurgica')->assertSuccessful();
});

it('renders the equipahosp division with the logo and an sr-only title', function () {
    $this->get('/solucoes/equipahosp')
        ->assertSuccessful()
        ->assertSee('<h1 class="sr-only">EquipaHosp</h1>', false)
        ->assertSee('<svg', false)
        ->assertDontSee('rounded-xl p-3 text-white', false);
});

it('does not render icons for division items in the solutions mega menu', function () {
    $html = view('livewire.institutional.header')->render();

    expect($html)->toContain('Tupan Care');
    expect($html)->not->toContain('<flux:icon name="sparkles"');
    expect($html)->not->toContain('<flux:icon name="wrench"');
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
        ->set('topic', 'Consultoria Técnica em Produtos')
        ->set('message', 'Precisamos de suporte para nossa equipe técnica.')
        ->call('submit')
        ->assertSet('successMessage', 'Mensagem enviada. Nossa equipe entrará em contato em breve.');

    Mail::assertSent(ContactFormMail::class, function (ContactFormMail $mail) {
        return $mail->name === 'Maria Oliveira'
            && $mail->email === 'maria@hospital.com'
            && $mail->topic === 'Consultoria Técnica em Produtos';
    });
});
