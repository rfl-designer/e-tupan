<?php

declare(strict_types = 1);

use App\Domain\Checkout\Enums\OrderStatus;
use App\Domain\Checkout\Models\Order;
use App\Domain\Customer\Livewire\OrderList;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\{actingAs, get};

describe('US-01: Listagem de pedidos do cliente', function () {
    describe('Pagina /minha-conta/pedidos exibe lista de pedidos', function () {
        it('displays the order list page for authenticated users', function () {
            $user = User::factory()->create();

            $response = actingAs($user)->get(route('customer.orders'));

            $response->assertOk();
            /** @phpstan-ignore method.notFound */
            $response->assertSeeLivewire(OrderList::class);
        });

        it('shows orders belonging to the authenticated user', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Pending,
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSee($order->order_number);
        });
    });

    describe('Apenas pedidos do usuario autenticado sao exibidos', function () {
        it('does not show orders from other users', function () {
            $user      = User::factory()->create();
            $otherUser = User::factory()->create();

            $userOrder = Order::factory()->create([
                'user_id' => $user->id,
            ]);

            $otherUserOrder = Order::factory()->create([
                'user_id' => $otherUser->id,
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSee($userOrder->order_number)
                ->assertDontSee($otherUserOrder->order_number);
        });

        it('does not show guest orders', function () {
            $user = User::factory()->create();

            $userOrder = Order::factory()->create([
                'user_id' => $user->id,
            ]);

            $guestOrder = Order::factory()->create([
                'user_id'     => null,
                'guest_email' => 'guest@example.com',
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSee($userOrder->order_number)
                ->assertDontSee($guestOrder->order_number);
        });
    });

    describe('Lista mostra numero, data, status e valor total', function () {
        it('displays order number', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-TEST01',
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSee('ORD-TEST01');
        });

        it('displays order date', function () {
            $user     = User::factory()->create();
            $placedAt = now()->subDays(5);
            $order    = Order::factory()->create([
                'user_id'   => $user->id,
                'placed_at' => $placedAt,
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSee($placedAt->format('d/m/Y'));
        });

        it('displays order status', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Processing,
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSee('Processando');
        });

        it('displays order total', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'total'   => 15990, // R$ 159,90
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSee('159,90');
        });
    });

    describe('Pedidos ordenados do mais recente para o mais antigo', function () {
        it('orders are sorted by placed_at descending', function () {
            $user = User::factory()->create();

            $oldOrder = Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-OLD001',
                'placed_at'    => now()->subDays(10),
            ]);

            $newOrder = Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-NEW001',
                'placed_at'    => now()->subDay(),
            ]);

            $component = Livewire::actingAs($user)->test(OrderList::class);

            $orders = $component->viewData('orders');

            expect($orders->first()->order_number)->toBe('ORD-NEW001');
            expect($orders->last()->order_number)->toBe('ORD-OLD001');
        });
    });

    describe('Visitantes sao redirecionados para login', function () {
        it('redirects guests to login page', function () {
            get(route('customer.orders'))
                ->assertRedirect(route('login'));
        });
    });

    describe('Paginacao com 10 pedidos por pagina', function () {
        it('paginates orders with 10 per page', function () {
            $user = User::factory()->create();

            Order::factory()->count(15)->create([
                'user_id' => $user->id,
            ]);

            $component = Livewire::actingAs($user)->test(OrderList::class);

            $orders = $component->viewData('orders');

            expect($orders)->toHaveCount(10);
            expect($orders->total())->toBe(15);
        });

        it('can navigate to next page', function () {
            $user = User::factory()->create();

            Order::factory()->count(15)->create([
                'user_id' => $user->id,
            ]);

            $component = Livewire::actingAs($user)
                ->test(OrderList::class)
                ->call('nextPage');

            $orders = $component->viewData('orders');

            expect($orders)->toHaveCount(5);
            expect($orders->currentPage())->toBe(2);
        });
    });

    describe('Empty state quando nao ha pedidos', function () {
        it('shows empty state message when user has no orders', function () {
            $user = User::factory()->create();

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSee('Voce ainda nao realizou nenhum pedido');
        });

        it('shows call to action button when empty', function () {
            $user = User::factory()->create();

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSee('Ir as Compras');
        });
    });
});

describe('US-05 (F6-06) / US-08 (F6-07): Link da lista para detalhes do pedido', function () {
    describe('O link da lista de pedidos leva para a pagina de detalhes', function () {
        it('displays clickable order card linking to details with wire:navigate', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-LINK01',
            ]);

            $component = Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSee('ORD-LINK01')
                ->assertSeeHtml('href="' . route('customer.orders.show', $order) . '"');

            expect($component->html())->toContain('wire:navigate');
        });
    });
});
