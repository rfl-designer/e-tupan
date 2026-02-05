<?php

declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use App\Domain\Catalog\Models\Product;
use App\Domain\Inventory\Jobs\SendLowStockAlertsJob;
use App\Domain\Inventory\Notifications\LowStockNotification;
use Illuminate\Support\Facades\{Notification, Queue};

beforeEach(function () {
    $this->admin = Admin::factory()->master()->withTwoFactor()->create();
});

describe('LowStockNotification', function () {
    it('includes products with low stock in the notification', function () {
        $lowStockProducts = Product::factory()->count(3)->create([
            'stock_quantity'      => 3,
            'low_stock_threshold' => 5,
            'manage_stock'        => true,
            'notify_low_stock'    => true,
        ]);

        $notification = new LowStockNotification($lowStockProducts);
        $mail         = $notification->toMail($this->admin);

        expect($mail->subject)->toContain('Estoque Baixo')
            ->and($mail)->not->toBeNull();
    });

    it('formats product list correctly in mail', function () {
        $product = Product::factory()->create([
            'name'                => 'Test Product',
            'sku'                 => 'TST-001',
            'stock_quantity'      => 2,
            'low_stock_threshold' => 5,
            'manage_stock'        => true,
            'notify_low_stock'    => true,
        ]);

        $notification = new LowStockNotification(collect([$product]));
        $mail         = $notification->toMail($this->admin);

        // Check that the notification contains the product info in the lines
        $lines          = collect($mail->introLines);
        $hasProductName = $lines->contains(fn ($line) => str_contains($line, 'Test Product'));
        $hasProductSku  = $lines->contains(fn ($line) => str_contains($line, 'TST-001'));

        expect($hasProductName)->toBeTrue()
            ->and($hasProductSku)->toBeTrue();
    });
});

