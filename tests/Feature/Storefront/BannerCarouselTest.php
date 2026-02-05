<?php

declare(strict_types = 1);

use App\Domain\Marketing\Models\Banner;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('does not render carousel when no displayable banners exist', function () {
    Banner::factory()->inactive()->create();

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertDontSee('bannerCarousel(');
});

it('renders only displayable banners', function () {
    Banner::factory()->valid()->create(['alt_text' => 'Banner Ativo']);
    Banner::factory()->inactive()->create(['alt_text' => 'Banner Inativo']);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('Banner Ativo');
    $response->assertDontSee('Banner Inativo');
});

it('renders external and internal links correctly', function () {
    Banner::factory()->valid()->create([
        'alt_text' => 'Banner Externo',
        'link'     => 'https://example.com',
    ]);

    Banner::factory()->valid()->create([
        'alt_text' => 'Banner Interno',
        'link'     => '/categoria/teste',
    ]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('href="https://example.com"', false);
    $response->assertSee('target="_blank"', false);
    $response->assertSee('rel="noopener noreferrer"', false);
    $response->assertSee('href="/categoria/teste"', false);
});

it('lazy loads banners after the first slide', function () {
    Banner::factory()->count(2)->valid()->create();

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('loading="lazy"', false);
});
