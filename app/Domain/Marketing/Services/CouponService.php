<?php

declare(strict_types = 1);

namespace App\Domain\Marketing\Services;

use App\Domain\Cart\Models\Cart;
use App\Domain\Marketing\Enums\CouponType;
use App\Domain\Marketing\Exceptions\CouponException;
use App\Domain\Marketing\Models\{Coupon, CouponUsage};
use Illuminate\Support\Facades\DB;

class CouponService
{
    /**
     * Find a coupon by its code.
     */
    public function findByCode(string $code): ?Coupon
    {
        return Coupon::query()
            ->byCode($code)
            ->first();
    }

    /**
     * Validate a coupon for a cart.
     *
     * @throws CouponException
     */
    public function validate(string $code, Cart $cart, ?int $userId = null): Coupon
    {
        $coupon = $this->findByCode($code);

        if ($coupon === null) {
            throw CouponException::notFound($code);
        }

        $this->validateCoupon($coupon, $cart, $userId);

        return $coupon;
    }

    /**
     * Validate a coupon instance for a cart.
     *
     * @throws CouponException
     */
    public function validateCoupon(Coupon $coupon, Cart $cart, ?int $userId = null): void
    {
        // Check if coupon is active
        if (!$coupon->is_active) {
            throw CouponException::inactive();
        }

        // Check date range
        $now = now();

        if ($coupon->starts_at !== null && $now->lt($coupon->starts_at)) {
            throw CouponException::notStarted();
        }

        if ($coupon->expires_at !== null && $now->gt($coupon->expires_at)) {
            throw CouponException::expired();
        }

        // Check usage limit
        if ($coupon->hasReachedUsageLimit()) {
            throw CouponException::usageLimitReached();
        }

        // Check user limit
        if ($coupon->hasUserReachedLimit($userId)) {
            throw CouponException::userLimitReached();
        }

        // Check minimum order value
        if (!$coupon->meetsMinimumOrderValue($cart->subtotal)) {
            throw CouponException::minimumOrderNotMet($coupon->minimum_order_value ?? 0);
        }

        // For free shipping coupons, check if shipping is calculated
        if ($coupon->type === CouponType::FreeShipping && $cart->shipping_cost === null) {
            throw CouponException::requiresShipping();
        }
    }

    /**
     * Apply a coupon to a cart.
     *
     * @throws CouponException
     */
    public function apply(string $code, Cart $cart, ?int $userId = null): Cart
    {
        // Check if cart already has a coupon
        if ($cart->coupon_id !== null) {
            throw CouponException::alreadyApplied();
        }

        $coupon = $this->validate($code, $cart, $userId);

        return DB::transaction(function () use ($cart, $coupon) {
            // Calculate and apply discount
            $discount = $this->calculateDiscount($coupon, $cart);

            $cart->coupon_id = $coupon->id;
            $cart->discount  = $discount;
            $cart->calculateTotals();
            $cart->save();

            return $cart;
        });
    }

    /**
     * Remove a coupon from a cart.
     *
     * @throws CouponException
     */
    public function remove(Cart $cart): Cart
    {
        if ($cart->coupon_id === null) {
            throw CouponException::noCouponApplied();
        }

        return DB::transaction(function () use ($cart) {
            $cart->coupon_id = null;
            $cart->discount  = 0;
            $cart->calculateTotals();
            $cart->save();

            return $cart;
        });
    }

    /**
     * Calculate the discount for a coupon on a cart.
     */
    public function calculateDiscount(Coupon $coupon, Cart $cart): int
    {
        return $coupon->calculateDiscount(
            orderValue: $cart->subtotal,
            shippingCost: $cart->shipping_cost,
        );
    }

    /**
     * Record coupon usage when an order is placed.
     */
    public function recordUsage(Coupon $coupon, ?int $userId, ?string $orderId, int $discountAmount): CouponUsage
    {
        return DB::transaction(function () use ($coupon, $userId, $orderId, $discountAmount) {
            // Increment coupon usage counter
            $coupon->incrementUsage();

            // Create usage record
            return CouponUsage::create([
                'coupon_id'       => $coupon->id,
                'user_id'         => $userId,
                'order_id'        => $orderId,
                'discount_amount' => $discountAmount,
            ]);
        });
    }

    /**
     * Revert coupon usage (e.g., when an order is cancelled).
     */
    public function revertUsage(CouponUsage $usage): void
    {
        DB::transaction(function () use ($usage) {
            // Decrement coupon usage counter
            $usage->coupon->decrementUsage();

            // Delete usage record
            $usage->delete();
        });
    }

    /**
     * Recalculate and update discount for a cart.
     * Used when cart subtotal changes.
     */
    public function recalculateDiscount(Cart $cart): Cart
    {
        if ($cart->coupon_id === null) {
            return $cart;
        }

        $coupon = $cart->coupon;

        if ($coupon === null) {
            $cart->coupon_id = null;
            $cart->discount  = 0;
            $cart->calculateTotals();
            $cart->save();

            return $cart;
        }

        // Check if coupon is still valid
        try {
            $this->validateCoupon($coupon, $cart, $cart->user_id);
        } catch (CouponException) {
            // Remove invalid coupon
            $cart->coupon_id = null;
            $cart->discount  = 0;
            $cart->calculateTotals();
            $cart->save();

            return $cart;
        }

        // Recalculate discount
        $discount       = $this->calculateDiscount($coupon, $cart);
        $cart->discount = $discount;
        $cart->calculateTotals();
        $cart->save();

        return $cart;
    }

    /**
     * Get active coupons.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Coupon>
     */
    public function getActiveCoupons(): \Illuminate\Database\Eloquent\Collection
    {
        return Coupon::query()
            ->active()
            ->valid()
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
