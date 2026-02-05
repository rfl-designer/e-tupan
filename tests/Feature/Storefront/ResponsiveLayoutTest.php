<?php

declare(strict_types = 1);

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\{Category, Product};
use App\Domain\Marketing\Models\Banner;
use App\Livewire\Storefront\Homepage;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Header Responsiveness', function () {
    test('header has mobile menu button visible on small screens', function () {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('lg:hidden');
        $response->assertSee('open-mobile-menu');
    });

    test('header has desktop navigation hidden on mobile', function () {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('hidden lg:flex');
    });

    test('header mobile menu has slide transition', function () {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('translate-x-full');
        $response->assertSee('translate-x-0');
    });
});

describe('Footer Responsiveness', function () {
    test('footer has responsive grid layout', function () {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('grid-cols-2');
        $response->assertSee('md:grid-cols-4');
    });

    test('footer bottom bar has flex responsive layout', function () {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('flex-col');
        $response->assertSee('sm:flex-row');
    });
});

describe('Banner Carousel Responsiveness', function () {
    test('banner carousel has responsive aspect ratio', function () {
        Banner::factory()->valid()->create();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('aspect-[16/9]');
        $response->assertSee('sm:aspect-[2/1]');
        $response->assertSee('lg:aspect-[192/50]');
    });

    test('banner carousel has max height on desktop', function () {
        Banner::factory()->valid()->create();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('lg:max-h-[500px]');
    });
});

describe('Homepage Sections Responsiveness', function () {
    test('categories grid has responsive columns', function () {
        Category::factory()->count(3)->create([
            'is_active' => true,
            'parent_id' => null,
        ]);

        $response = Livewire::test(Homepage::class);

        $response->assertSee('grid-cols-2');
        $response->assertSee('sm:grid-cols-3');
        $response->assertSee('md:grid-cols-4');
        $response->assertSee('lg:grid-cols-6');
    });

    test('products grid has responsive columns', function () {
        Product::factory()->count(3)->create([
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(Homepage::class);

        $response->assertSee('grid-cols-2');
        $response->assertSee('sm:grid-cols-3');
        $response->assertSee('lg:grid-cols-4');
    });

    test('newsletter form has responsive layout', function () {
        $response = Livewire::test(Homepage::class);

        $response->assertSee('flex-col');
        $response->assertSee('sm:flex-row');
    });

    test('features section has responsive grid', function () {
        $response = Livewire::test(Homepage::class);

        $response->assertSee('grid-cols-2');
        $response->assertSee('md:grid-cols-4');
    });

    test('sections have responsive padding', function () {
        $response = Livewire::test(Homepage::class);

        $response->assertSee('px-4');
        $response->assertSee('sm:px-6');
        $response->assertSee('lg:px-8');
    });
});

describe('Product Card Responsiveness', function () {
    test('product card displays correctly with image', function () {
        $product = Product::factory()->create([
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(Homepage::class);

        $response->assertSee($product->name);
    });

    test('product card has hover effects', function () {
        $product = Product::factory()
            ->hasImages(1)
            ->create([
                'status' => ProductStatus::Active,
            ]);

        $response = Livewire::test(Homepage::class);

        $response->assertSee('group-hover:scale-105', false);
    });
});

describe('Category Card Responsiveness', function () {
    test('category card has correct aspect ratio', function () {
        Category::factory()->create([
            'is_active' => true,
            'parent_id' => null,
        ]);

        $response = $this->get(route('home'));

        $response->assertSee('aspect-[4/3]', false);
    });

    test('category card has hover effects', function () {
        Category::factory()->create([
            'is_active' => true,
            'parent_id' => null,
            'image'     => 'categories/test.jpg',
        ]);

        $response = Livewire::test(Homepage::class);

        $response->assertSee('group-hover:scale-105', false);
    });
});

describe('Layout Structure', function () {
    test('layout has correct structure with header main footer', function () {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('<header', false);
        $response->assertSee('<main', false);
        $response->assertSee('<footer', false);
    });

    test('layout has sticky header', function () {
        $response = $this->get(route('home'));

        $response->assertSee('sticky top-0');
    });

    test('layout has min height screen', function () {
        $response = $this->get(route('home'));

        $response->assertSee('min-h-screen');
    });
});

describe('Dark Mode Support', function () {
    test('header supports dark mode', function () {
        $response = $this->get(route('home'));

        $response->assertSee('dark:bg-zinc-900');
        $response->assertSee('dark:border-zinc-700');
    });

    test('footer supports dark mode', function () {
        $response = $this->get(route('home'));

        $response->assertSee('dark:bg-zinc-900');
        $response->assertSee('dark:text-white');
    });

    test('homepage sections support dark mode', function () {
        $response = Livewire::test(Homepage::class);

        $response->assertSee('dark:text-white');
        $response->assertSee('dark:text-zinc-400');
    });
});

describe('Max Width Container', function () {
    test('sections use max-w-7xl container', function () {
        $response = Livewire::test(Homepage::class);

        $response->assertSee('max-w-7xl');
    });

    test('header uses max-w-7xl container', function () {
        $response = $this->get(route('home'));

        $response->assertSee('max-w-7xl');
    });

    test('footer uses max-w-7xl container', function () {
        $response = $this->get(route('home'));

        $response->assertSee('max-w-7xl');
    });
});

describe('Touch Friendly Elements', function () {
    test('buttons have adequate touch targets', function () {
        $response = $this->get(route('home'));

        $response->assertSee('size-9');
        $response->assertSee('sm:size-10');
        $response->assertSee('h-14');
        $response->assertSee('sm:h-16');
    });

    test('mobile menu has full width navigation', function () {
        $response = $this->get(route('home'));

        $response->assertSee('w-full max-w-sm');
    });

    test('mobile menu items have adequate touch targets', function () {
        Category::factory()->create([
            'is_active' => true,
            'parent_id' => null,
        ]);

        $response = $this->get(route('home'));

        $response->assertSee('py-3');
    });
});
