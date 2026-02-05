<?php

declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use App\Domain\Cart\Livewire\Admin\AbandonedCarts;
use App\Domain\Cart\Models\{Cart, CartItem};
use App\Domain\Catalog\Models\Product;
use App\Models\User;
use Livewire\Livewire;

describe('AbandonedCarts Admin', function () {
    describe('route access', function () {
        it('requires admin authentication', function () {
            $this->get(route('admin.carts.abandoned'))
                ->assertRedirect(route('admin.login'));
        });

        it('requires 2FA confirmation', function () {
            $admin = Admin::factory()->withTwoFactor()->create();

            $this->actingAs($admin, 'admin')
                ->get(route('admin.carts.abandoned'))
                ->assertRedirect(route('admin.two-factor.challenge'));
        });

        it('allows access to authenticated admin with 2FA', function () {
            $admin = Admin::factory()->withTwoFactor()->create();

            actingAsAdminWith2FA($this, $admin)
                ->get(route('admin.carts.abandoned'))
                ->assertOk();
        });
    });

    describe('AbandonedCarts component', function () {
        it('displays abandoned carts with items', function () {
            $admin   = Admin::factory()->create();
            $user    = User::factory()->create(['name' => 'Joao Silva']);
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            $cart = Cart::factory()->forUser($user)->abandoned()->create([
                'subtotal'     => 15000,
                'total'        => 15000,
                'abandoned_at' => now()->subDays(2),
            ]);
            CartItem::factory()->forCart($cart)->forProduct($product)->withQuantity(2)->create();

            actingAsAdminWith2FA($this, $admin);

            Livewire::test(AbandonedCarts::class)
                ->assertSee('Joao Silva')
                ->assertSee('150,00');
        });

        it('shows item count for each cart', function () {
            $admin    = Admin::factory()->create();
            $user     = User::factory()->create();
            $product1 = Product::factory()->active()->create(['stock_quantity' => 10]);
            $product2 = Product::factory()->active()->create(['stock_quantity' => 10]);

            $cart = Cart::factory()->forUser($user)->abandoned()->create([
                'abandoned_at' => now()->subDay(),
            ]);
            CartItem::factory()->forCart($cart)->forProduct($product1)->withQuantity(2)->create();
            CartItem::factory()->forCart($cart)->forProduct($product2)->withQuantity(3)->create();

            actingAsAdminWith2FA($this, $admin);

            Livewire::test(AbandonedCarts::class)
                ->assertSee('5 itens');
        });

        it('shows abandonment date', function () {
            $admin   = Admin::factory()->create();
            $user    = User::factory()->create();
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            $abandonedAt = now()->subDays(3);
            $cart        = Cart::factory()->forUser($user)->abandoned()->create([
                'abandoned_at' => $abandonedAt,
            ]);
            CartItem::factory()->forCart($cart)->forProduct($product)->create();

            actingAsAdminWith2FA($this, $admin);

            Livewire::test(AbandonedCarts::class)
                ->assertSee($abandonedAt->format('d/m/Y'));
        });

        it('shows guest identifier for session carts', function () {
            $admin   = Admin::factory()->create();
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            $cart = Cart::factory()->forSession('guest-session')->abandoned()->create([
                'abandoned_at' => now()->subDay(),
            ]);
            CartItem::factory()->forCart($cart)->forProduct($product)->create();

            actingAsAdminWith2FA($this, $admin);

            Livewire::test(AbandonedCarts::class)
                ->assertSee('Visitante');
        });

        it('does not show active carts', function () {
            $admin   = Admin::factory()->create();
            $user    = User::factory()->create(['name' => 'Usuario Ativo']);
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            $cart = Cart::factory()->forUser($user)->active()->create();
            CartItem::factory()->forCart($cart)->forProduct($product)->create();

            actingAsAdminWith2FA($this, $admin);

            Livewire::test(AbandonedCarts::class)
                ->assertDontSee('Usuario Ativo');
        });

        it('does not show converted carts', function () {
            $admin   = Admin::factory()->create();
            $user    = User::factory()->create(['name' => 'Usuario Convertido']);
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            $cart = Cart::factory()->forUser($user)->converted()->create();
            CartItem::factory()->forCart($cart)->forProduct($product)->create();

            actingAsAdminWith2FA($this, $admin);

            Livewire::test(AbandonedCarts::class)
                ->assertDontSee('Usuario Convertido');
        });

        it('orders by abandoned_at descending', function () {
            $admin   = Admin::factory()->create();
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            $user1 = User::factory()->create(['name' => 'Primeiro Abandono']);
            $user2 = User::factory()->create(['name' => 'Segundo Abandono']);

            $cart1 = Cart::factory()->forUser($user1)->abandoned()->create([
                'abandoned_at' => now()->subDays(5),
            ]);
            CartItem::factory()->forCart($cart1)->forProduct($product)->create();

            $cart2 = Cart::factory()->forUser($user2)->abandoned()->create([
                'abandoned_at' => now()->subDays(1),
            ]);
            CartItem::factory()->forCart($cart2)->forProduct($product)->create();

            actingAsAdminWith2FA($this, $admin);

            Livewire::test(AbandonedCarts::class)
                ->assertSeeInOrder(['Segundo Abandono', 'Primeiro Abandono']);
        });

        it('paginates results', function () {
            $admin   = Admin::factory()->create();
            $product = Product::factory()->active()->create(['stock_quantity' => 50]);

            // Create 25 abandoned carts
            for ($i = 0; $i < 25; $i++) {
                $user = User::factory()->create();
                $cart = Cart::factory()->forUser($user)->abandoned()->create([
                    'abandoned_at' => now()->subHours($i),
                ]);
                CartItem::factory()->forCart($cart)->forProduct($product)->create();
            }

            actingAsAdminWith2FA($this, $admin);

            Livewire::test(AbandonedCarts::class)
                ->assertSee('Mostrando');
        });
    });

    describe('filtering', function () {
        it('filters by customer name', function () {
            $admin   = Admin::factory()->create();
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            $user1 = User::factory()->create(['name' => 'Maria Santos']);
            $user2 = User::factory()->create(['name' => 'Jose Oliveira']);

            $cart1 = Cart::factory()->forUser($user1)->abandoned()->create([
                'abandoned_at' => now()->subDay(),
            ]);
            CartItem::factory()->forCart($cart1)->forProduct($product)->create();

            $cart2 = Cart::factory()->forUser($user2)->abandoned()->create([
                'abandoned_at' => now()->subDay(),
            ]);
            CartItem::factory()->forCart($cart2)->forProduct($product)->create();

            actingAsAdminWith2FA($this, $admin);

            Livewire::test(AbandonedCarts::class)
                ->set('search', 'Maria')
                ->assertSee('Maria Santos')
                ->assertDontSee('Jose Oliveira');
        });

        it('filters by date range', function () {
            $admin   = Admin::factory()->create();
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            $user1 = User::factory()->create(['name' => 'Cliente Antigo']);
            $user2 = User::factory()->create(['name' => 'Cliente Recente']);

            $cart1 = Cart::factory()->forUser($user1)->abandoned()->create([
                'abandoned_at' => now()->subDays(10),
            ]);
            CartItem::factory()->forCart($cart1)->forProduct($product)->create();

            $cart2 = Cart::factory()->forUser($user2)->abandoned()->create([
                'abandoned_at' => now()->subDays(2),
            ]);
            CartItem::factory()->forCart($cart2)->forProduct($product)->create();

            actingAsAdminWith2FA($this, $admin);

            Livewire::test(AbandonedCarts::class)
                ->set('dateFrom', now()->subDays(5)->format('Y-m-d'))
                ->set('dateTo', now()->format('Y-m-d'))
                ->assertSee('Cliente Recente')
                ->assertDontSee('Cliente Antigo');
        });
    });

    describe('cart details', function () {
        it('shows cart items in modal', function () {
            $admin   = Admin::factory()->create();
            $product = Product::factory()->active()->create([
                'name'           => 'Produto no Carrinho',
                'stock_quantity' => 10,
            ]);

            $user = User::factory()->create();
            $cart = Cart::factory()->forUser($user)->abandoned()->create([
                'abandoned_at' => now()->subDay(),
            ]);
            CartItem::factory()->forCart($cart)->forProduct($product)->withQuantity(3)->create();

            actingAsAdminWith2FA($this, $admin);

            Livewire::test(AbandonedCarts::class)
                ->call('showDetails', $cart->id)
                ->assertSet('selectedCart.id', $cart->id)
                ->assertSee('Produto no Carrinho');
        });
    });
});
