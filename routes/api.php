<?php declare(strict_types = 1);

use App\Domain\Checkout\Http\Controllers\InstallmentController;
use App\Domain\Shipping\Http\Controllers\TrackingWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('checkout')->name('api.checkout.')->group(function () {
    Route::get('installments', InstallmentController::class)->name('installments');
});

// Shipping webhooks
Route::prefix('webhooks')->name('api.webhooks.')->group(function () {
    Route::post('melhor-envio/tracking', TrackingWebhookController::class)->name('melhor-envio.tracking');
});
