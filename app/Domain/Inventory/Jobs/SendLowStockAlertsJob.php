<?php

declare(strict_types = 1);

namespace App\Domain\Inventory\Jobs;

use App\Domain\Catalog\Models\Product;
use App\Domain\Inventory\Notifications\LowStockNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Log, Notification};

class SendLowStockAlertsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!config('inventory.send_low_stock_notifications', true)) {
            Log::info('Low stock notifications are disabled.');

            return;
        }

        $recipients = config('inventory.notification_recipients', []);

        if (empty($recipients)) {
            Log::info('No recipients configured for low stock notifications.');

            return;
        }

        $products = $this->getLowStockProducts();

        if ($products->isEmpty()) {
            Log::info('No products with low stock found.');

            return;
        }

        $notification = new LowStockNotification($products);

        Notification::route('mail', $recipients)
            ->notify($notification);

        Log::info("Low stock notification sent for {$products->count()} products to " . count($recipients) . ' recipients.');
    }

    /**
     * Get products that are below their low stock threshold and have notifications enabled.
     *
     * @return \Illuminate\Support\Collection<int, Product>
     */
    protected function getLowStockProducts(): \Illuminate\Support\Collection
    {
        return Product::query()
            ->belowThreshold()
            ->where('notify_low_stock', true)
            ->orderBy('stock_quantity')
            ->get();
    }
}
