<?php declare(strict_types = 1);

use App\Domain\Admin\Http\Controllers\{ActivityLogController, AdminAuthController, AdminController, AdminPasswordResetController, AdminTwoFactorController, CustomerController, DashboardController, HelpController, NotificationController, OrderController, SettingsController};
use App\Domain\Cart\Http\Controllers\AdminCartController;
use App\Domain\Catalog\Http\Controllers\{AttributeController, CategoryController, ProductController};
use App\Domain\Checkout\Http\Controllers\Admin\PaymentLogController;
use App\Domain\Inventory\Http\Controllers\StockController;
use App\Domain\Marketing\Http\Controllers\{BannerController, CouponController};
use App\Domain\Shipping\Http\Controllers\Admin\ShippingSettingsController;
use App\Domain\Shipping\Livewire\Admin\ShipmentManager;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Here is where you can register admin routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group.
|
*/

Route::prefix('admin')->name('admin.')->group(function () {
    // Guest routes (not authenticated as admin)
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AdminAuthController::class, 'login'])
            ->middleware('throttle:admin-login')
            ->name('login.store');

        // Password Reset Routes
        Route::get('forgot-password', [AdminPasswordResetController::class, 'showForgotForm'])
            ->name('password.request');
        Route::post('forgot-password', [AdminPasswordResetController::class, 'sendResetLink'])
            ->name('password.email');
        Route::get('reset-password/{token}', [AdminPasswordResetController::class, 'showResetForm'])
            ->name('password.reset');
        Route::post('reset-password', [AdminPasswordResetController::class, 'reset'])
            ->name('password.update');
    });

    // Authenticated admin routes
    Route::middleware('auth:admin')->group(function () {
        // Two-factor authentication setup and challenge (does not require 2FA confirmed)
        Route::prefix('two-factor')->name('two-factor.')->group(function () {
            Route::get('setup', [AdminTwoFactorController::class, 'showSetup'])->name('setup');
            Route::post('confirm', [AdminTwoFactorController::class, 'confirmSetup'])->name('confirm');
            Route::get('challenge', [AdminTwoFactorController::class, 'showChallenge'])->name('challenge');
            Route::post('challenge', [AdminTwoFactorController::class, 'verifyChallenge'])->name('verify');
        });

        // Routes that require 2FA confirmed with session timeout and access logging
        Route::middleware(['admin.timeout', 'admin.2fa', 'admin.log'])->group(function () {
            Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

            // Dashboard
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

            // Orders management
            Route::prefix('orders')->name('orders.')->group(function () {
                Route::get('/', [OrderController::class, 'index'])->name('index');
                Route::get('/{order}', [OrderController::class, 'show'])->name('show');
            });

            // Customers management
            Route::prefix('customers')->name('customers.')->group(function () {
                Route::get('/', [CustomerController::class, 'index'])->name('index');
                Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
            });

            // Settings
            Route::prefix('settings')->name('settings.')->group(function () {
                Route::get('/', [SettingsController::class, 'index'])->name('index');
            });

            // Activity Logs
            Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
                Route::get('/', [ActivityLogController::class, 'index'])->name('index');
            });

            // Email Logs
            Route::prefix('email-logs')->name('email-logs.')->group(function () {
                Route::get('/', [EmailLogController::class, 'index'])->name('index');
            });

            // Notifications
            Route::prefix('notifications')->name('notifications.')->group(function () {
                Route::get('/', [NotificationController::class, 'index'])->name('index');
            });

            // Help
            Route::get('/help', [HelpController::class, 'index'])->name('help');

            // Admin management (master only)
            Route::middleware('admin.master')->group(function () {
                Route::resource('administrators', AdminController::class)->except(['show']);
            });

            // Catalog management
            Route::prefix('categories')->name('categories.')->group(function () {
                Route::get('/', [CategoryController::class, 'index'])->name('index');
                Route::get('/create', [CategoryController::class, 'create'])->name('create');
                Route::post('/', [CategoryController::class, 'store'])->name('store');
                Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
                Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
                Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
                Route::post('/reorder', [CategoryController::class, 'reorder'])->name('reorder');
            });

            Route::prefix('attributes')->name('attributes.')->group(function () {
                Route::get('/', [AttributeController::class, 'index'])->name('index');
                Route::get('/create', [AttributeController::class, 'create'])->name('create');
                Route::post('/', [AttributeController::class, 'store'])->name('store');
                Route::get('/{attribute}/edit', [AttributeController::class, 'edit'])->name('edit');
                Route::put('/{attribute}', [AttributeController::class, 'update'])->name('update');
                Route::delete('/{attribute}', [AttributeController::class, 'destroy'])->name('destroy');
            });

            // Products management
            Route::prefix('products')->name('products.')->group(function () {
                Route::get('/', [ProductController::class, 'index'])->name('index');
                Route::get('/create', [ProductController::class, 'create'])->name('create');
                Route::post('/', [ProductController::class, 'store'])->name('store');
                Route::get('/trash', [ProductController::class, 'trash'])->name('trash');
                Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
                Route::put('/{product}', [ProductController::class, 'update'])->name('update');
                Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
                Route::post('/{product}/duplicate', [ProductController::class, 'duplicate'])->name('duplicate');
                Route::post('/{product}/restore', [ProductController::class, 'restore'])->name('restore')->withTrashed();
                Route::delete('/{product}/force-delete', [ProductController::class, 'forceDelete'])->name('force-delete')->withTrashed();
                Route::post('/bulk-action', [ProductController::class, 'bulkAction'])->name('bulk-action');
            });

            // Inventory management
            Route::prefix('inventory')->name('inventory.')->group(function () {
                Route::get('/', [StockController::class, 'index'])->name('index');
                Route::get('/dashboard', [StockController::class, 'dashboard'])->name('dashboard');
                Route::get('/movements', [StockController::class, 'movements'])->name('movements');
                Route::post('/adjust', [StockController::class, 'adjust'])->name('adjust');
            });

            // Coupons management
            Route::prefix('coupons')->name('coupons.')->group(function () {
                Route::get('/', [CouponController::class, 'index'])->name('index');
                Route::get('/create', [CouponController::class, 'create'])->name('create');
                Route::post('/', [CouponController::class, 'store'])->name('store');
                Route::get('/{coupon}/edit', [CouponController::class, 'edit'])->name('edit');
                Route::put('/{coupon}', [CouponController::class, 'update'])->name('update');
                Route::delete('/{coupon}', [CouponController::class, 'destroy'])->name('destroy');
                Route::patch('/{coupon}/toggle-active', [CouponController::class, 'toggleActive'])->name('toggle-active');
            });

            // Banners management
            Route::prefix('banners')->name('banners.')->group(function () {
                Route::get('/', [BannerController::class, 'index'])->name('index');
                Route::get('/create', [BannerController::class, 'create'])->name('create');
                Route::post('/', [BannerController::class, 'store'])->name('store');
                Route::get('/{banner}/edit', [BannerController::class, 'edit'])->name('edit');
                Route::put('/{banner}', [BannerController::class, 'update'])->name('update');
                Route::delete('/{banner}', [BannerController::class, 'destroy'])->name('destroy');
                Route::patch('/{banner}/toggle-active', [BannerController::class, 'toggleActive'])->name('toggle-active');
                Route::post('/{banner}/duplicate', [BannerController::class, 'duplicate'])->name('duplicate');
                Route::patch('/reorder', [BannerController::class, 'reorder'])->name('reorder');
            });

            // Carts management
            Route::prefix('carts')->name('carts.')->group(function () {
                Route::get('/abandoned', [AdminCartController::class, 'abandoned'])->name('abandoned');
            });

            // Payment logs
            Route::prefix('payments')->name('payments.')->group(function () {
                Route::get('/logs', [PaymentLogController::class, 'index'])->name('logs');
                Route::get('/logs/{paymentLog}', [PaymentLogController::class, 'show'])->name('logs.show');
            });

            // Shipping settings and management
            Route::prefix('shipping')->name('shipping.')->group(function () {
                Route::get('/', ShipmentManager::class)->name('index');
                Route::get('/settings', [ShippingSettingsController::class, 'index'])->name('settings');
                Route::post('/settings/carriers', [ShippingSettingsController::class, 'updateCarriers'])->name('settings.carriers');
                Route::post('/settings/free-shipping', [ShippingSettingsController::class, 'updateFreeShipping'])->name('settings.free-shipping');
                Route::post('/settings/origin', [ShippingSettingsController::class, 'updateOrigin'])->name('settings.origin');
                Route::post('/settings/handling', [ShippingSettingsController::class, 'updateHandling'])->name('settings.handling');
                Route::post('/settings/test-connection', [ShippingSettingsController::class, 'testConnection'])->name('settings.test-connection');
            });
        });
    });
});
