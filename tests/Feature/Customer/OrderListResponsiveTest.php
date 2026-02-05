<?php

declare(strict_types = 1);

use App\Domain\Checkout\Models\Order;
use App\Domain\Customer\Livewire\OrderList;
use App\Models\User;
use Livewire\Livewire;

describe('US-07: Experiencia responsiva na listagem de pedidos', function () {
    describe('Pagina otimizada para diferentes dispositivos', function () {
        it('has responsive container with proper padding', function () {
            $user = User::factory()->create();

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSeeHtml('px-4')
                ->assertSeeHtml('sm:px-6')
                ->assertSeeHtml('lg:px-8');
        });

        it('has responsive layout for order cards', function () {
            $user = User::factory()->create();
            Order::factory()->create(['user_id' => $user->id]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSeeHtml('flex-col')
                ->assertSeeHtml('sm:flex-row');
        });

        it('has responsive padding on order cards', function () {
            $user = User::factory()->create();
            Order::factory()->create(['user_id' => $user->id]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSeeHtml('p-4')
                ->assertSeeHtml('sm:p-6');
        });
    });

    describe('Informacoes reorganizadas em formato de card no mobile', function () {
        it('displays order info in column layout on mobile', function () {
            $user = User::factory()->create();
            Order::factory()->create(['user_id' => $user->id]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSeeHtml('flex-col gap-4 sm:flex-row');
        });

        it('shows total with proper alignment on mobile', function () {
            $user = User::factory()->create();
            Order::factory()->create(['user_id' => $user->id]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSeeHtml('sm:flex-col sm:items-end');
        });
    });

    describe('Filtros acessiveis em mobile', function () {
        it('has horizontal scroll container for filters on mobile', function () {
            $user = User::factory()->create();

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSeeHtml('overflow-x-auto')
                ->assertSeeHtml('scrollbar-hide');
        });

        it('displays all filter options', function () {
            $user = User::factory()->create();

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSee('Todos')
                ->assertSee('Pendentes')
                ->assertSee('Em Andamento')
                ->assertSee('Enviados')
                ->assertSee('Entregues')
                ->assertSee('Cancelados');
        });

        it('has touch-friendly filter buttons', function () {
            $user = User::factory()->create();

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSeeHtml('px-4 py-2');
        });
    });

    describe('Paginacao facil de usar em telas pequenas', function () {
        it('displays pagination when there are multiple pages', function () {
            $user = User::factory()->create();

            // Create 15 orders to trigger pagination (10 per page)
            Order::factory()->count(15)->create(['user_id' => $user->id]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSeeHtml('nav');
        });

        it('does not show pagination with few orders', function () {
            $user = User::factory()->create();

            Order::factory()->count(5)->create(['user_id' => $user->id]);

            $component = Livewire::actingAs($user)
                ->test(OrderList::class);

            // With only 5 orders, pagination should not be needed
            $orders = $component->viewData('orders');
            expect($orders->hasPages())->toBeFalse();
        });
    });

    describe('Loading states apropriados', function () {
        it('has loading indicator for search', function () {
            $user = User::factory()->create();

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSeeHtml('wire:loading');
        });

        it('has loading overlay for list', function () {
            $user = User::factory()->create();

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSeeHtml('wire:loading.class');
        });

        it('shows skeleton loader during initial load', function () {
            $user = User::factory()->create();

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSeeHtml('wire:loading.flex');
        });
    });

    describe('Acessibilidade e UX', function () {
        it('has proper focus states on interactive elements', function () {
            $user = User::factory()->create();

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSeeHtml('focus:');
        });

        it('has hover states on cards', function () {
            $user = User::factory()->create();
            Order::factory()->create(['user_id' => $user->id]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSeeHtml('hover:shadow-md');
        });

        it('uses wire:navigate for smooth navigation', function () {
            $user = User::factory()->create();
            Order::factory()->create(['user_id' => $user->id]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSeeHtml('wire:navigate');
        });
    });
});
