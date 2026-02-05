<?php

declare(strict_types = 1);

use App\Domain\Checkout\Enums\OrderStatus;
use App\Domain\Checkout\Models\Order;
use App\Domain\Customer\Livewire\CustomerDashboard;
use App\Models\User;
use Livewire\Livewire;

describe('US-06: Historico de pedidos no dashboard', function () {
    describe('Card Meus Pedidos mostra ultimos 3 pedidos', function () {
        it('displays the last 3 orders on dashboard', function () {
            $user = User::factory()->create();

            // Create 5 orders, we should only see the last 3
            $orders = collect();

            for ($i = 1; $i <= 5; $i++) {
                $orders->push(Order::factory()->create([
                    'user_id'      => $user->id,
                    'order_number' => "ORD-00000{$i}",
                    'placed_at'    => now()->subDays(5 - $i), // Order 5 is most recent
                ]));
            }

            Livewire::actingAs($user)
                ->test(CustomerDashboard::class)
                ->assertSee('ORD-000005') // Most recent
                ->assertSee('ORD-000004')
                ->assertSee('ORD-000003')
                ->assertDontSee('ORD-000002') // Should not be visible
                ->assertDontSee('ORD-000001');
        });

        it('displays orders in descending order (most recent first)', function () {
            $user = User::factory()->create();

            Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-OLDEST',
                'placed_at'    => now()->subDays(10),
            ]);

            Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-NEWEST',
                'placed_at'    => now(),
            ]);

            $component = Livewire::actingAs($user)
                ->test(CustomerDashboard::class);

            $html      = $component->html();
            $newestPos = strpos($html, 'ORD-NEWEST');
            $oldestPos = strpos($html, 'ORD-OLDEST');

            expect($newestPos)->toBeLessThan($oldestPos);
        });
    });

    describe('Cada pedido mostra numero, data e status', function () {
        it('displays order number', function () {
            $user = User::factory()->create();

            Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-ABC123',
            ]);

            Livewire::actingAs($user)
                ->test(CustomerDashboard::class)
                ->assertSee('ORD-ABC123');
        });

        it('displays order date', function () {
            $user = User::factory()->create();

            Order::factory()->create([
                'user_id'   => $user->id,
                'placed_at' => now()->setDate(2025, 1, 15),
            ]);

            Livewire::actingAs($user)
                ->test(CustomerDashboard::class)
                ->assertSee('15/01/2025');
        });

        it('displays order status badge', function () {
            $user = User::factory()->create();

            Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Pending,
            ]);

            Livewire::actingAs($user)
                ->test(CustomerDashboard::class)
                ->assertSee('Pendente');
        });

        it('displays different status badges correctly', function () {
            $user = User::factory()->create();

            Order::factory()->create([
                'user_id'   => $user->id,
                'status'    => OrderStatus::Shipped,
                'placed_at' => now(),
            ]);

            Order::factory()->create([
                'user_id'   => $user->id,
                'status'    => OrderStatus::Completed,
                'placed_at' => now()->subDay(),
            ]);

            Livewire::actingAs($user)
                ->test(CustomerDashboard::class)
                ->assertSee('Enviado')
                ->assertSee('Entregue');
        });
    });

    describe('Link Ver todos os pedidos', function () {
        it('displays link to orders page when there are orders', function () {
            $user = User::factory()->create();

            Order::factory()->create(['user_id' => $user->id]);

            Livewire::actingAs($user)
                ->test(CustomerDashboard::class)
                ->assertSee('Ver todos os pedidos');
        });

        it('link navigates to orders page', function () {
            $user = User::factory()->create();

            Order::factory()->create(['user_id' => $user->id]);

            Livewire::actingAs($user)
                ->test(CustomerDashboard::class)
                ->assertSeeHtml('minha-conta/pedidos');
        });
    });

    describe('Empty state quando nao ha pedidos', function () {
        it('displays empty state message when user has no orders', function () {
            $user = User::factory()->create();

            Livewire::actingAs($user)
                ->test(CustomerDashboard::class)
                ->assertSee('Nenhum pedido realizado ainda');
        });

        it('displays go shopping button when user has no orders', function () {
            $user = User::factory()->create();

            Livewire::actingAs($user)
                ->test(CustomerDashboard::class)
                ->assertSee('Ir as Compras');
        });

        it('does not show empty state when user has orders', function () {
            $user = User::factory()->create();

            Order::factory()->create(['user_id' => $user->id]);

            Livewire::actingAs($user)
                ->test(CustomerDashboard::class)
                ->assertDontSee('Nenhum pedido realizado ainda');
        });
    });

    describe('Contador total de pedidos', function () {
        it('displays total order count in card header', function () {
            $user = User::factory()->create();

            Order::factory()->count(5)->create(['user_id' => $user->id]);

            Livewire::actingAs($user)
                ->test(CustomerDashboard::class)
                ->assertSee('5 pedidos');
        });

        it('displays singular form for single order', function () {
            $user = User::factory()->create();

            Order::factory()->create(['user_id' => $user->id]);

            Livewire::actingAs($user)
                ->test(CustomerDashboard::class)
                ->assertSee('1 pedido');
        });

        it('displays zero count when no orders', function () {
            $user = User::factory()->create();

            Livewire::actingAs($user)
                ->test(CustomerDashboard::class)
                ->assertSee('0 pedidos');
        });

        it('shows correct count even with many orders', function () {
            $user = User::factory()->create();

            Order::factory()->count(25)->create(['user_id' => $user->id]);

            Livewire::actingAs($user)
                ->test(CustomerDashboard::class)
                ->assertSee('25 pedidos');
        });
    });

    describe('Isolamento de dados do usuario', function () {
        it('only shows orders belonging to the authenticated user', function () {
            $user      = User::factory()->create();
            $otherUser = User::factory()->create();

            Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-MYORDER',
            ]);

            Order::factory()->create([
                'user_id'      => $otherUser->id,
                'order_number' => 'ORD-NOTMINE',
            ]);

            Livewire::actingAs($user)
                ->test(CustomerDashboard::class)
                ->assertSee('ORD-MYORDER')
                ->assertDontSee('ORD-NOTMINE');
        });
    });
});
