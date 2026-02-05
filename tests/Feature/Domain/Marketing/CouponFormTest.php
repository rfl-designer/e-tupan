<?php

declare(strict_types = 1);

use App\Domain\Cart\Services\CartService;
use App\Domain\Catalog\Models\Product;
use App\Domain\Marketing\Livewire\CouponForm;
use App\Domain\Marketing\Models\Coupon;
use App\Models\User;
use Livewire\Livewire;

describe('CouponForm Component', function () {
    beforeEach(function () {
        $this->user    = User::factory()->create();
        $this->product = Product::factory()->active()->simple()->create([
            'price'          => 10000,
            'stock_quantity' => 10,
        ]);

        $cartService = new CartService();
        $this->cart  = $cartService->getOrCreate(userId: $this->user->id);
        $cartService->addItem($this->cart, $this->product, 2);
        $this->cart->refresh();
    });

    describe('rendering', function () {
        it('renders successfully', function () {
            Livewire::actingAs($this->user)
                ->test(CouponForm::class)
                ->assertStatus(200);
        });

        it('shows coupon input when no coupon applied', function () {
            Livewire::actingAs($this->user)
                ->test(CouponForm::class)
                ->assertSee('Cupom de Desconto')
                ->assertSee('Aplicar');
        });

        it('shows applied coupon when coupon exists', function () {
            $coupon = Coupon::factory()->withCode('TEST10')->percentage(10)->create();

            $this->cart->coupon_id = $coupon->id;
            $this->cart->discount  = 2000;
            $this->cart->save();

            Livewire::actingAs($this->user)
                ->test(CouponForm::class)
                ->assertSet('couponCode', 'TEST10')
                ->assertSee('Cupom aplicado: TEST10')
                ->assertSee('10% de desconto')
                ->assertSee('-R$ 20,00');
        });

        it('loads existing coupon code on mount', function () {
            $coupon = Coupon::factory()->withCode('MOUNTED')->create();

            $this->cart->coupon_id = $coupon->id;
            $this->cart->save();

            Livewire::actingAs($this->user)
                ->test(CouponForm::class)
                ->assertSet('couponCode', 'MOUNTED');
        });
    });

    describe('applying coupon', function () {
        it('applies valid coupon', function () {
            Coupon::factory()->withCode('VALID10')->percentage(10)->active()->create();

            Livewire::actingAs($this->user)
                ->test(CouponForm::class)
                ->set('couponCode', 'VALID10')
                ->call('applyCoupon')
                ->assertSet('successMessage', 'Cupom aplicado com sucesso!')
                ->assertDispatched('cart-updated')
                ->assertDispatched('coupon-applied');

            $this->cart->refresh();
            expect($this->cart->coupon_id)->not->toBeNull()
                ->and($this->cart->discount)->toBe(2000);
        });

        it('shows error for empty coupon code', function () {
            Livewire::actingAs($this->user)
                ->test(CouponForm::class)
                ->set('couponCode', '')
                ->call('applyCoupon')
                ->assertSet('errorMessage', 'Digite um codigo de cupom.');
        });

        it('shows error for invalid coupon', function () {
            Livewire::actingAs($this->user)
                ->test(CouponForm::class)
                ->set('couponCode', 'INVALID')
                ->call('applyCoupon')
                ->assertSet('errorMessage', "Cupom 'INVALID' nao encontrado.");
        });

        it('shows error for inactive coupon', function () {
            Coupon::factory()->withCode('INACTIVE')->inactive()->create();

            Livewire::actingAs($this->user)
                ->test(CouponForm::class)
                ->set('couponCode', 'INACTIVE')
                ->call('applyCoupon')
                ->assertSet('errorMessage', 'Este cupom esta inativo.');
        });

        it('shows error for expired coupon', function () {
            Coupon::factory()->withCode('EXPIRED')->active()->expired()->create();

            Livewire::actingAs($this->user)
                ->test(CouponForm::class)
                ->set('couponCode', 'EXPIRED')
                ->call('applyCoupon')
                ->assertSet('errorMessage', 'Este cupom expirou.');
        });

        it('shows error when minimum order not met', function () {
            Coupon::factory()
                ->withCode('MINIMUM')
                ->active()
                ->percentage(10)
                ->withMinimumOrder(50000)
                ->create();

            Livewire::actingAs($this->user)
                ->test(CouponForm::class)
                ->set('couponCode', 'MINIMUM')
                ->call('applyCoupon')
                ->assertSee('O pedido minimo para este cupom');
        });

        it('shows error for empty cart', function () {
            $emptyUser = User::factory()->create();

            Livewire::actingAs($emptyUser)
                ->test(CouponForm::class)
                ->set('couponCode', 'TEST')
                ->call('applyCoupon')
                ->assertSet('errorMessage', 'Adicione itens ao carrinho primeiro.');
        });

        it('clears previous messages on new apply', function () {
            Livewire::actingAs($this->user)
                ->test(CouponForm::class)
                ->set('errorMessage', 'Previous error')
                ->set('successMessage', 'Previous success')
                ->set('couponCode', 'INVALID')
                ->call('applyCoupon')
                ->assertSet('successMessage', '');
        });
    });

    describe('removing coupon', function () {
        it('removes applied coupon', function () {
            $coupon = Coupon::factory()->percentage(10)->create();

            $this->cart->coupon_id = $coupon->id;
            $this->cart->discount  = 2000;
            $this->cart->save();

            Livewire::actingAs($this->user)
                ->test(CouponForm::class)
                ->call('removeCoupon')
                ->assertSet('couponCode', '')
                ->assertSet('successMessage', 'Cupom removido com sucesso.')
                ->assertDispatched('cart-updated')
                ->assertDispatched('coupon-removed');

            $this->cart->refresh();
            expect($this->cart->coupon_id)->toBeNull()
                ->and($this->cart->discount)->toBe(0);
        });

        it('shows error when no coupon to remove', function () {
            Livewire::actingAs($this->user)
                ->test(CouponForm::class)
                ->call('removeCoupon')
                ->assertSet('errorMessage', 'Nenhum cupom aplicado neste carrinho.');
        });
    });

    describe('computed properties', function () {
        it('computes hasCoupon correctly when no coupon', function () {
            $component = Livewire::actingAs($this->user)
                ->test(CouponForm::class);

            expect($component->get('hasCoupon'))->toBeFalse();
        });

        it('computes hasCoupon correctly when coupon applied', function () {
            $coupon                = Coupon::factory()->create();
            $this->cart->coupon_id = $coupon->id;
            $this->cart->save();

            $component = Livewire::actingAs($this->user)
                ->test(CouponForm::class);

            expect($component->get('hasCoupon'))->toBeTrue();
        });

        it('computes discountAmount correctly', function () {
            $this->cart->discount = 2500;
            $this->cart->save();

            $component = Livewire::actingAs($this->user)
                ->test(CouponForm::class);

            expect($component->get('discountAmount'))->toBe(2500);
        });
    });

    describe('event handling', function () {
        it('refreshes on cart-updated event', function () {
            $coupon = Coupon::factory()->percentage(10)->create();

            $this->cart->coupon_id = $coupon->id;
            $this->cart->discount  = 1000;
            $this->cart->save();

            $component = Livewire::actingAs($this->user)
                ->test(CouponForm::class);

            // Change cart subtotal
            $this->cart->subtotal = 30000;
            $this->cart->save();

            $component->dispatch('cart-updated');

            // Discount should be recalculated (10% of 30000 = 3000)
            $this->cart->refresh();
            expect($this->cart->discount)->toBe(3000);
        });

        it('handles shipping-selected event', function () {
            $coupon = Coupon::factory()->percentage(10)->create();

            $this->cart->coupon_id     = $coupon->id;
            $this->cart->discount      = 2000;
            $this->cart->shipping_cost = 2500;
            $this->cart->save();

            Livewire::actingAs($this->user)
                ->test(CouponForm::class)
                ->dispatch('shipping-selected', code: 'pac');

            $this->cart->refresh();
            expect($this->cart->discount)->toBe(2000);
        });

        it('removes free shipping coupon when shipping is cleared', function () {
            $coupon = Coupon::factory()->freeShipping()->create();

            $this->cart->coupon_id     = $coupon->id;
            $this->cart->discount      = 2500;
            $this->cart->shipping_cost = 2500;
            $this->cart->save();

            Livewire::actingAs($this->user)
                ->test(CouponForm::class)
                ->dispatch('shipping-cleared')
                ->assertSet('couponCode', '')
                ->assertSee('cupom de frete gratis foi removido');

            $this->cart->refresh();
            expect($this->cart->coupon_id)->toBeNull();
        });
    });

    describe('coupon display', function () {
        it('displays percentage coupon correctly', function () {
            $coupon = Coupon::factory()
                ->withCode('PERCENT15')
                ->percentage(15)
                ->create();

            $this->cart->coupon_id = $coupon->id;
            $this->cart->discount  = 3000;
            $this->cart->save();

            Livewire::actingAs($this->user)
                ->test(CouponForm::class)
                ->assertSee('15% de desconto');
        });

        it('displays fixed coupon correctly', function () {
            $coupon = Coupon::factory()
                ->withCode('FIXED50')
                ->fixed(5000)
                ->create();

            $this->cart->coupon_id = $coupon->id;
            $this->cart->discount  = 5000;
            $this->cart->save();

            Livewire::actingAs($this->user)
                ->test(CouponForm::class)
                ->assertSee('R$ 50,00 de desconto');
        });

        it('displays free shipping coupon correctly', function () {
            $coupon = Coupon::factory()
                ->withCode('FREESHIP')
                ->freeShipping()
                ->create();

            $this->cart->coupon_id     = $coupon->id;
            $this->cart->shipping_cost = 2500;
            $this->cart->discount      = 2500;
            $this->cart->save();

            Livewire::actingAs($this->user)
                ->test(CouponForm::class)
                ->assertSee('Frete gratis');
        });
    });

    describe('guest users', function () {
        it('works for guest users with session cart', function () {
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            // Create a guest cart
            $component = Livewire::test(CouponForm::class);
            $sessionId = session()->getId();

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(sessionId: $sessionId);
            $cartService->addItem($cart, $product, 2);

            Coupon::factory()->withCode('GUESTCOUPON')->percentage(10)->active()->create();

            Livewire::test(CouponForm::class)
                ->set('couponCode', 'GUESTCOUPON')
                ->call('applyCoupon')
                ->assertSet('successMessage', 'Cupom aplicado com sucesso!');

            $cart->refresh();
            expect($cart->coupon_id)->not->toBeNull();
        });
    });
});
