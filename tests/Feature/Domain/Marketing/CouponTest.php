<?php

declare(strict_types = 1);

use App\Domain\Marketing\Enums\CouponType;
use App\Domain\Marketing\Models\{Coupon, CouponUsage};
use App\Models\User;

describe('Coupon Model', function () {
    describe('factory', function () {
        it('can create a coupon', function () {
            $coupon = Coupon::factory()->create();

            expect($coupon)
                ->toBeInstanceOf(Coupon::class)
                ->id->not->toBeNull()
                ->code->not->toBeNull();
        });

        it('can create a percentage coupon', function () {
            $coupon = Coupon::factory()->percentage(15)->create();

            expect($coupon)
                ->type->toBe(CouponType::Percentage)
                ->value->toBe(15);
        });

        it('can create a fixed coupon', function () {
            $coupon = Coupon::factory()->fixed(2000)->create();

            expect($coupon)
                ->type->toBe(CouponType::Fixed)
                ->value->toBe(2000);
        });

        it('can create a free shipping coupon', function () {
            $coupon = Coupon::factory()->freeShipping()->create();

            expect($coupon)
                ->type->toBe(CouponType::FreeShipping)
                ->value->toBeNull();
        });
    });

    describe('code normalization', function () {
        it('converts code to uppercase on create', function () {
            $coupon = Coupon::factory()->create(['code' => 'abc123']);

            expect($coupon->code)->toBe('ABC123');
        });

        it('converts code to uppercase on update', function () {
            $coupon = Coupon::factory()->create();
            $coupon->update(['code' => 'xyz789']);

            expect($coupon->fresh()->code)->toBe('XYZ789');
        });
    });

    describe('date range validation', function () {
        it('is within date range when no dates set', function () {
            $coupon = Coupon::factory()->create([
                'starts_at'  => null,
                'expires_at' => null,
            ]);

            expect($coupon->isWithinDateRange())->toBeTrue();
        });

        it('is within date range when dates are valid', function () {
            $coupon = Coupon::factory()->valid()->create();

            expect($coupon->isWithinDateRange())->toBeTrue();
        });

        it('is not within date range when expired', function () {
            $coupon = Coupon::factory()->expired()->create();

            expect($coupon->isWithinDateRange())->toBeFalse();
        });

        it('is not within date range when scheduled', function () {
            $coupon = Coupon::factory()->scheduled()->create();

            expect($coupon->isWithinDateRange())->toBeFalse();
        });
    });

    describe('usage limit validation', function () {
        it('has not reached usage limit when no limit set', function () {
            $coupon = Coupon::factory()->create(['usage_limit' => null]);

            expect($coupon->hasReachedUsageLimit())->toBeFalse();
        });

        it('has not reached usage limit when under limit', function () {
            $coupon = Coupon::factory()->create([
                'usage_limit' => 10,
                'times_used'  => 5,
            ]);

            expect($coupon->hasReachedUsageLimit())->toBeFalse();
        });

        it('has reached usage limit when at limit', function () {
            $coupon = Coupon::factory()->exhausted()->create();

            expect($coupon->hasReachedUsageLimit())->toBeTrue();
        });
    });

    describe('user usage limit validation', function () {
        it('has not reached user limit when no limit set', function () {
            $coupon = Coupon::factory()->create(['usage_limit_per_user' => null]);
            $user   = User::factory()->create();

            expect($coupon->hasUserReachedLimit($user->id))->toBeFalse();
        });

        it('has not reached user limit when no user provided', function () {
            $coupon = Coupon::factory()->withUsageLimitPerUser(1)->create();

            expect($coupon->hasUserReachedLimit(null))->toBeFalse();
        });

        it('has not reached user limit when under limit', function () {
            $coupon = Coupon::factory()->withUsageLimitPerUser(2)->create();
            $user   = User::factory()->create();

            CouponUsage::factory()->forCoupon($coupon)->forUser($user)->create();

            expect($coupon->hasUserReachedLimit($user->id))->toBeFalse();
        });

        it('has reached user limit when at limit', function () {
            $coupon = Coupon::factory()->withUsageLimitPerUser(1)->create();
            $user   = User::factory()->create();

            CouponUsage::factory()->forCoupon($coupon)->forUser($user)->create();

            expect($coupon->hasUserReachedLimit($user->id))->toBeTrue();
        });
    });

    describe('minimum order validation', function () {
        it('meets minimum order when no minimum set', function () {
            $coupon = Coupon::factory()->create(['minimum_order_value' => null]);

            expect($coupon->meetsMinimumOrderValue(1000))->toBeTrue();
        });

        it('meets minimum order when order value is at minimum', function () {
            $coupon = Coupon::factory()->withMinimumOrder(5000)->create();

            expect($coupon->meetsMinimumOrderValue(5000))->toBeTrue();
        });

        it('meets minimum order when order value is above minimum', function () {
            $coupon = Coupon::factory()->withMinimumOrder(5000)->create();

            expect($coupon->meetsMinimumOrderValue(10000))->toBeTrue();
        });

        it('does not meet minimum order when order value is below minimum', function () {
            $coupon = Coupon::factory()->withMinimumOrder(5000)->create();

            expect($coupon->meetsMinimumOrderValue(2500))->toBeFalse();
        });
    });

    describe('canBeApplied', function () {
        it('can be applied when all conditions are met', function () {
            $coupon = Coupon::factory()->valid()->create();

            expect($coupon->canBeApplied(10000))->toBeTrue();
        });

        it('cannot be applied when inactive', function () {
            $coupon = Coupon::factory()->inactive()->create();

            expect($coupon->canBeApplied(10000))->toBeFalse();
        });

        it('cannot be applied when expired', function () {
            $coupon = Coupon::factory()->active()->expired()->create();

            expect($coupon->canBeApplied(10000))->toBeFalse();
        });

        it('cannot be applied when usage limit reached', function () {
            $coupon = Coupon::factory()->active()->exhausted()->create();

            expect($coupon->canBeApplied(10000))->toBeFalse();
        });

        it('cannot be applied when minimum order not met', function () {
            $coupon = Coupon::factory()->active()->withMinimumOrder(10000)->create();

            expect($coupon->canBeApplied(5000))->toBeFalse();
        });

        it('cannot be applied when user limit reached', function () {
            $coupon = Coupon::factory()->active()->withUsageLimitPerUser(1)->create();
            $user   = User::factory()->create();

            CouponUsage::factory()->forCoupon($coupon)->forUser($user)->create();

            expect($coupon->canBeApplied(10000, $user->id))->toBeFalse();
        });
    });

    describe('discount calculation', function () {
        it('calculates percentage discount', function () {
            $coupon = Coupon::factory()->percentage(10)->create();

            expect($coupon->calculateDiscount(10000))->toBe(1000);
        });

        it('calculates fixed discount', function () {
            $coupon = Coupon::factory()->fixed(1500)->create();

            expect($coupon->calculateDiscount(10000))->toBe(1500);
        });

        it('calculates free shipping discount', function () {
            $coupon = Coupon::factory()->freeShipping()->create();

            expect($coupon->calculateDiscount(10000, 2500))->toBe(2500);
        });

        it('applies maximum discount for percentage', function () {
            $coupon = Coupon::factory()
                ->percentage(20)
                ->withMaximumDiscount(1000)
                ->create();

            expect($coupon->calculateDiscount(10000))->toBe(1000);
        });

        it('does not exceed order value for fixed discount', function () {
            $coupon = Coupon::factory()->fixed(5000)->create();

            expect($coupon->calculateDiscount(2500))->toBe(2500);
        });

        it('does not exceed order value for percentage discount', function () {
            $coupon = Coupon::factory()->percentage(100)->create();

            expect($coupon->calculateDiscount(5000))->toBe(5000);
        });
    });

    describe('usage tracking', function () {
        it('increments usage counter', function () {
            $coupon = Coupon::factory()->create(['times_used' => 5]);

            $coupon->incrementUsage();

            expect($coupon->fresh()->times_used)->toBe(6);
        });

        it('decrements usage counter', function () {
            $coupon = Coupon::factory()->create(['times_used' => 5]);

            $coupon->decrementUsage();

            expect($coupon->fresh()->times_used)->toBe(4);
        });

        it('does not decrement below zero', function () {
            $coupon = Coupon::factory()->create(['times_used' => 0]);

            $coupon->decrementUsage();

            expect($coupon->fresh()->times_used)->toBe(0);
        });
    });

    describe('relations', function () {
        it('has many usages', function () {
            $coupon = Coupon::factory()->create();

            CouponUsage::factory()->count(3)->forCoupon($coupon)->create();

            expect($coupon->usages)->toHaveCount(3);
        });

        it('belongs to creator', function () {
            $user   = User::factory()->create();
            $coupon = Coupon::factory()->createdBy($user)->create();

            expect($coupon->creator)
                ->toBeInstanceOf(User::class)
                ->id->toBe($user->id);
        });
    });

    describe('scopes', function () {
        it('filters active coupons', function () {
            Coupon::factory()->active()->count(2)->create();
            Coupon::factory()->inactive()->count(3)->create();

            expect(Coupon::active()->count())->toBe(2);
        });

        it('filters valid coupons', function () {
            Coupon::factory()->valid()->count(2)->create();
            Coupon::factory()->expired()->count(3)->create();

            expect(Coupon::valid()->count())->toBe(2);
        });

        it('finds coupon by code', function () {
            $coupon = Coupon::factory()->withCode('TEST10')->create();
            Coupon::factory()->count(3)->create();

            $found = Coupon::byCode('test10')->first();

            expect($found)
                ->not->toBeNull()
                ->id->toBe($coupon->id);
        });
    });

    describe('attributes', function () {
        it('converts fixed value to reais', function () {
            $coupon = Coupon::factory()->fixed(1500)->create();

            expect($coupon->value_in_reais)->toBe(15.0);
        });

        it('returns null for percentage value in reais', function () {
            $coupon = Coupon::factory()->percentage(15)->create();

            expect($coupon->value_in_reais)->toBeNull();
        });

        it('converts minimum order value to reais', function () {
            $coupon = Coupon::factory()->withMinimumOrder(10000)->create();

            expect($coupon->minimum_order_value_in_reais)->toBe(100.0);
        });

        it('converts maximum discount to reais', function () {
            $coupon = Coupon::factory()->withMaximumDiscount(5000)->create();

            expect($coupon->maximum_discount_in_reais)->toBe(50.0);
        });
    });
});
