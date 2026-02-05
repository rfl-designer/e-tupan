<?php

declare(strict_types = 1);

use App\Domain\Checkout\Models\Order;
use App\Domain\Customer\Livewire\OrderDetail;
use App\Models\User;
use Livewire\Livewire;

describe('US-03: Resumo financeiro do pedido', function () {
    describe('O resumo exibe: subtotal dos itens, valor do frete, desconto (se houver) e total', function () {
        it('displays subtotal value', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'       => $user->id,
                'subtotal'      => 15990, // R$ 159,90
                'shipping_cost' => 2500,
                'discount'      => 0,
                'total'         => 18490,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Subtotal')
                ->assertSee('R$ 159,90');
        });

        it('displays shipping cost value', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'       => $user->id,
                'subtotal'      => 10000,
                'shipping_cost' => 3590, // R$ 35,90
                'discount'      => 0,
                'total'         => 13590,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Frete')
                ->assertSee('R$ 35,90');
        });

        it('displays discount when present', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'       => $user->id,
                'subtotal'      => 20000,
                'shipping_cost' => 2000,
                'discount'      => 5000, // R$ 50,00
                'total'         => 17000,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Desconto')
                ->assertSee('R$ 50,00');
        });

        it('does not display discount section when discount is zero', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'       => $user->id,
                'subtotal'      => 10000,
                'shipping_cost' => 2000,
                'discount'      => 0,
                'total'         => 12000,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertDontSee('Desconto');
        });

        it('displays total value', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'       => $user->id,
                'subtotal'      => 25000,
                'shipping_cost' => 1500,
                'discount'      => 2500,
                'total'         => 24000, // R$ 240,00
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Total')
                ->assertSee('R$ 240,00');
        });

        it('displays free shipping when shipping cost is zero', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'       => $user->id,
                'subtotal'      => 50000,
                'shipping_cost' => 0,
                'discount'      => 0,
                'total'         => 50000,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Frete')
                ->assertSee('Grátis');
        });
    });

    describe('Se houver cupom aplicado, o código é exibido junto ao desconto', function () {
        it('displays coupon code when applied', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'       => $user->id,
                'subtotal'      => 30000,
                'shipping_cost' => 2000,
                'discount'      => 6000,
                'total'         => 26000,
                'coupon_code'   => 'PROMO20',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('PROMO20')
                ->assertSee('R$ 60,00');
        });

        it('does not display coupon code section when no coupon', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'       => $user->id,
                'subtotal'      => 10000,
                'shipping_cost' => 2000,
                'discount'      => 0,
                'total'         => 12000,
                'coupon_code'   => null,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertDontSee('Cupom');
        });

        it('displays discount with coupon code inline', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'       => $user->id,
                'subtotal'      => 20000,
                'shipping_cost' => 1500,
                'discount'      => 4000,
                'total'         => 17500,
                'coupon_code'   => 'SAVE20',
            ]);

            $component = Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order]);

            $component->assertSee('Desconto');
            $component->assertSee('SAVE20');
            $component->assertSee('R$ 40,00');
        });
    });

    describe('Os valores são formatados em Reais', function () {
        it('formats values with thousands separator', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'       => $user->id,
                'subtotal'      => 1234567, // R$ 12.345,67
                'shipping_cost' => 0,
                'discount'      => 0,
                'total'         => 1234567,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('R$ 12.345,67');
        });

        it('formats small values correctly', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'       => $user->id,
                'subtotal'      => 990, // R$ 9,90
                'shipping_cost' => 500,
                'discount'      => 0,
                'total'         => 1490,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('R$ 9,90')
                ->assertSee('R$ 5,00')
                ->assertSee('R$ 14,90');
        });
    });

    describe('O total é destacado visualmente', function () {
        it('displays total with visual emphasis', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'       => $user->id,
                'subtotal'      => 10000,
                'shipping_cost' => 2000,
                'discount'      => 0,
                'total'         => 12000,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSeeHtml('font-semibold')
                ->assertSeeHtml('text-lg');
        });
    });

    describe('Seção de resumo financeiro', function () {
        it('displays financial summary section with header', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'       => $user->id,
                'subtotal'      => 15000,
                'shipping_cost' => 2500,
                'discount'      => 0,
                'total'         => 17500,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Resumo');
        });
    });
});
