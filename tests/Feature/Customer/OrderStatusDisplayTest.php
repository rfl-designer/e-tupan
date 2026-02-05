<?php

declare(strict_types = 1);

use App\Domain\Checkout\Enums\{OrderStatus, PaymentStatus};
use App\Domain\Checkout\Models\Order;
use App\Domain\Customer\Livewire\OrderDetail;
use App\Models\User;
use Livewire\Livewire;

describe('US-04: Status atual do pedido', function () {
    describe('O status do pedido é exibido com badge colorido', function () {
        it('displays order status with badge for pending orders', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Pending,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Pendente')
                ->assertSeeHtml('amber'); // Badge color for pending
        });

        it('displays order status with badge for processing orders', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Processing,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Processando')
                ->assertSeeHtml('sky');
        });

        it('displays order status with badge for shipped orders', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Shipped,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Enviado')
                ->assertSeeHtml('indigo');
        });

        it('displays order status with badge for completed orders', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Completed,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Entregue')
                ->assertSeeHtml('lime');
        });

        it('displays order status with badge for cancelled orders', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Cancelled,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Cancelado')
                ->assertSeeHtml('red');
        });

        it('displays order status icon', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'status'  => OrderStatus::Shipped,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSeeHtml('data-flux-icon'); // Flux icon SVG
        });
    });

    describe('O status de pagamento é exibido separadamente', function () {
        it('displays payment status with badge for pending payment', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'        => $user->id,
                'payment_status' => PaymentStatus::Pending,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Pagamento')
                ->assertSeeHtml('amber');
        });

        it('displays payment status with badge for approved payment', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'        => $user->id,
                'payment_status' => PaymentStatus::Approved,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Pagamento')
                ->assertSee('Aprovado');
        });

        it('displays payment status separately from order status', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'        => $user->id,
                'status'         => OrderStatus::Processing,
                'payment_status' => PaymentStatus::Approved,
            ]);

            $component = Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order]);

            // Both statuses should be visible
            $component->assertSee('Processando'); // Order status
            $component->assertSee('Aprovado'); // Payment status
        });

        it('displays payment status icon', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'        => $user->id,
                'payment_status' => PaymentStatus::Approved,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSeeHtml('data-flux-icon'); // Flux icon SVG
        });
    });

    describe('A data de criação do pedido é exibida', function () {
        it('displays order creation date', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'   => $user->id,
                'placed_at' => now()->parse('2024-06-15 14:30:00'),
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('15/06/2024')
                ->assertSee('14:30');
        });

        it('displays creation date in header', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'   => $user->id,
                'placed_at' => now()->parse('2024-12-25 10:00:00'),
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Realizado em')
                ->assertSee('25/12/2024');
        });
    });

    describe('Se o pedido foi enviado, a data de envio é exibida', function () {
        it('displays shipping date when order is shipped', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'    => $user->id,
                'status'     => OrderStatus::Shipped,
                'shipped_at' => now()->parse('2024-06-20 09:15:00'),
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Enviado em')
                ->assertSee('20/06/2024');
        });

        it('does not display shipping date when order is not shipped', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'    => $user->id,
                'status'     => OrderStatus::Processing,
                'shipped_at' => null,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertDontSee('Enviado em');
        });

        it('displays shipping date for completed orders', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'      => $user->id,
                'status'       => OrderStatus::Completed,
                'shipped_at'   => now()->parse('2024-06-18 11:00:00'),
                'delivered_at' => now()->parse('2024-06-22 15:30:00'),
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Enviado em')
                ->assertSee('18/06/2024');
        });
    });

    describe('Se o pedido foi entregue, a data de entrega é exibida', function () {
        it('displays delivery date when order is completed', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'      => $user->id,
                'status'       => OrderStatus::Completed,
                'delivered_at' => now()->parse('2024-06-25 16:45:00'),
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Entregue em')
                ->assertSee('25/06/2024');
        });

        it('does not display delivery date when order is not delivered', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'      => $user->id,
                'status'       => OrderStatus::Shipped,
                'delivered_at' => null,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertDontSee('Entregue em');
        });

        it('displays both shipping and delivery dates when available', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'      => $user->id,
                'status'       => OrderStatus::Completed,
                'shipped_at'   => now()->parse('2024-06-18 11:00:00'),
                'delivered_at' => now()->parse('2024-06-22 15:30:00'),
            ]);

            $component = Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order]);

            $component->assertSee('Enviado em');
            $component->assertSee('18/06/2024');
            $component->assertSee('Entregue em');
            $component->assertSee('22/06/2024');
        });
    });

    describe('Seção de status e datas', function () {
        it('displays status section with header', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Status');
        });

        it('displays all status information in a structured section', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'        => $user->id,
                'status'         => OrderStatus::Completed,
                'payment_status' => PaymentStatus::Approved,
                'placed_at'      => now()->parse('2024-06-10 10:00:00'),
                'shipped_at'     => now()->parse('2024-06-12 14:00:00'),
                'delivered_at'   => now()->parse('2024-06-15 16:00:00'),
            ]);

            $component = Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order]);

            $component->assertSee('Status');
            $component->assertSee('Entregue');
            $component->assertSee('Aprovado');
            $component->assertSee('Enviado em');
            $component->assertSee('Entregue em');
        });
    });
});
