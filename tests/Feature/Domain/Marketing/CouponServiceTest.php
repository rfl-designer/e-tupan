<?php

declare(strict_types = 1);

use App\Domain\Cart\Models\Cart;
use App\Domain\Cart\Services\CartService;
use App\Domain\Catalog\Models\Product;
use App\Domain\Marketing\Exceptions\CouponException;
use App\Domain\Marketing\Models\{Coupon, CouponUsage};
use App\Domain\Marketing\Services\CouponService;
use App\Models\User;

describe('CouponService', function () {
    beforeEach(function () {
        $this->service = new CouponService();
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

    describe('findByCode', function () {
        it('finds a coupon by code', function () {
            $coupon = Coupon::factory()->withCode('TEST10')->create();

            $found = $this->service->findByCode('TEST10');

            expect($found)
                ->not->toBeNull()
                ->id->toBe($coupon->id);
        });

        it('finds coupon case-insensitively', function () {
            Coupon::factory()->withCode('TEST10')->create();

            $found = $this->service->findByCode('test10');

            expect($found)->not->toBeNull();
        });

        it('returns null for non-existent code', function () {
            $found = $this->service->findByCode('NONEXISTENT');

            expect($found)->toBeNull();
        });
    });

    describe('validate', function () {
        it('validates a valid coupon', function () {
            $coupon = Coupon::factory()
                ->withCode('VALID10')
                ->percentage(10)
                ->valid()
                ->create();

            $validated = $this->service->validate('VALID10', $this->cart, $this->user->id);

            expect($validated->id)->toBe($coupon->id);
        });

        it('throws exception for non-existent coupon', function () {
            $this->service->validate('NONEXISTENT', $this->cart, $this->user->id);
        })->throws(CouponException::class, 'nao encontrado');

        it('throws exception for inactive coupon', function () {
            Coupon::factory()
                ->withCode('INACTIVE')
                ->inactive()
                ->create();

            $this->service->validate('INACTIVE', $this->cart, $this->user->id);
        })->throws(CouponException::class, 'inativo');

        it('throws exception for expired coupon', function () {
            Coupon::factory()
                ->withCode('EXPIRED')
                ->active()
                ->expired()
                ->create();

            $this->service->validate('EXPIRED', $this->cart, $this->user->id);
        })->throws(CouponException::class, 'expirou');

        it('throws exception for not started coupon', function () {
            Coupon::factory()
                ->withCode('FUTURE')
                ->active()
                ->scheduled()
                ->create();

            $this->service->validate('FUTURE', $this->cart, $this->user->id);
        })->throws(CouponException::class, 'nao esta valido');

        it('throws exception when usage limit reached', function () {
            Coupon::factory()
                ->withCode('EXHAUSTED')
                ->active()
                ->exhausted()
                ->create();

            $this->service->validate('EXHAUSTED', $this->cart, $this->user->id);
        })->throws(CouponException::class, 'limite de uso');

        it('throws exception when user limit reached', function () {
            $coupon = Coupon::factory()
                ->withCode('USERLIMIT')
                ->active()
                ->withUsageLimitPerUser(1)
                ->create();

            CouponUsage::factory()
                ->forCoupon($coupon)
                ->forUser($this->user)
                ->create();

            $this->service->validate('USERLIMIT', $this->cart, $this->user->id);
        })->throws(CouponException::class, 'numero maximo de vezes');

        it('throws exception when minimum order not met', function () {
            Coupon::factory()
                ->withCode('MINIMUM')
                ->active()
                ->withMinimumOrder(50000) // R$ 500
                ->create();

            $this->service->validate('MINIMUM', $this->cart, $this->user->id);
        })->throws(CouponException::class, 'pedido minimo');

        it('throws exception for free shipping coupon without shipping', function () {
            Coupon::factory()
                ->withCode('FREESHIP')
                ->active()
                ->freeShipping()
                ->create();

            $this->service->validate('FREESHIP', $this->cart, $this->user->id);
        })->throws(CouponException::class, 'frete seja calculado');

        it('validates free shipping coupon when shipping is set', function () {
            $coupon = Coupon::factory()
                ->withCode('FREESHIP')
                ->active()
                ->freeShipping()
                ->create();

            $this->cart->shipping_cost = 2500;
            $this->cart->save();

            $validated = $this->service->validate('FREESHIP', $this->cart, $this->user->id);

            expect($validated->id)->toBe($coupon->id);
        });
    });

    describe('apply', function () {
        it('applies percentage coupon to cart', function () {
            Coupon::factory()
                ->withCode('PERCENT10')
                ->percentage(10)
                ->active()
                ->create();

            $cart = $this->service->apply('PERCENT10', $this->cart, $this->user->id);

            expect($cart)
                ->coupon_id->not->toBeNull()
                ->discount->toBe(2000); // 10% of 20000
        });

        it('applies fixed coupon to cart', function () {
            Coupon::factory()
                ->withCode('FIXED1000')
                ->fixed(1000) // R$ 10
                ->active()
                ->create();

            $cart = $this->service->apply('FIXED1000', $this->cart, $this->user->id);

            expect($cart)
                ->coupon_id->not->toBeNull()
                ->discount->toBe(1000);
        });

        it('applies free shipping coupon to cart', function () {
            Coupon::factory()
                ->withCode('FREESHIP')
                ->freeShipping()
                ->active()
                ->create();

            $this->cart->shipping_cost = 2500;
            $this->cart->save();

            $cart = $this->service->apply('FREESHIP', $this->cart, $this->user->id);

            expect($cart)
                ->coupon_id->not->toBeNull()
                ->discount->toBe(2500);
        });

        it('respects maximum discount', function () {
            Coupon::factory()
                ->withCode('MAXDISC')
                ->percentage(50)
                ->withMaximumDiscount(1000) // Max R$ 10
                ->active()
                ->create();

            $cart = $this->service->apply('MAXDISC', $this->cart, $this->user->id);

            expect($cart->discount)->toBe(1000);
        });

        it('throws exception when cart already has coupon', function () {
            $coupon = Coupon::factory()->withCode('FIRST')->active()->create();

            $this->cart->coupon_id = $coupon->id;
            $this->cart->save();

            Coupon::factory()->withCode('SECOND')->active()->create();

            $this->service->apply('SECOND', $this->cart, $this->user->id);
        })->throws(CouponException::class, 'ja esta aplicado');

        it('updates cart total correctly', function () {
            Coupon::factory()
                ->withCode('DISCOUNT')
                ->fixed(5000)
                ->active()
                ->create();

            $this->cart->shipping_cost = 2500;
            $this->cart->save();

            $subtotalBefore = $this->cart->subtotal;

            $cart = $this->service->apply('DISCOUNT', $this->cart, $this->user->id);

            // Total = subtotal - discount + shipping
            expect($cart->total)->toBe($subtotalBefore - 5000 + 2500);
        });
    });

    describe('remove', function () {
        it('removes coupon from cart', function () {
            $coupon = Coupon::factory()->percentage(10)->create();

            $this->cart->coupon_id = $coupon->id;
            $this->cart->discount  = 2000;
            $this->cart->save();

            $cart = $this->service->remove($this->cart);

            expect($cart)
                ->coupon_id->toBeNull()
                ->discount->toBe(0);
        });

        it('throws exception when no coupon applied', function () {
            $this->service->remove($this->cart);
        })->throws(CouponException::class, 'Nenhum cupom');

        it('recalculates cart total after removal', function () {
            $coupon = Coupon::factory()->fixed(5000)->create();

            $this->cart->coupon_id     = $coupon->id;
            $this->cart->discount      = 5000;
            $this->cart->shipping_cost = 2500;
            $this->cart->calculateTotals();
            $this->cart->save();

            $cart = $this->service->remove($this->cart);

            // Total = subtotal - 0 + shipping
            expect($cart->total)->toBe($cart->subtotal + 2500);
        });
    });

    describe('calculateDiscount', function () {
        it('calculates percentage discount', function () {
            $coupon = Coupon::factory()->percentage(15)->make();

            $discount = $this->service->calculateDiscount($coupon, $this->cart);

            expect($discount)->toBe(3000); // 15% of 20000
        });

        it('calculates fixed discount', function () {
            $coupon = Coupon::factory()->fixed(2500)->make();

            $discount = $this->service->calculateDiscount($coupon, $this->cart);

            expect($discount)->toBe(2500);
        });

        it('calculates free shipping discount', function () {
            $coupon = Coupon::factory()->freeShipping()->make();

            $this->cart->shipping_cost = 3500;

            $discount = $this->service->calculateDiscount($coupon, $this->cart);

            expect($discount)->toBe(3500);
        });

        it('limits fixed discount to order value', function () {
            $coupon = Coupon::factory()->fixed(50000)->make();

            $discount = $this->service->calculateDiscount($coupon, $this->cart);

            expect($discount)->toBe($this->cart->subtotal);
        });
    });

    describe('recordUsage', function () {
        it('creates usage record', function () {
            $coupon = Coupon::factory()->create();

            $usage = $this->service->recordUsage($coupon, $this->user->id, null, 2000);

            expect($usage)
                ->toBeInstanceOf(CouponUsage::class)
                ->coupon_id->toBe($coupon->id)
                ->user_id->toBe($this->user->id)
                ->discount_amount->toBe(2000);
        });

        it('increments coupon times_used', function () {
            $coupon = Coupon::factory()->create(['times_used' => 5]);

            $this->service->recordUsage($coupon, $this->user->id, null, 2000);

            expect($coupon->fresh()->times_used)->toBe(6);
        });

        it('can record usage without user', function () {
            $coupon = Coupon::factory()->create();

            $usage = $this->service->recordUsage($coupon, null, null, 2000);

            expect($usage->user_id)->toBeNull();
        });
    });

    describe('revertUsage', function () {
        it('deletes usage record', function () {
            $coupon = Coupon::factory()->create();
            $usage  = CouponUsage::factory()->forCoupon($coupon)->create();

            $this->service->revertUsage($usage);

            expect(CouponUsage::find($usage->id))->toBeNull();
        });

        it('decrements coupon times_used', function () {
            $coupon = Coupon::factory()->create(['times_used' => 5]);
            $usage  = CouponUsage::factory()->forCoupon($coupon)->create();

            $this->service->revertUsage($usage);

            expect($coupon->fresh()->times_used)->toBe(4);
        });
    });

    describe('recalculateDiscount', function () {
        it('recalculates discount when cart changes', function () {
            $coupon = Coupon::factory()->percentage(10)->create();

            $this->cart->coupon_id = $coupon->id;
            $this->cart->save();

            // Simulate cart subtotal change
            $this->cart->subtotal = 30000;

            $cart = $this->service->recalculateDiscount($this->cart);

            expect($cart->discount)->toBe(3000); // 10% of 30000
        });

        it('removes coupon if no longer valid', function () {
            $coupon = Coupon::factory()
                ->percentage(10)
                ->withMinimumOrder(50000)
                ->create();

            $this->cart->coupon_id = $coupon->id;
            $this->cart->discount  = 2000;
            $this->cart->save();

            // Cart subtotal (20000) is below minimum (50000)
            $cart = $this->service->recalculateDiscount($this->cart);

            expect($cart)
                ->coupon_id->toBeNull()
                ->discount->toBe(0);
        });

        it('does nothing when no coupon applied', function () {
            $originalTotal = $this->cart->total;

            $cart = $this->service->recalculateDiscount($this->cart);

            expect($cart->total)->toBe($originalTotal);
        });

        it('removes coupon if coupon is deleted', function () {
            $couponId = Coupon::factory()->create()->id;

            $this->cart->coupon_id = $couponId;
            $this->cart->discount  = 2000;
            $this->cart->save();

            // Delete the coupon
            Coupon::find($couponId)->forceDelete();

            $cart = $this->service->recalculateDiscount($this->cart);

            expect($cart)
                ->coupon_id->toBeNull()
                ->discount->toBe(0);
        });
    });

    describe('getActiveCoupons', function () {
        it('returns active and valid coupons', function () {
            Coupon::factory()->active()->valid()->count(3)->create();
            Coupon::factory()->inactive()->count(2)->create();
            Coupon::factory()->active()->expired()->count(2)->create();

            $coupons = $this->service->getActiveCoupons();

            expect($coupons)->toHaveCount(3);
        });

        it('orders by created_at desc', function () {
            $oldest = Coupon::factory()->active()->create(['created_at' => now()->subDays(3)]);
            $newest = Coupon::factory()->active()->create(['created_at' => now()]);
            $middle = Coupon::factory()->active()->create(['created_at' => now()->subDay()]);

            $coupons = $this->service->getActiveCoupons();

            expect($coupons->first()->id)->toBe($newest->id)
                ->and($coupons->last()->id)->toBe($oldest->id);
        });
    });
});
