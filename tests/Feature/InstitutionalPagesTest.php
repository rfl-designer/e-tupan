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

it('renders the equipahosp page with hub content, partners and seo', function () {
    $this->get('/solucoes/equipahosp')
        ->assertSuccessful()
        ->assertSee('<title>EquipaHosp | Engenharia Clínica e Equipamentos Hospitalares</title>', false)
        ->assertSee('Engenharia clínica, venda, locação, manutenção, calibração e assistência técnica', false)
        ->assertSee('O que o hub conecta')
        ->assertSee('Calibração, conformidade e segurança operacional')
        ->assertSee('Bancos de sangue e unidades de hemoterapia')
        ->assertSee('Por que escolher a EquipaHosp')
        ->assertSee('Solicitar avaliação técnica')
        ->assertSee('Falar com um consultor')
        ->assertSee('Fale com a Tupan Saúde')
        ->assertSee('href="https://bluehealthglobal.com/" target="_blank" rel="noopener noreferrer"', false)
        ->assertSee('https://www.fresenius-kabi.com/br', false)
        ->assertSee('https://cerne.tec.br', false)
        ->assertSee('Conhecer parceiro')
        ->assertSee('data-origem="equipa-hosp"', false)
        ->assertSee('text=' . rawurlencode('Olá, gostaria de falar com a EquipaHosp sobre uma necessidade de equipamentos ou engenharia clínica.'), false);
});

it('renders the tupan care page with its own brand, partnership and seo', function () {
    $this->get('/solucoes/tupan-care')
        ->assertSuccessful()
        ->assertSee('<title>Tupan Care | Soluções em Curativos e Estomias</title>', false)
        ->assertSee('Uma operação Tupan Saúde')
        ->assertSee('Tupan Care + Gentell Casex')
        ->assertSee('Como atuamos')
        ->assertSee('Produtos para estomizados')
        ->assertSee('Instituições de longa permanência')
        ->assertSee('Solicitar contato')
        ->assertSee('Solicitar treinamento')
        ->assertSee('href="https://gentell.com.br/" target="_blank" rel="noopener noreferrer"', false)
        ->assertSee('https://loja.gentell.com.br/', false)
        ->assertSee('https://casex.lojaintegrada.com.br/ostomia', false)
        ->assertSee('data-origem="tupan-care"', false)
        ->assertSee('text=' . rawurlencode('Olá, gostaria de solicitar informações sobre treinamento, padronização ou soluções Gentell Casex pela Tupan Care.'), false);
});

it('redirects the legacy tupan care slug permanently', function () {
    $this->get('/solucoes/proprios')->assertMovedPermanently()->assertRedirect('/solucoes/tupan-care');
});

it('shows the solutions cards for equipahosp and tupan care on the home page', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('Conhecer a EquipaHosp')
        ->assertSee('Conhecer a Tupan Care')
        ->assertSee(route('solutions.show', 'tupan-care'), false);
});

it('shows the operation descriptions in the solutions menu', function () {
    $html = view('livewire.institutional.header')->render();

    expect($html)
        ->toContain('Engenharia Clínica, Equipamentos e Operações Técnicas')
        ->toContain('Curativos, Estomias, Padronização e Educação em Saúde');
});

it('sends contact email with origin and subject for the division forms', function (string $origin, string $topic, string $subject) {
    Mail::fake();

    config(['mail.from.address' => 'contato@tupan.test']);

    Livewire::test(ContactForm::class, ['origin' => $origin])
        ->assertSee('Cidade/Estado')
        ->assertSee($topic)
        ->set('name', 'Maria Oliveira')
        ->set('company', 'Hospital Santa Maria')
        ->set('role', 'Coordenadora de Enfermagem')
        ->set('location', 'Recife/PE')
        ->set('email', 'maria@hospital.com')
        ->set('phone', '(81) 99999-0000')
        ->set('topic', $topic)
        ->set('message', 'Precisamos de apoio da equipe.')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('successMessage', 'Mensagem enviada. Nossa equipe entrará em contato em breve.');

    Mail::assertSent(ContactFormMail::class, function (ContactFormMail $mail) use ($origin, $topic, $subject) {
        return $mail->origin === $origin
            && $mail->topic === $topic
            && $mail->role === 'Coordenadora de Enfermagem'
            && $mail->location === 'Recife/PE'
            && $mail->phone === '(81) 99999-0000'
            && $mail->envelope()->subject === "{$subject} - {$topic}";
    });
})->with([
    'equipahosp' => ['equipa-hosp', 'Locação de equipamento', 'EquipaHosp — Engenharia Clínica e Equipamentos'],
    'tupan care' => ['tupan-care', 'Padronização institucional', 'Tupan Care — Curativos, Estomias e Padronização'],
]);

it('rejects topics outside the configured list for the origin', function () {
    Livewire::test(ContactForm::class, ['origin' => 'tupan-care'])
        ->set('name', 'Maria Oliveira')
        ->set('email', 'maria@hospital.com')
        ->set('topic', 'Locação de equipamento')
        ->set('message', 'Mensagem')
        ->call('submit')
        ->assertHasErrors(['topic' => 'in']);
});

it('ignores unknown origins and falls back to the default form', function () {
    Livewire::test(ContactForm::class, ['origin' => 'desconhecida'])
        ->assertSet('origin', null)
        ->assertSet('topic', 'Consultoria Técnica em Produtos')
        ->assertDontSee('Cidade/Estado');
});
