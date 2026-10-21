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
        ->set('topic', 'Consultoria técnica em produtos')
        ->set('message', 'Precisamos de suporte para nossa equipe técnica.')
        ->call('submit')
        ->assertSet('successMessage', 'Mensagem enviada. Nossa equipe entrará em contato em breve.');

    Mail::assertSent(ContactFormMail::class, function (ContactFormMail $mail) {
        return $mail->name === 'Maria Oliveira'
            && $mail->email === 'maria@hospital.com'
            && $mail->topic === 'Consultoria técnica em produtos';
    });
});

it('renders the equipahosp page with hub content, partners and seo', function () {
    config(['institutional.contact.whatsapp' => 'https://wa.me/5500000000000']);

    $this->get('/solucoes/equipahosp')
        ->assertSuccessful()
        ->assertSee('<title>EquipaHosp | Engenharia Clínica e Equipamentos Hospitalares</title>', false)
        ->assertSee('Engenharia clínica, venda, locação, manutenção, calibração e assistência técnica', false)
        ->assertSee('O que a EquipaHosp oferece')
        ->assertSee('Calibração, conformidade e segurança operacional')
        ->assertSee('Bancos de sangue e unidades de hemoterapia')
        ->assertSee('Por que escolher a EquipaHosp')
        ->assertSee('Falar com um consultor')
        ->assertSee('Falar pelo WhatsApp')
        ->assertSee('Fale com a equipe EquipaHosp')
        ->assertSee('href="https://bluehealthglobal.com/" target="_blank" rel="noopener noreferrer"', false)
        ->assertSee('https://www.fresenius-kabi.com/br', false)
        ->assertSee('images/parceiros/blue-health.webp', false)
        ->assertSee('images/parceiros/fresenius-kabi.webp', false)
        ->assertSee('aria-label="Logo Blue Health"', false)
        ->assertSee('https://cerne.tec.br', false)
        ->assertSee('Conhecer parceiro')
        ->assertSee('data-origem="equipa-hosp"', false)
        ->assertSee('text=' . rawurlencode('Olá, gostaria de falar com a EquipaHosp sobre uma necessidade de equipamentos ou engenharia clínica.'), false);
});

it('renders the tupan care page with its own brand, partnership and seo', function () {
    config(['institutional.contact.whatsapp' => 'https://wa.me/5500000000000']);

    $this->get('/solucoes/tupan-care')
        ->assertSuccessful()
        ->assertSee('<title>Tupan Care | Soluções em Curativos e Estomias</title>', false)
        ->assertSee('Uma operação Tupan Saúde')
        ->assertSee('Cuidado de feridas e estomias com apoio técnico local')
        ->assertSee('alt="Logo Tupan Care"', false)
        ->assertSee('images/parceiros/tupan-care-wordmark.webp', false)
        ->assertSee('images/parceiros/tupan-care-wordmark.webp', false)
        ->assertSee('images/parceiros/gentell-v4.webp', false)
        ->assertSee('images/parceiros/casex.webp', false)
        ->assertSee('aria-label="Logos Gentell Casex"', false)
        ->assertSee('Tupan Care + Gentell Casex')
        ->assertSee('Como atuamos')
        ->assertSee('Produtos para estomizados')
        ->assertSee('Instituições de longa permanência')
        ->assertSee('Falar com um consultor')
        ->assertSee('Solicitar treinamento')
        ->assertSee('href="https://gentell.com.br/" target="_blank" rel="noopener noreferrer"', false)
        ->assertSee('https://loja.gentell.com.br/', false)
        ->assertSee('https://casex.lojaintegrada.com.br/ostomia', false)
        ->assertSee('data-origem="tupan-care"', false)
        ->assertSee('text=' . rawurlencode('Olá, gostaria de solicitar informações sobre treinamento, padronização ou soluções Gentell Casex pela Tupan Care.'), false);
});

