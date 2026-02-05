<?php declare(strict_types = 1);

use App\Domain\Cart\Http\Controllers\CartController;
use App\Domain\Checkout\Http\Controllers\CheckoutController;
use App\Domain\Checkout\Livewire\CheckoutPage;
use App\Domain\Customer\Livewire\{AddressManager, CustomerDashboard, OrderDetail, OrderList};
use App\Domain\Institutional\Livewire\{AboutPage, BlogListPage, BlogPostPage, ContactPage, DivisionPage, HomePage};
use App\Domain\Shipping\Http\Controllers\TrackingController;
use App\Livewire\Storefront\{Homepage as StorefrontHomepage, ProductList, ProductShow};
use Illuminate\Support\Facades\Route;

Route::get('/', HomePage::class)->name('institutional.home');
Route::get('/sobre', AboutPage::class)->name('about');
Route::get('/contato', ContactPage::class)->name('contact');
Route::get('/blog', BlogListPage::class)->name('blog.index');
Route::get('/blog/{slug}', BlogPostPage::class)->name('blog.show');
Route::get('/solucoes/{slug}', DivisionPage::class)->name('solutions.show');

Route::get('/loja', StorefrontHomepage::class)->name('home');
Route::get('/produtos', ProductList::class)->name('products.index');
Route::get('/produtos/{slug}', ProductShow::class)->name('products.show');
Route::get('/busca', ProductList::class)->name('search');

// Cart routes
Route::prefix('carrinho')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
});

// Checkout routes
Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', CheckoutPage::class)->name('index');
    Route::get('/sucesso/{order}', [CheckoutController::class, 'success'])->name('success');
});

// Tracking routes
Route::prefix('rastreio')->name('tracking.')->group(function () {
    Route::get('/', [TrackingController::class, 'index'])->name('index');
    Route::post('/', [TrackingController::class, 'search'])->name('search');
    Route::get('/{code}', [TrackingController::class, 'show'])->name('show');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('minha-conta', CustomerDashboard::class)->name('customer.dashboard');
    Route::get('minha-conta/enderecos', AddressManager::class)->name('customer.addresses');
    Route::get('minha-conta/pedidos', OrderList::class)->name('customer.orders');
    Route::get('minha-conta/pedidos/{order}', OrderDetail::class)->name('customer.orders.show');
});

require __DIR__ . '/settings.php';
