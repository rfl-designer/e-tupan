<?php declare(strict_types = 1);

use App\Domain\Checkout\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Payment Webhook Routes
|--------------------------------------------------------------------------
|
| These routes handle payment gateway webhooks. They are excluded from
| CSRF verification and other web middleware.
|
*/

Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('/mercadopago', [PaymentWebhookController::class, 'mercadopago'])->name('mercadopago');
    Route::post('/mock', [PaymentWebhookController::class, 'mock'])->name('mock');
});
