<?php

declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use App\Domain\Marketing\Models\Banner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

describe('Banner Admin CRUD (US-01)', function () {
    beforeEach(function () {
        $this->admin = Admin::factory()->withTwoFactor()->create();
        Storage::fake('banners');
    });

    describe('index', function () {
        it('lists all banners', function () {
            Banner::factory()->count(3)->create();

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->get(route('admin.banners.index'));

            $response->assertOk()
                ->assertSee('Banners Promocionais')
                ->assertViewHas('banners');
        });

        it('requires admin authentication', function () {
            $response = $this->get(route('admin.banners.index'));

            $response->assertRedirect(route('admin.login'));
        });
    });

    describe('create', function () {
        it('shows create form with all required fields', function () {
            $response = actingAsAdminWith2FA($this, $this->admin)
                ->get(route('admin.banners.create'));

            $response->assertOk()
                ->assertSee('Novo Banner')
                ->assertSee('Titulo')
                ->assertSee('Imagem Desktop')
                ->assertSee('Imagem Mobile')
                ->assertSee('Link de Destino')
                ->assertSee('Texto Alternativo')
                ->assertSee('Banner ativo');
        });
    });

    describe('store', function () {
        it('creates banner with desktop image only', function () {
            $image = UploadedFile::fake()->image('banner.jpg', 1920, 500);

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.banners.store'), [
                    'title'         => 'Black Friday 2024',
                    'image_desktop' => $image,
                    'link'          => '/categoria/promocoes',
                    'alt_text'      => 'Promocao Black Friday ate 50% off',
                ]);

            $response->assertRedirect(route('admin.banners.index'))
                ->assertSessionHas('success');

            $banner = Banner::where('title', 'Black Friday 2024')->first();
            expect($banner)
                ->not->toBeNull()
                ->title->toBe('Black Friday 2024')
                ->link->toBe('/categoria/promocoes')
                ->alt_text->toBe('Promocao Black Friday ate 50% off')
                ->image_mobile->toBeNull()
                ->created_by->toBe($this->admin->id);
        });

        it('creates banner with both desktop and mobile images', function () {
            $desktopImage = UploadedFile::fake()->image('banner-desktop.jpg', 1920, 500);
            $mobileImage  = UploadedFile::fake()->image('banner-mobile.jpg', 768, 400);

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.banners.store'), [
                    'title'         => 'Promocao Verao',
                    'image_desktop' => $desktopImage,
                    'image_mobile'  => $mobileImage,
                    'link'          => 'https://example.com/promo',
                    'alt_text'      => 'Promocao de verao',
                ]);

            $response->assertRedirect(route('admin.banners.index'));

            $banner = Banner::where('title', 'Promocao Verao')->first();
            expect($banner)
                ->not->toBeNull()
                ->image_mobile->not->toBeNull();
        });

        it('creates banner without link', function () {
            $image = UploadedFile::fake()->image('banner.jpg', 1920, 500);

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.banners.store'), [
                    'title'         => 'Banner Informativo',
                    'image_desktop' => $image,
                ]);

            $response->assertRedirect(route('admin.banners.index'));

            $banner = Banner::where('title', 'Banner Informativo')->first();
            expect($banner)
                ->not->toBeNull()
                ->link->toBeNull();
        });

        it('accepts internal URL links', function () {
            $image = UploadedFile::fake()->image('banner.jpg', 1920, 500);

            actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.banners.store'), [
                    'title'         => 'Banner Interno',
                    'image_desktop' => $image,
                    'link'          => '/categoria/eletronicos',
                ]);

            $banner = Banner::where('title', 'Banner Interno')->first();
            expect($banner->link)->toBe('/categoria/eletronicos');
        });

        it('accepts external URL links', function () {
            $image = UploadedFile::fake()->image('banner.jpg', 1920, 500);

            actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.banners.store'), [
                    'title'         => 'Banner Externo',
                    'image_desktop' => $image,
                    'link'          => 'https://parceiro.com/promocao',
                ]);

            $banner = Banner::where('title', 'Banner Externo')->first();
            expect($banner->link)->toBe('https://parceiro.com/promocao');
        });

        it('validates required title field', function () {
            $image = UploadedFile::fake()->image('banner.jpg', 1920, 500);

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.banners.store'), [
                    'image_desktop' => $image,
                ]);

            $response->assertSessionHasErrors(['title']);
        });

        it('validates required desktop image', function () {
            $response = actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.banners.store'), [
                    'title' => 'Banner Sem Imagem',
                ]);

            $response->assertSessionHasErrors(['image_desktop']);
        });

        it('accepts jpg image format', function () {
            $image = UploadedFile::fake()->image('banner.jpg', 1920, 500);

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.banners.store'), [
                    'title'         => 'Banner JPG',
                    'image_desktop' => $image,
                ]);

            $response->assertRedirect(route('admin.banners.index'));
        });

        it('accepts png image format', function () {
            $image = UploadedFile::fake()->image('banner.png', 1920, 500);

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.banners.store'), [
                    'title'         => 'Banner PNG',
                    'image_desktop' => $image,
                ]);

            $response->assertRedirect(route('admin.banners.index'));
        });

        it('validates webp in allowed mimes', function () {
            // WebP validation is handled by the 'mimes:jpg,jpeg,png,webp' rule
            // The actual webp processing is tested in integration tests with real files
            $request = new \App\Domain\Marketing\Http\Requests\StoreBannerRequest();
            $rules   = $request->rules();

            expect($rules['image_desktop'])->toContain('mimes:jpg,jpeg,png,webp');
        });

        it('rejects image larger than 2MB', function () {
            $image = UploadedFile::fake()->create('banner.jpg', 3000, 'image/jpeg');

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.banners.store'), [
                    'title'         => 'Banner Grande',
                    'image_desktop' => $image,
                ]);

            $response->assertSessionHasErrors(['image_desktop']);
        });

        it('rejects invalid image format', function () {
            $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.banners.store'), [
                    'title'         => 'Banner PDF',
                    'image_desktop' => $file,
                ]);

            $response->assertSessionHasErrors(['image_desktop']);
        });

        it('rejects mobile image larger than 2MB', function () {
            $desktopImage = UploadedFile::fake()->image('desktop.jpg', 1920, 500);
            $mobileImage  = UploadedFile::fake()->create('mobile.jpg', 3000, 'image/jpeg');

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.banners.store'), [
                    'title'         => 'Banner Mobile Grande',
                    'image_desktop' => $desktopImage,
                    'image_mobile'  => $mobileImage,
                ]);

            $response->assertSessionHasErrors(['image_mobile']);
        });

        it('assigns position automatically', function () {
            $image = UploadedFile::fake()->image('banner.jpg', 1920, 500);

            actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.banners.store'), [
                    'title'         => 'Primeiro Banner',
                    'image_desktop' => $image,
                ]);

            actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.banners.store'), [
                    'title'         => 'Segundo Banner',
                    'image_desktop' => $image,
                ]);

            $first  = Banner::where('title', 'Primeiro Banner')->first();
            $second = Banner::where('title', 'Segundo Banner')->first();

            expect($first->position)->toBeLessThan($second->position);
        });

        it('logs activity on banner creation', function () {
            $image = UploadedFile::fake()->image('banner.jpg', 1920, 500);

            actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.banners.store'), [
                    'title'         => 'Banner Log Test',
                    'image_desktop' => $image,
                ]);

            $this->assertDatabaseHas('activity_logs', [
                'admin_id'     => $this->admin->id,
                'action'       => 'created',
                'subject_type' => Banner::class,
            ]);
        });
    });

    describe('model methods', function () {
        it('returns effective mobile image as desktop when mobile is null', function () {
            $banner = Banner::factory()->create([
                'image_desktop' => 'banners/desktop/large/test.webp',
                'image_mobile'  => null,
            ]);

            expect($banner->getEffectiveMobileImage())
                ->toBe('banners/desktop/large/test.webp');
        });

        it('returns mobile image when available', function () {
            $banner = Banner::factory()->withMobileImage()->create([
                'image_desktop' => 'banners/desktop/large/desktop.webp',
                'image_mobile'  => 'banners/mobile/large/mobile.webp',
            ]);

            expect($banner->getEffectiveMobileImage())
                ->toBe('banners/mobile/large/mobile.webp');
        });

        it('identifies external links correctly', function () {
            $externalBanner = Banner::factory()->withExternalLink('https://example.com')->create();
            $internalBanner = Banner::factory()->withInternalLink('/categoria/teste')->create();
            $noLinkBanner   = Banner::factory()->withoutLink()->create();

            expect($externalBanner->isExternalLink())->toBeTrue();
            expect($internalBanner->isExternalLink())->toBeFalse();
            expect($noLinkBanner->isExternalLink())->toBeFalse();
        });
    });
});