it('hides the whatsapp links on the special pages while no number is configured', function (string $path) {
    config(['institutional.contact.whatsapp' => 'https://wa.me/']);

    $this->get($path)
        ->assertSuccessful()
        ->assertDontSee('https://wa.me/?', false)
        ->assertSee('href="#fale-conosco"', false);
})->with(['/solucoes/tupan-care', '/solucoes/equipahosp']);

it('renders the central phone, address and instagram in the contact section', function (string $path) {
    $this->get($path)
        ->assertSuccessful()
        ->assertSeeInOrder(['Central de atendimento', 'href="tel:+5581996840060"', 'Segunda a sexta, 8h às 18h'], false)
        ->assertSeeTextInOrder(['Sede TUPAN', 'Estrada dos Remédios, 2123 (Galeria Beatriz Barbosa), Madalena, CEP 50720-715', 'Atuação em todo o Nordeste'])
        ->assertSee('href="https://www.instagram.com/tupansaude/" target="_blank"', false)
        ->assertSee('@tupansaude');
})->with(['/', '/solucoes/cirurgica']);

it('renders the own phone and instagram of tupan care in its contact section', function () {
    $this->get('/solucoes/tupan-care')
        ->assertSuccessful()
        ->assertSee('Atendimento Tupan Care')
        ->assertSee('href="tel:+5581998717695"', false)
        ->assertSee('href="https://instagram.com/tupancare"', false)
        ->assertSee('@tupancare')
        ->assertDontSee('@tupansaude');
});

it('falls back to the central phone on equipahosp while it has no phone of its own', function () {
    $this->get('/solucoes/equipahosp')
        ->assertSuccessful()
        ->assertSeeInOrder(['Central de atendimento', 'href="tel:+5581996840060"', 'Segunda a sexta, 8h às 18h'], false)
        ->assertDontSee('Atendimento EquipaHosp')
        ->assertSee('href="https://instagram.com/equipahosp"', false)
        ->assertSee('@equipahosp')
        ->assertDontSee('@tupansaude');
});

it('shows the phone, address and social links in the footer', function () {
    $this->get('/sobre')
        ->assertSuccessful()
        ->assertSee('href="tel:+5581996840060"', false)
        ->assertSeeText('Estrada dos Remédios, 2123 (Galeria Beatriz Barbosa), Madalena, CEP 50720-715')
        ->assertSee('href="https://www.instagram.com/tupansaude/"', false)
        ->assertSee('href="https://www.linkedin.com/company/tupansaude/"', false)
        ->assertDontSee('aria-label="Facebook"', false);
});

it('links the whatsapp buttons to the central number', function () {
    $this->get('/solucoes/cirurgica')
        ->assertSuccessful()
        ->assertSee('href="https://wa.me/5581996840060', false)
        ->assertSee('Falar pelo WhatsApp');
});

it('does not show fill-in placeholders on the public pages', function (string $path) {
    $this->get($path)
        ->assertSuccessful()
        ->assertDontSee('[PREENCHER');
})->with([
    '/',
    '/sobre',
    '/contato',
    '/blog',
    '/blog/engenharia-clinica-seguranca',
    '/solucoes/cirurgica',
    '/solucoes/tupan-care',
    '/solucoes/equipahosp',
]);

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
        ->assertSet('topic', 'Consultoria técnica em produtos')
        ->assertDontSee('Cidade/Estado');
});

it('preselects the topic sent by the contact-topic event', function () {
    Livewire::test(ContactForm::class, ['origin' => 'tupan-care'])
        ->dispatch('contact-topic', topic: 'Treinamento')
        ->assertSet('topic', 'Treinamento');
});

it('keeps the current topic when the contact-topic event sends one outside the configured list', function () {
    Livewire::test(ContactForm::class, ['origin' => 'tupan-care'])
        ->dispatch('contact-topic', topic: 'Locação de equipamento')
        ->assertSet('topic', 'Compra de produtos');
});
