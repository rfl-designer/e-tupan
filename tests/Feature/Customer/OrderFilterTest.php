<?php

declare(strict_types = 1);

use App\Domain\Checkout\Enums\OrderStatus;
use App\Domain\Checkout\Models\Order;
use App\Domain\Customer\Livewire\OrderList;
use App\Models\User;
use Livewire\Livewire;

describe('US-03: Filtrar pedidos por status', function () {
    describe('Tabs permitem filtrar por status', function () {
        it('displays filter tabs', function () {
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

        it('filters by pending status', function () {
            $user = User::factory()->create();

            $pendingOrder = Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Pending,
            ]);

            $completedOrder = Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Completed,
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->set('status', 'pending')
                ->assertSee($pendingOrder->order_number)
                ->assertDontSee($completedOrder->order_number);
        });

        it('filters by processing status', function () {
            $user = User::factory()->create();

            $processingOrder = Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Processing,
            ]);

            $pendingOrder = Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Pending,
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->set('status', 'processing')
                ->assertSee($processingOrder->order_number)
                ->assertDontSee($pendingOrder->order_number);
        });

        it('filters by shipped status', function () {
            $user = User::factory()->create();

            $shippedOrder = Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Shipped,
            ]);

            $pendingOrder = Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Pending,
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->set('status', 'shipped')
                ->assertSee($shippedOrder->order_number)
                ->assertDontSee($pendingOrder->order_number);
        });

        it('filters by completed status', function () {
            $user = User::factory()->create();

            $completedOrder = Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Completed,
            ]);

            $pendingOrder = Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Pending,
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->set('status', 'completed')
                ->assertSee($completedOrder->order_number)
                ->assertDontSee($pendingOrder->order_number);
        });

        it('filters by cancelled status', function () {
            $user = User::factory()->create();

            $cancelledOrder = Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Cancelled,
            ]);

            $pendingOrder = Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Pending,
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->set('status', 'cancelled')
                ->assertSee($cancelledOrder->order_number)
                ->assertDontSee($pendingOrder->order_number);
        });

        it('shows all orders when status is empty', function () {
            $user = User::factory()->create();

            $pendingOrder = Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Pending,
            ]);

            $completedOrder = Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Completed,
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->set('status', '')
                ->assertSee($pendingOrder->order_number)
                ->assertSee($completedOrder->order_number);
        });
    });

    describe('Filtro atualiza lista sem recarregar pagina', function () {
        it('updates list reactively when status changes', function () {
            $user = User::factory()->create();

            $pendingOrder = Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Pending,
            ]);

            $completedOrder = Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Completed,
            ]);

            $component = Livewire::actingAs($user)
                ->test(OrderList::class);

            // Initially shows all
            $component->assertSee($pendingOrder->order_number)
                ->assertSee($completedOrder->order_number);

            // Filter by pending
            $component->set('status', 'pending')
                ->assertSee($pendingOrder->order_number)
                ->assertDontSee($completedOrder->order_number);

            // Filter by completed
            $component->set('status', 'completed')
                ->assertDontSee($pendingOrder->order_number)
                ->assertSee($completedOrder->order_number);

            // Back to all
            $component->set('status', '')
                ->assertSee($pendingOrder->order_number)
                ->assertSee($completedOrder->order_number);
        });
    });

    describe('Contador de pedidos atualiza ao filtrar', function () {
        it('shows total order count', function () {
            $user = User::factory()->create();

            Order::factory()->count(5)->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Pending,
            ]);

            Order::factory()->count(3)->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Completed,
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSee('8 pedidos');
        });

        it('shows filtered order count when filtering', function () {
            $user = User::factory()->create();

            Order::factory()->count(5)->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Pending,
            ]);

            Order::factory()->count(3)->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Completed,
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->set('status', 'pending')
                ->assertSee('5 pedidos');
        });

        it('shows singular form for single order', function () {
            $user = User::factory()->create();

            Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Pending,
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSee('1 pedido');
        });
    });

    describe('Filtro refletido na URL', function () {
        it('reflects status filter in URL query string', function () {
            $user = User::factory()->create();

            Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Pending,
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->set('status', 'pending')
                ->assertSet('status', 'pending');
        });

        it('initializes from URL query string', function () {
            $user = User::factory()->create();

            $pendingOrder = Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Pending,
            ]);

            $completedOrder = Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Completed,
            ]);

            Livewire::actingAs($user)
                ->withQueryParams(['status' => 'pending'])
                ->test(OrderList::class)
                ->assertSet('status', 'pending')
                ->assertSee($pendingOrder->order_number)
                ->assertDontSee($completedOrder->order_number);
        });

        it('clears filter when status is set to empty', function () {
            $user = User::factory()->create();

            Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Pending,
            ]);

            Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Completed,
            ]);

            Livewire::actingAs($user)
                ->withQueryParams(['status' => 'pending'])
                ->test(OrderList::class)
                ->call('clearFilter')
                ->assertSet('status', '');
        });
    });

    describe('Resetar paginacao ao filtrar', function () {
        it('resets pagination when filter changes', function () {
            $user = User::factory()->create();

            Order::factory()->count(15)->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Pending,
            ]);

            Order::factory()->count(5)->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Completed,
            ]);

            $component = Livewire::actingAs($user)
                ->test(OrderList::class)
                ->call('nextPage');

            $orders = $component->viewData('orders');
            expect($orders->currentPage())->toBe(2);

            // When filter changes, should reset to page 1
            $component->set('status', 'completed');

            $orders = $component->viewData('orders');
            expect($orders->currentPage())->toBe(1);
        });
    });
});
