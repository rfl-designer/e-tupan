<?php

declare(strict_types = 1);

use App\Domain\Marketing\Models\{Coupon, CouponUsage};
use App\Models\User;

describe('CouponUsage Model', function () {
    describe('factory', function () {
        it('can create a coupon usage', function () {
            $usage = CouponUsage::factory()->create();

            expect($usage)
                ->toBeInstanceOf(CouponUsage::class)
                ->id->not->toBeNull();
        });

        it('can create usage for a specific user', function () {
            $user  = User::factory()->create();
            $usage = CouponUsage::factory()->forUser($user)->create();

            expect($usage->user_id)->toBe($user->id);
        });

        it('can create usage for a specific coupon', function () {
            $coupon = Coupon::factory()->create();
            $usage  = CouponUsage::factory()->forCoupon($coupon)->create();

            expect($usage->coupon_id)->toBe($coupon->id);
        });

        it('can create usage with specific discount', function () {
            $usage = CouponUsage::factory()->withDiscount(1500)->create();

            expect($usage->discount_amount)->toBe(1500);
        });
    });

    describe('relations', function () {
        it('belongs to a coupon', function () {
            $coupon = Coupon::factory()->create();
            $usage  = CouponUsage::factory()->forCoupon($coupon)->create();

            expect($usage->coupon)
                ->toBeInstanceOf(Coupon::class)
                ->id->toBe($coupon->id);
        });

        it('belongs to a user', function () {
            $user  = User::factory()->create();
            $usage = CouponUsage::factory()->forUser($user)->create();

            expect($usage->user)
                ->toBeInstanceOf(User::class)
                ->id->toBe($user->id);
        });

        it('can have null user for guest usage', function () {
            $usage = CouponUsage::factory()->create(['user_id' => null]);

            expect($usage->user)->toBeNull();
        });
    });

    describe('casts', function () {
        it('casts discount_amount to integer', function () {
            $usage = CouponUsage::factory()->create(['discount_amount' => 1500]);

            expect($usage->discount_amount)->toBeInt()->toBe(1500);
        });
    });
});
