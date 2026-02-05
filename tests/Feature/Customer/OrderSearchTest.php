<?php

declare(strict_types = 1);

use App\Domain\Checkout\Enums\OrderStatus;
use App\Domain\Checkout\Models\Order;
use App\Domain\Customer\Livewire\OrderList;
use App\Models\User;
use Livewire\Livewire;

describe('US-04: Buscar pedidos por numero', function () {
    describe('Campo de busca por numero do pedido', function () {
        it('displays search input field', function () {
            $user = User::factory()->create();

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSeeHtml('wire:model.live.debounce.300ms="search"');
        });

        it('has search property', function () {
            $user = User::factory()->create();

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSet('search', '');
        });
    });

    describe('Busca case-insensitive', function () {
        it('finds order by exact number', function () {
            $user = User::factory()->create();

            $order = Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-123456',
            ]);

            $otherOrder = Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-999999',
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->set('search', 'ORD-123456')
                ->assertSee('ORD-123456')
                ->assertDontSee('ORD-999999');
        });

        it('finds order by partial number', function () {
            $user = User::factory()->create();

            $order = Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-123456',
            ]);

            $otherOrder = Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-999999',
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->set('search', '123')
                ->assertSee('ORD-123456')
                ->assertDontSee('ORD-999999');
        });

        it('finds order with lowercase search', function () {
            $user = User::factory()->create();

            $order = Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-ABCDEF',
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->set('search', 'ord-abcdef')
                ->assertSee('ORD-ABCDEF');
        });

        it('finds order with uppercase search', function () {
            $user = User::factory()->create();

            $order = Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ord-abcdef',
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->set('search', 'ORD-ABCDEF')
                ->assertSee('ord-abcdef');
        });

        it('finds order with mixed case search', function () {
            $user = User::factory()->create();

            $order = Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-AbCdEf',
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->set('search', 'oRd-aBcDeF')
                ->assertSee('ORD-AbCdEf');
        });
    });

    describe('Busca atualiza lista em tempo real', function () {
        it('uses debounce of 300ms on search input', function () {
            $user = User::factory()->create();

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSeeHtml('wire:model.live.debounce.300ms="search"');
        });

        it('updates list reactively when search changes', function () {
            $user = User::factory()->create();

            $order1 = Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-111111',
            ]);

            $order2 = Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-222222',
            ]);

            $component = Livewire::actingAs($user)
                ->test(OrderList::class);

            // Initially shows all
            $component->assertSee('ORD-111111')
                ->assertSee('ORD-222222');

            // Search for first order
            $component->set('search', '111')
                ->assertSee('ORD-111111')
                ->assertDontSee('ORD-222222');

            // Search for second order
            $component->set('search', '222')
                ->assertDontSee('ORD-111111')
                ->assertSee('ORD-222222');

            // Clear search shows all
            $component->set('search', '')
                ->assertSee('ORD-111111')
                ->assertSee('ORD-222222');
        });

        it('resets pagination when search changes', function () {
            $user = User::factory()->create();

            // Create 15 orders to have pagination
            Order::factory()->count(15)->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Pending,
            ]);

            $component = Livewire::actingAs($user)
                ->test(OrderList::class)
                ->call('nextPage');

            $orders = $component->viewData('orders');
            expect($orders->currentPage())->toBe(2);

            // When search changes, should reset to page 1
            $component->set('search', 'ORD');

            $orders = $component->viewData('orders');
            expect($orders->currentPage())->toBe(1);
        });
    });

    describe('Mensagem amigavel quando nenhum pedido encontrado', function () {
        it('shows friendly message when search has no results', function () {
            $user = User::factory()->create();

            Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-123456',
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->set('search', 'NONEXISTENT')
                ->assertSee('Nenhum pedido encontrado')
                ->assertSee('Tente buscar por outro numero');
        });

        it('shows different message when user has no orders at all', function () {
            $user = User::factory()->create();

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSee('Nenhum pedido encontrado')
                ->assertSee('Voce ainda nao realizou nenhum pedido');
        });

        it('shows clear search button when search has no results', function () {
            $user = User::factory()->create();

            Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-123456',
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->set('search', 'NONEXISTENT')
                ->assertSee('Limpar busca');
        });
    });

    describe('Busca combinada com filtro de status', function () {
        it('combines search with status filter', function () {
            $user = User::factory()->create();

            $pendingOrder = Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-111111',
                'status'       => OrderStatus::Pending,
            ]);

            $completedOrder = Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-111222',
                'status'       => OrderStatus::Completed,
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->set('search', '111')
                ->set('status', 'pending')
                ->assertSee('ORD-111111')
                ->assertDontSee('ORD-111222');
        });

        it('updates counter when search and filter are combined', function () {
            $user = User::factory()->create();

            // Create orders with order numbers containing 'AAA'
            for ($i = 1; $i <= 3; $i++) {
                Order::factory()->create([
                    'user_id'      => $user->id,
                    'order_number' => "ORD-AAA-{$i}",
                    'status'       => OrderStatus::Pending,
                ]);
            }

            // Create orders with order numbers containing 'BBB'
            for ($i = 1; $i <= 2; $i++) {
                Order::factory()->create([
                    'user_id'      => $user->id,
                    'order_number' => "ORD-BBB-{$i}",
                    'status'       => OrderStatus::Pending,
                ]);
            }

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->set('status', 'pending')
                ->assertSee('5 pedidos')
                ->set('search', 'AAA')
                ->assertSee('3 pedidos');
        });
    });

    describe('URL sync para busca', function () {
        it('reflects search in URL query string', function () {
            $user = User::factory()->create();

            Order::factory()->create([
                'user_id' => $user->id,
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->set('search', 'ORD-123')
                ->assertSet('search', 'ORD-123');
        });

        it('initializes search from URL query string', function () {
            $user = User::factory()->create();

            $order = Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-123456',
            ]);

            $otherOrder = Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-999999',
            ]);

            Livewire::actingAs($user)
                ->withQueryParams(['search' => '123456'])
                ->test(OrderList::class)
                ->assertSet('search', '123456')
                ->assertSee('ORD-123456')
                ->assertDontSee('ORD-999999');
        });
    });
});
