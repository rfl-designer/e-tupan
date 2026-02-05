<?php

declare(strict_types = 1);

use App\Domain\Checkout\Enums\{OrderStatus, PaymentStatus};
use App\Domain\Checkout\Models\{Order, OrderItem, Payment};
use App\Models\User;

describe('Order Model', function () {
    it('generates order number on creation', function () {
        $order = Order::factory()->create();

        expect($order->order_number)->toStartWith('ORD-')
            ->and(strlen($order->order_number))->toBe(10);
    });

    it('has uuid as primary key', function () {
        $order = Order::factory()->create();

        expect($order->id)->toBeString()
            ->and(strlen($order->id))->toBe(36);
    });

    it('belongs to a user', function () {
        $user  = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        expect($order->user)->toBeInstanceOf(User::class)
            ->and($order->user->id)->toBe($user->id);
    });

    it('can be a guest order', function () {
        $order = Order::factory()->guest()->create();

        expect($order->isGuest())->toBeTrue()
            ->and($order->user_id)->toBeNull()
            ->and($order->guest_email)->not->toBeNull()
            ->and($order->guest_name)->not->toBeNull();
    });

    it('has many items', function () {
        $order = Order::factory()->create();
        OrderItem::factory()->count(3)->create(['order_id' => $order->id]);

        expect($order->items)->toHaveCount(3);
    });

    it('has many payments', function () {
        $order = Order::factory()->create();
        Payment::factory()->count(2)->create(['order_id' => $order->id]);

        expect($order->payments)->toHaveCount(2);
    });

    it('casts status to OrderStatus enum', function () {
        $order = Order::factory()->create(['status' => 'pending']);

        expect($order->status)->toBe(OrderStatus::Pending)
            ->and($order->status)->toBeInstanceOf(OrderStatus::class);
    });

    it('casts payment_status to PaymentStatus enum', function () {
        $order = Order::factory()->create(['payment_status' => 'approved']);

        expect($order->payment_status)->toBe(PaymentStatus::Approved)
            ->and($order->payment_status)->toBeInstanceOf(PaymentStatus::class);
    });

    it('calculates price attributes in reais', function () {
        $order = Order::factory()->create([
            'subtotal'      => 10000,
            'shipping_cost' => 1500,
            'discount'      => 500,
            'total'         => 11000,
        ]);

        expect($order->subtotal_in_reais)->toBe(100.00)
            ->and($order->shipping_cost_in_reais)->toBe(15.00)
            ->and($order->discount_in_reais)->toBe(5.00)
            ->and($order->total_in_reais)->toBe(110.00);
    });

    it('returns customer email from user or guest', function () {
        $user       = User::factory()->create(['email' => 'user@test.com']);
        $userOrder  = Order::factory()->create(['user_id' => $user->id]);
        $guestOrder = Order::factory()->guest()->create(['guest_email' => 'guest@test.com']);

        expect($userOrder->customer_email)->toBe('user@test.com')
            ->and($guestOrder->customer_email)->toBe('guest@test.com');
    });

    it('returns customer name from user or guest', function () {
        $user       = User::factory()->create(['name' => 'John User']);
        $userOrder  = Order::factory()->create(['user_id' => $user->id]);
        $guestOrder = Order::factory()->guest()->create(['guest_name' => 'Jane Guest']);

        expect($userOrder->customer_name)->toBe('John User')
            ->and($guestOrder->customer_name)->toBe('Jane Guest');
    });

    it('checks if order is paid', function () {
        $paidOrder   = Order::factory()->paid()->create();
        $unpaidOrder = Order::factory()->create();

        expect($paidOrder->isPaid())->toBeTrue()
            ->and($unpaidOrder->isPaid())->toBeFalse();
    });

    it('checks if order is completed', function () {
        $completedOrder = Order::factory()->completed()->create();
        $pendingOrder   = Order::factory()->create();

        expect($completedOrder->isCompleted())->toBeTrue()
            ->and($pendingOrder->isCompleted())->toBeFalse();
    });

    it('checks if order can be cancelled', function () {
        $pendingOrder   = Order::factory()->pending()->create();
        $completedOrder = Order::factory()->completed()->create();

        expect($pendingOrder->canBeCancelled())->toBeTrue()
            ->and($completedOrder->canBeCancelled())->toBeFalse();
    });

    it('can mark order as paid', function () {
        $order = Order::factory()->create();
        $order->markAsPaid();

        expect($order->fresh()->isPaid())->toBeTrue()
            ->and($order->fresh()->paid_at)->not->toBeNull();
    });

    it('can cancel order', function () {
        $order = Order::factory()->create();
        $order->cancel();

        expect($order->fresh()->isCancelled())->toBeTrue()
            ->and($order->fresh()->cancelled_at)->not->toBeNull();
    });

    it('can mark order as shipped', function () {
        $order = Order::factory()->paid()->create();
        $order->markAsShipped('TRACK123');

        expect($order->fresh()->shipped_at)->not->toBeNull()
            ->and($order->fresh()->tracking_number)->toBe('TRACK123');
    });

    it('formats shipping address correctly', function () {
        $order = Order::factory()->create([
            'shipping_street'       => 'Rua das Flores',
            'shipping_number'       => '123',
            'shipping_complement'   => 'Apto 45',
            'shipping_neighborhood' => 'Centro',
            'shipping_city'         => 'Sao Paulo',
            'shipping_state'        => 'SP',
            'shipping_zipcode'      => '01234-567',
        ]);

        expect($order->formatted_shipping_address)
            ->toContain('Rua das Flores')
            ->toContain('123')
            ->toContain('Apto 45')
            ->toContain('Centro')
            ->toContain('Sao Paulo/SP');
    });

    it('scopes orders by user', function () {
        $user = User::factory()->create();
        Order::factory()->count(2)->create(['user_id' => $user->id]);
        Order::factory()->count(3)->create();

        $userOrders = Order::forUser($user->id)->get();

        expect($userOrders)->toHaveCount(2);
    });

    it('scopes orders by payment status', function () {
        Order::factory()->count(2)->paid()->create();
        Order::factory()->count(3)->create();

        $paidOrders = Order::paid()->get();

        expect($paidOrders)->toHaveCount(2);
    });
});