describe('Banner Admin Management (US-03/US-04/US-05/US-06)', function () {
    beforeEach(function () {
        $this->admin = Admin::factory()->withTwoFactor()->create();
        Storage::fake('banners');
    });

    it('toggles banner active status', function () {
        $banner = Banner::factory()->active()->create();

        $response = actingAsAdminWith2FA($this, $this->admin)
            ->patch(route('admin.banners.toggle-active', $banner));

        $response->assertRedirect();

        expect($banner->refresh()->is_active)->toBeFalse();
    });

    it('duplicates a banner with images', function () {
        $image = UploadedFile::fake()->image('banner.jpg', 1920, 500);

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.banners.store'), [
                'title'         => 'Banner Original',
                'image_desktop' => $image,
                'alt_text'      => 'Banner Original',
            ]);

        $banner = Banner::where('title', 'Banner Original')->firstOrFail();

        $response = actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.banners.duplicate', $banner));

        $response->assertRedirect();

        $duplicated = Banner::where('title', 'Banner Original (Cópia)')->first();

        expect($duplicated)->not->toBeNull()
            ->and($duplicated->is_active)->toBeFalse()
            ->and($duplicated->image_desktop)->not->toBe($banner->image_desktop);
    });

    it('reorders banners via drag-and-drop payload', function () {
        $first  = Banner::factory()->atPosition(1)->create(['title' => 'Primeiro']);
        $second = Banner::factory()->atPosition(2)->create(['title' => 'Segundo']);
        $third  = Banner::factory()->atPosition(3)->create(['title' => 'Terceiro']);

        $response = actingAsAdminWith2FA($this, $this->admin)
            ->patch(route('admin.banners.reorder'), [
                'order' => [$third->id, $first->id, $second->id],
            ]);

        $response->assertRedirect();

        expect($third->refresh()->position)->toBe(1);
        expect($first->refresh()->position)->toBe(2);
        expect($second->refresh()->position)->toBe(3);
    });

    it('filters banners by status', function () {
        Banner::factory()->valid()->create(['title' => 'Banner Ativo']);
        Banner::factory()->inactive()->create(['title' => 'Banner Inativo']);
        Banner::factory()->scheduled()->create(['title' => 'Banner Agendado']);
        Banner::factory()->expired()->create(['title' => 'Banner Expirado']);

        $activeResponse = actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.banners.index', ['status' => 'active']));

        $activeResponse->assertOk()
            ->assertSee('Banner Ativo')
            ->assertDontSee('Banner Inativo');

        $inactiveResponse = actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.banners.index', ['status' => 'inactive']));

        $inactiveResponse->assertOk()
            ->assertSee('Banner Inativo')
            ->assertDontSee('Banner Ativo');
    });

    it('filters banners by search query', function () {
        Banner::factory()->create(['title' => 'Campanha Verao']);
        Banner::factory()->create(['title' => 'Campanha Inverno']);

        $response = actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.banners.index', ['search' => 'Verao']));

        $response->assertOk()
            ->assertSee('Campanha Verao')
            ->assertDontSee('Campanha Inverno');
    });
});

