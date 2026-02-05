<?php

declare(strict_types=1);

use App\Domain\Admin\Mail\TestEmailConfiguration;
use App\Domain\Admin\Notifications\AdminInvitation;
use App\Domain\Admin\Notifications\AdminResetPasswordNotification;
use App\Domain\Catalog\Models\Product;
use App\Domain\Checkout\Models\Order;
use App\Domain\Checkout\Notifications\OrderConfirmationNotification;
use App\Domain\Customer\Notifications\WelcomeNotification;
use App\Domain\Inventory\Notifications\LowStockNotification;
use App\Domain\Shipping\Models\Shipment;
use App\Domain\Shipping\Notifications\ShipmentShippedNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

describe('Email Queue Configuration', function () {
    it('has a valid default queue connection configured', function () {
        $defaultConnection = config('queue.default');

        expect(config('queue.connections'))->toHaveKey($defaultConnection);
    });
});

describe('TestEmailConfiguration Mailable Queue', function () {
    it('is properly configured for queueing', function () {
        $mailable = new TestEmailConfiguration('Test Store', 'smtp');

        expect($mailable)
            ->toBeInstanceOf(ShouldQueue::class)
            ->and($mailable->queue)->toBe('emails')
            ->and($mailable->tries)->toBe(3)
            ->and($mailable->timeout)->toBe(30)
            ->and($mailable->backoff())->toBe([10, 60, 300]);
    });

    it('is queued when sent via Mail facade', function () {
        Mail::fake();

        Mail::to('test@example.com')->send(new TestEmailConfiguration('Test Store', 'smtp'));

        Mail::assertQueued(TestEmailConfiguration::class);
    });
});

describe('Notification Queue Configuration', function () {
    dataset('notifications', function () {
        yield 'AdminInvitation' => [fn () => new AdminInvitation('test-token')];
        yield 'AdminResetPasswordNotification' => [fn () => new AdminResetPasswordNotification('test-token')];
        yield 'OrderConfirmationNotification' => [fn () => new OrderConfirmationNotification(Order::factory()->create())];
        yield 'WelcomeNotification' => [fn () => new WelcomeNotification(User::factory()->create())];
        yield 'LowStockNotification' => [fn () => new LowStockNotification(Product::factory()->count(2)->create())];
        yield 'ShipmentShippedNotification' => [fn () => new ShipmentShippedNotification(Shipment::factory()->create())];
    });

    it('implements ShouldQueue interface', function (Closure $createNotification) {
        expect($createNotification())->toBeInstanceOf(ShouldQueue::class);
    })->with('notifications');

    it('uses the emails queue', function (Closure $createNotification) {
        expect($createNotification()->queue)->toBe('emails');
    })->with('notifications');

    it('has 3 max tries', function (Closure $createNotification) {
        expect($createNotification()->tries)->toBe(3);
    })->with('notifications');

    it('has 30 seconds timeout', function (Closure $createNotification) {
        expect($createNotification()->timeout)->toBe(30);
    })->with('notifications');

    it('has exponential backoff of 10s, 60s, 300s', function (Closure $createNotification) {
        expect($createNotification()->backoff())->toBe([10, 60, 300]);
    })->with('notifications');
});