describe('SendLowStockAlertsJob', function () {
    it('dispatches notification when products are below threshold', function () {
        Notification::fake();

        config(['inventory.notification_recipients' => [$this->admin->email]]);
        config(['inventory.send_low_stock_notifications' => true]);

        Product::factory()->count(2)->create([
            'stock_quantity'      => 3,
            'low_stock_threshold' => 5,
            'manage_stock'        => true,
            'notify_low_stock'    => true,
        ]);

        $job = new SendLowStockAlertsJob();
        $job->handle();

        Notification::assertSentOnDemand(LowStockNotification::class);
    });

    it('does not send notification when no products are below threshold', function () {
        Notification::fake();

        config(['inventory.notification_recipients' => [$this->admin->email]]);
        config(['inventory.send_low_stock_notifications' => true]);

        Product::factory()->count(2)->create([
            'stock_quantity'      => 100,
            'low_stock_threshold' => 5,
            'manage_stock'        => true,
            'notify_low_stock'    => true,
        ]);

        $job = new SendLowStockAlertsJob();
        $job->handle();

        Notification::assertNothingSent();
    });

    it('excludes products with notify_low_stock disabled', function () {
        Notification::fake();

        config(['inventory.notification_recipients' => [$this->admin->email]]);
        config(['inventory.send_low_stock_notifications' => true]);

        // Product with notifications disabled
        Product::factory()->create([
            'stock_quantity'      => 2,
            'low_stock_threshold' => 5,
            'manage_stock'        => true,
            'notify_low_stock'    => false,
        ]);

        // Product with notifications enabled
        Product::factory()->create([
            'stock_quantity'      => 2,
            'low_stock_threshold' => 5,
            'manage_stock'        => true,
            'notify_low_stock'    => true,
        ]);

        $job = new SendLowStockAlertsJob();
        $job->handle();

        Notification::assertSentOnDemand(
            LowStockNotification::class,
            function (LowStockNotification $notification) {
                return $notification->products->count() === 1;
            },
        );
    });

    it('does not send when notifications are globally disabled', function () {
        Notification::fake();

        config(['inventory.send_low_stock_notifications' => false]);

        Product::factory()->create([
            'stock_quantity'      => 2,
            'low_stock_threshold' => 5,
            'manage_stock'        => true,
            'notify_low_stock'    => true,
        ]);

        $job = new SendLowStockAlertsJob();
        $job->handle();

        Notification::assertNothingSent();
    });

    it('does not send when no recipients configured', function () {
        Notification::fake();

        config(['inventory.notification_recipients' => []]);
        config(['inventory.send_low_stock_notifications' => true]);

        Product::factory()->create([
            'stock_quantity'      => 2,
            'low_stock_threshold' => 5,
            'manage_stock'        => true,
            'notify_low_stock'    => true,
        ]);

        $job = new SendLowStockAlertsJob();
        $job->handle();

        Notification::assertNothingSent();
    });

    it('uses default threshold when product has no custom threshold', function () {
        Notification::fake();

        config(['inventory.notification_recipients' => [$this->admin->email]]);
        config(['inventory.send_low_stock_notifications' => true]);
        config(['inventory.default_low_stock_threshold' => 10]);

        Product::factory()->create([
            'stock_quantity'      => 8,
            'low_stock_threshold' => null,
            'manage_stock'        => true,
            'notify_low_stock'    => true,
        ]);

        $job = new SendLowStockAlertsJob();
        $job->handle();

        Notification::assertSentOnDemand(LowStockNotification::class);
    });

    it('excludes products not managing stock', function () {
        Notification::fake();

        config(['inventory.notification_recipients' => [$this->admin->email]]);
        config(['inventory.send_low_stock_notifications' => true]);

        Product::factory()->create([
            'stock_quantity'      => 2,
            'low_stock_threshold' => 5,
            'manage_stock'        => false,
            'notify_low_stock'    => true,
        ]);

        $job = new SendLowStockAlertsJob();
        $job->handle();

        Notification::assertNothingSent();
    });

    it('excludes products with zero stock', function () {
        Notification::fake();

        config(['inventory.notification_recipients' => [$this->admin->email]]);
        config(['inventory.send_low_stock_notifications' => true]);

        // Zero stock products should NOT be in low stock alerts
        // (they are "out of stock", a different category)
        Product::factory()->create([
            'stock_quantity'      => 0,
            'low_stock_threshold' => 5,
            'manage_stock'        => true,
            'notify_low_stock'    => true,
        ]);

        $job = new SendLowStockAlertsJob();
        $job->handle();

        Notification::assertNothingSent();
    });

    it('sends to multiple recipients', function () {
        Notification::fake();

        $recipients = ['admin1@example.com', 'admin2@example.com', 'admin3@example.com'];
        config(['inventory.notification_recipients' => $recipients]);
        config(['inventory.send_low_stock_notifications' => true]);

        Product::factory()->create([
            'stock_quantity'      => 2,
            'low_stock_threshold' => 5,
            'manage_stock'        => true,
            'notify_low_stock'    => true,
        ]);

        $job = new SendLowStockAlertsJob();
        $job->handle();

        Notification::assertSentOnDemand(LowStockNotification::class);
    });

    it('can be queued', function () {
        Queue::fake();

        SendLowStockAlertsJob::dispatch();

        Queue::assertPushed(SendLowStockAlertsJob::class);
    });
});

describe('Product notify_low_stock field', function () {
    it('defaults to true for new products', function () {
        $product = Product::factory()->create();

        expect($product->notify_low_stock)->toBeTrue();
    });

    it('can be set to false to disable notifications', function () {
        $product = Product::factory()->create([
            'notify_low_stock' => false,
        ]);

        expect($product->notify_low_stock)->toBeFalse();
    });

    it('is included in low stock query when true', function () {
        $enabledProduct = Product::factory()->create([
            'stock_quantity'      => 2,
            'low_stock_threshold' => 5,
            'manage_stock'        => true,
            'notify_low_stock'    => true,
        ]);

        $disabledProduct = Product::factory()->create([
            'stock_quantity'      => 2,
            'low_stock_threshold' => 5,
            'manage_stock'        => true,
            'notify_low_stock'    => false,
        ]);

        $lowStockProducts = Product::query()
            ->belowThreshold()
            ->where('notify_low_stock', true)
            ->get();

        expect($lowStockProducts)->toHaveCount(1)
            ->and($lowStockProducts->first()->id)->toBe($enabledProduct->id);
    });
});