describe('Banner Display Period (US-02)', function () {
    beforeEach(function () {
        $this->admin = Admin::factory()->withTwoFactor()->create();
        Storage::fake('banners');
    });

    describe('create form', function () {
        it('shows period fields in create form', function () {
            $response = actingAsAdminWith2FA($this, $this->admin)
                ->get(route('admin.banners.create'));

            $response->assertOk()
                ->assertSee('Periodo de Exibicao')
                ->assertSee('Data de Inicio')
                ->assertSee('Data de Fim');
        });
    });

    describe('store with dates', function () {
        it('creates banner with start and end dates', function () {
            $image    = UploadedFile::fake()->image('banner.jpg', 1920, 500);
            $startsAt = now()->addDay()->format('Y-m-d\TH:i');
            $endsAt   = now()->addMonth()->format('Y-m-d\TH:i');

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.banners.store'), [
                    'title'         => 'Banner Agendado',
                    'image_desktop' => $image,
                    'starts_at'     => $startsAt,
                    'ends_at'       => $endsAt,
                ]);

            $response->assertRedirect(route('admin.banners.index'));

            $banner = Banner::where('title', 'Banner Agendado')->first();
            expect($banner)
                ->not->toBeNull()
                ->starts_at->not->toBeNull()
                ->ends_at->not->toBeNull();
        });

        it('creates banner without start date (displays immediately)', function () {
            $image  = UploadedFile::fake()->image('banner.jpg', 1920, 500);
            $endsAt = now()->addMonth()->format('Y-m-d\TH:i');

            actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.banners.store'), [
                    'title'         => 'Banner Imediato',
                    'image_desktop' => $image,
                    'ends_at'       => $endsAt,
                ]);

            $banner = Banner::where('title', 'Banner Imediato')->first();
            expect($banner)
                ->starts_at->toBeNull()
                ->ends_at->not->toBeNull();
        });

        it('creates banner without end date (displays indefinitely)', function () {
            $image    = UploadedFile::fake()->image('banner.jpg', 1920, 500);
            $startsAt = now()->subDay()->format('Y-m-d\TH:i');

            actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.banners.store'), [
                    'title'         => 'Banner Indefinido',
                    'image_desktop' => $image,
                    'starts_at'     => $startsAt,
                ]);

            $banner = Banner::where('title', 'Banner Indefinido')->first();
            expect($banner)
                ->starts_at->not->toBeNull()
                ->ends_at->toBeNull();
        });

        it('validates end date is after or equal to start date', function () {
            $image    = UploadedFile::fake()->image('banner.jpg', 1920, 500);
            $startsAt = now()->addMonth()->format('Y-m-d\TH:i');
            $endsAt   = now()->format('Y-m-d\TH:i'); // Before starts_at

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.banners.store'), [
                    'title'         => 'Banner Invalido',
                    'image_desktop' => $image,
                    'starts_at'     => $startsAt,
                    'ends_at'       => $endsAt,
                ]);

            $response->assertSessionHasErrors(['ends_at']);
        });
    });

    describe('period status methods', function () {
        it('returns scheduled status when starts_at is in the future', function () {
            $banner = Banner::factory()->scheduled()->create();

            expect($banner->isScheduled())->toBeTrue();
            expect($banner->isExpired())->toBeFalse();
            expect($banner->getPeriodStatus())->toBe('scheduled');
            expect($banner->getPeriodStatusLabel())->toBe('Agendado');
            expect($banner->getPeriodStatusColor())->toBe('yellow');
        });

        it('returns active status when within period', function () {
            $banner = Banner::factory()->valid()->create();

            expect($banner->isScheduled())->toBeFalse();
            expect($banner->isExpired())->toBeFalse();
            expect($banner->getPeriodStatus())->toBe('active');
            expect($banner->getPeriodStatusLabel())->toBe('Ativo');
            expect($banner->getPeriodStatusColor())->toBe('green');
        });

        it('returns expired status when ends_at is in the past', function () {
            $banner = Banner::factory()->expired()->create();

            expect($banner->isScheduled())->toBeFalse();
            expect($banner->isExpired())->toBeTrue();
            expect($banner->getPeriodStatus())->toBe('expired');
            expect($banner->getPeriodStatusLabel())->toBe('Expirado');
            expect($banner->getPeriodStatusColor())->toBe('red');
        });

        it('returns active status when no dates are set', function () {
            $banner = Banner::factory()->create([
                'starts_at' => null,
                'ends_at'   => null,
            ]);

            expect($banner->isScheduled())->toBeFalse();
            expect($banner->isExpired())->toBeFalse();
            expect($banner->getPeriodStatus())->toBe('active');
        });

        it('returns active status when only end date is set and not expired', function () {
            $banner = Banner::factory()->create([
                'starts_at' => null,
                'ends_at'   => now()->addMonth(),
            ]);

            expect($banner->getPeriodStatus())->toBe('active');
        });
    });

    describe('within period check', function () {
        it('is within period when no dates are set', function () {
            $banner = Banner::factory()->create([
                'starts_at' => null,
                'ends_at'   => null,
            ]);

            expect($banner->isWithinPeriod())->toBeTrue();
        });

        it('is within period when starts_at is in the past and ends_at is in the future', function () {
            $banner = Banner::factory()->valid()->create();

            expect($banner->isWithinPeriod())->toBeTrue();
        });

        it('is not within period when starts_at is in the future', function () {
            $banner = Banner::factory()->scheduled()->create();

            expect($banner->isWithinPeriod())->toBeFalse();
        });

        it('is not within period when ends_at is in the past', function () {
            $banner = Banner::factory()->expired()->create();

            expect($banner->isWithinPeriod())->toBeFalse();
        });
    });

    describe('displayable scope', function () {
        it('excludes banners outside display period', function () {
            Banner::factory()->valid()->create(['title' => 'Active']);
            Banner::factory()->scheduled()->create(['title' => 'Scheduled']);
            Banner::factory()->expired()->create(['title' => 'Expired']);

            $displayable = Banner::displayable()->pluck('title')->toArray();

            expect($displayable)->toContain('Active');
            expect($displayable)->not->toContain('Scheduled');
            expect($displayable)->not->toContain('Expired');
        });

        it('excludes inactive banners even within period', function () {
            Banner::factory()->valid()->create(['title' => 'Active Valid']);
            Banner::factory()->valid()->inactive()->create(['title' => 'Inactive Valid']);

            $displayable = Banner::displayable()->pluck('title')->toArray();

            expect($displayable)->toContain('Active Valid');
            expect($displayable)->not->toContain('Inactive Valid');
        });
    });

    describe('index displays period information', function () {
        it('shows period dates in listing', function () {
            $startsAt = now()->subDay();
            $endsAt   = now()->addMonth();

            Banner::factory()->create([
                'title'     => 'Banner com Periodo',
                'starts_at' => $startsAt,
                'ends_at'   => $endsAt,
            ]);

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->get(route('admin.banners.index'));

            $response->assertOk()
                ->assertSee('Banner com Periodo')
                ->assertSee($startsAt->format('d/m/Y H:i'))
                ->assertSee($endsAt->format('d/m/Y H:i'));
        });

        it('shows correct status badge for scheduled banner', function () {
            Banner::factory()->scheduled()->create(['title' => 'Banner Agendado']);

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->get(route('admin.banners.index'));

            $response->assertOk()
                ->assertSee('Banner Agendado')
                ->assertSee('Agendado');
        });

        it('shows correct status badge for expired banner', function () {
            Banner::factory()->expired()->create(['title' => 'Banner Expirado']);

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->get(route('admin.banners.index'));

            $response->assertOk()
                ->assertSee('Banner Expirado')
                ->assertSee('Expirado');
        });

        it('shows correct status badge for active banner', function () {
            Banner::factory()->valid()->create(['title' => 'Banner Ativo']);

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->get(route('admin.banners.index'));

            $response->assertOk()
                ->assertSee('Banner Ativo')
                ->assertSee('Ativo');
        });
    });
});
