<?php

declare(strict_types=1);

use App\Domain\Admin\Services\SettingsService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->settingsService = app(SettingsService::class);
    $this->settingsService->saveGroup('general', [
        'store_name' => 'Minha Loja Teste',
        'store_email' => 'contato@minhaloja.com',
        'store_phone' => '(11) 99999-9999',
        'store_address' => 'Rua das Flores, 123 - Sao Paulo, SP',
    ]);
});

describe('Email Layout Component', function () {
    it('renders the layout with header containing store logo area', function () {
        $html = Blade::render('<x-emails.layout><p>Conteudo</p></x-emails.layout>');

        expect($html)->toContain('Minha Loja Teste');
    });

    it('renders the footer with store name', function () {
        $html = Blade::render('<x-emails.layout><p>Conteudo</p></x-emails.layout>');

        expect($html)->toContain('Minha Loja Teste');
    });

    it('renders the footer with store address', function () {
        $html = Blade::render('<x-emails.layout><p>Conteudo</p></x-emails.layout>');

        expect($html)->toContain('Rua das Flores, 123 - Sao Paulo, SP');
    });

    it('renders the footer with store phone', function () {
        $html = Blade::render('<x-emails.layout><p>Conteudo</p></x-emails.layout>');

        expect($html)->toContain('(11) 99999-9999');
    });

    it('renders the footer with store email', function () {
        $html = Blade::render('<x-emails.layout><p>Conteudo</p></x-emails.layout>');

        expect($html)->toContain('contato@minhaloja.com');
    });

    it('renders the footer with link to store', function () {
        $html = Blade::render('<x-emails.layout><p>Conteudo</p></x-emails.layout>');

        expect($html)->toContain(config('app.url'));
    });

    it('renders content inside the layout', function () {
        $html = Blade::render('<x-emails.layout><p>Meu conteudo especial</p></x-emails.layout>');

        expect($html)->toContain('Meu conteudo especial');
    });

    it('uses inline CSS for email compatibility', function () {
        $html = Blade::render('<x-emails.layout><p>Conteudo</p></x-emails.layout>');

        expect($html)->toContain('style=');
    });

    it('renders the preheader text when provided', function () {
        $html = Blade::render('<x-emails.layout preheader="Visualizar no navegador"><p>Conteudo</p></x-emails.layout>');

        expect($html)->toContain('Visualizar no navegador');
    });

    it('hides the preheader from the visible content', function () {
        $html = Blade::render('<x-emails.layout preheader="Texto oculto preheader"><p>Conteudo</p></x-emails.layout>');

        // Preheader should be in a hidden span
        expect($html)->toContain('display:none')
            ->and($html)->toContain('Texto oculto preheader');
    });

    it('has responsive max-width container', function () {
        $html = Blade::render('<x-emails.layout><p>Conteudo</p></x-emails.layout>');

        expect($html)->toContain('max-width');
    });

    it('includes the year in the footer copyright', function () {
        $html = Blade::render('<x-emails.layout><p>Conteudo</p></x-emails.layout>');

        expect($html)->toContain(date('Y'));
    });
});

describe('Email Layout with Primary Color', function () {
    beforeEach(function () {
        $this->settingsService->saveGroup('general', [
            'store_name' => 'Loja Colorida',
            'store_email' => 'contato@lojacolorida.com',
            'store_phone' => '(11) 88888-8888',
            'store_address' => 'Avenida Central, 456',
            'primary_color' => '#FF5733',
        ]);
    });

    it('applies primary color when configured', function () {
        $html = Blade::render('<x-emails.layout><p>Conteudo</p></x-emails.layout>');

        expect($html)->toContain('#FF5733');
    });

    it('uses default color when primary color is not configured', function () {
        $this->settingsService->set('general.primary_color', '');

        $html = Blade::render('<x-emails.layout><p>Conteudo</p></x-emails.layout>');

        // Should use default emerald/green color
        expect($html)->toContain('#059669');
    });
});

describe('Email Layout with Logo', function () {
    it('renders logo image when store logo is configured', function () {
        Storage::disk('public')->put('settings/logo.png', 'fake-image-content');
        $this->settingsService->set('general.store_logo', 'settings/logo.png');

        $html = Blade::render('<x-emails.layout><p>Conteudo</p></x-emails.layout>');

        expect($html)->toContain('settings/logo.png');
    });

    it('renders store name as text when no logo is configured', function () {
        $this->settingsService->set('general.store_logo', '');

        $html = Blade::render('<x-emails.layout><p>Conteudo</p></x-emails.layout>');

        // Should show store name in header even without logo
        expect($html)->toContain('Minha Loja Teste');
    });
});

describe('Email Layout Subject Slot', function () {
    it('renders the subject slot in the header when provided', function () {
        $html = Blade::render('<x-emails.layout subject="Assunto do Email"><p>Conteudo</p></x-emails.layout>');

        expect($html)->toContain('Assunto do Email');
    });
});
