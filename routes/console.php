<?php declare(strict_types = 1);

use App\Domain\Admin\Jobs\CleanOldEmailLogsJob;
use App\Domain\Checkout\Jobs\CleanOldPaymentLogsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\{Artisan, Schedule};

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

Schedule::job(new CleanOldPaymentLogsJob())
    ->daily()
    ->at('03:00')
    ->name('clean-old-payment-logs')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::job(new CleanOldEmailLogsJob)
    ->daily()
    ->at('03:30')
    ->name('clean-old-email-logs')
    ->withoutOverlapping()
    ->onOneServer();
