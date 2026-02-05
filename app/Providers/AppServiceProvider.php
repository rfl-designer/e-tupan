<?php declare(strict_types = 1);

namespace App\Providers;

use App\Domain\Admin\Livewire\{ActivityLogList, GlobalSearch, NotificationBell, NotificationList};
use App\Domain\Admin\Livewire\Customers\{CustomerDetails, CustomerList};
use App\Domain\Admin\Livewire\Dashboard\{QuickActions, RecentOrders, SalesChart, SalesOverview, TopProducts};
use App\Domain\Admin\Livewire\Orders\{OrderActions, OrderDetails, OrderList, OrderNotes, OrderTimeline};
use App\Domain\Admin\Livewire\Settings\{CheckoutSettings, EmailSettings, GeneralSettings, PaymentSettings, StockSettings};
use App\Domain\Cart\Listeners\MergeCartOnLogin;
use App\Domain\Cart\Livewire\{AddToCart, CartItemRow, CartPage, MiniCart};
use App\Domain\Cart\Livewire\Admin\AbandonedCarts;
use App\Domain\Cart\Models\{Cart, CartItem};
use App\Domain\Cart\Observers\{CartItemObserver, CartObserver};
use App\Domain\Catalog\Livewire\Admin\{AttributeManager, CategoryTree, ProductImages, ProductList, ProductTrash, ProductVariants};
use App\Domain\Checkout\Console\Commands\PaymentTestCommand;
use App\Domain\Checkout\Contracts\PaymentGatewayInterface;
use App\Domain\Checkout\Events\OrderCreated;
use App\Domain\Checkout\Factories\PaymentGatewayFactory;
use App\Domain\Checkout\Listeners\SendOrderConfirmationEmail;
use App\Domain\Checkout\Livewire\{CheckoutAddress, CheckoutIdentification, CheckoutPage, CheckoutPayment, CheckoutReview, CheckoutShipping, CheckoutSummary, OrderConfirmation};
use App\Domain\Customer\Listeners\{LogFailedLogin, LogLockout, LogSuccessfulLogin, LogSuccessfulLogout};
use App\Domain\Institutional\Livewire\{AboutPage, BlogListPage, BlogPostPage, ContactForm, ContactPage, DivisionPage, Header as InstitutionalHeader, HomePage};
use App\Domain\Marketing\Livewire\CouponForm;
use App\Domain\Shipping\Livewire\ShippingCalculator;
use Illuminate\Auth\Events\{Failed, Lockout, Login, Logout};
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Event, RateLimiter};
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, function () {
            return PaymentGatewayFactory::default();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerAuthEventListeners();
        $this->registerCheckoutEventListeners();
        $this->registerModelObservers();
        $this->configureRateLimiting();
        $this->registerLivewireComponents();
        $this->registerCommands();
    }

    /**
     * Register console commands.
     */
    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PaymentTestCommand::class,
            ]);
        }
    }

    /**
     * Register model observers.
     */
    private function registerModelObservers(): void
    {
        Cart::observe(CartObserver::class);
        CartItem::observe(CartItemObserver::class);
    }

    /**
     * Register custom Livewire components.
     */
    private function registerLivewireComponents(): void
    {
        // Admin Dashboard components
        Livewire::component('admin.dashboard.sales-overview', SalesOverview::class);
        Livewire::component('admin.dashboard.sales-chart', SalesChart::class);
        Livewire::component('admin.dashboard.recent-orders', RecentOrders::class);
        Livewire::component('admin.dashboard.top-products', TopProducts::class);
        Livewire::component('admin.dashboard.quick-actions', QuickActions::class);

        // Admin Orders components
        Livewire::component('admin.orders.order-list', OrderList::class);
        Livewire::component('admin.orders.order-details', OrderDetails::class);
        Livewire::component('admin.orders.order-timeline', OrderTimeline::class);
        Livewire::component('admin.orders.order-actions', OrderActions::class);
        Livewire::component('admin.orders.order-notes', OrderNotes::class);

        // Admin Customers components
        Livewire::component('admin.customers.customer-list', CustomerList::class);
        Livewire::component('admin.customers.customer-details', CustomerDetails::class);

        // Admin Settings components
        Livewire::component('admin.settings.general-settings', GeneralSettings::class);
        Livewire::component('admin.settings.checkout-settings', CheckoutSettings::class);
        Livewire::component('admin.settings.stock-settings', StockSettings::class);
        Livewire::component('admin.settings.payment-settings', PaymentSettings::class);
        Livewire::component('admin.settings.email-settings', EmailSettings::class);
        Livewire::component('admin.settings.email-log-list', EmailLogList::class);

        // Admin Activity & Notifications components
        Livewire::component('admin.activity-log-list', ActivityLogList::class);
        Livewire::component('admin.notification-bell', NotificationBell::class);
        Livewire::component('admin.notification-list', NotificationList::class);
        Livewire::component('admin.global-search', GlobalSearch::class);

        // Catalog components
        Livewire::component('category-tree', CategoryTree::class);
        Livewire::component('admin.attribute-manager', AttributeManager::class);
        Livewire::component('product-list', ProductList::class);
        Livewire::component('product-trash', ProductTrash::class);
        Livewire::component('product-images', ProductImages::class);
        Livewire::component('product-variants', ProductVariants::class);

        // Cart components
        Livewire::component('cart.add-to-cart', AddToCart::class);
        Livewire::component('cart.mini-cart', MiniCart::class);
        Livewire::component('cart.cart-page', CartPage::class);
        Livewire::component('cart.cart-item-row', CartItemRow::class);
        Livewire::component('admin.abandoned-carts', AbandonedCarts::class);

        // Shipping components
        Livewire::component('shipping.calculator', ShippingCalculator::class);

        // Marketing components
        Livewire::component('marketing.coupon-form', CouponForm::class);

        // Institutional components
        Livewire::component('institutional.home-page', HomePage::class);
        Livewire::component('institutional.about-page', AboutPage::class);
        Livewire::component('institutional.contact-page', ContactPage::class);
        Livewire::component('institutional.contact-form', ContactForm::class);
        Livewire::component('institutional.blog-list-page', BlogListPage::class);
        Livewire::component('institutional.blog-post-page', BlogPostPage::class);
        Livewire::component('institutional.division-page', DivisionPage::class);
        Livewire::component('institutional.header', InstitutionalHeader::class);

        // Checkout components
        Livewire::component('checkout.checkout-page', CheckoutPage::class);
        Livewire::component('checkout.checkout-identification', CheckoutIdentification::class);
        Livewire::component('checkout.checkout-address', CheckoutAddress::class);
        Livewire::component('checkout.checkout-shipping', CheckoutShipping::class);
        Livewire::component('checkout.checkout-payment', CheckoutPayment::class);
        Livewire::component('checkout.checkout-review', CheckoutReview::class);
        Livewire::component('checkout.checkout-summary', CheckoutSummary::class);
        Livewire::component('checkout.order-confirmation', OrderConfirmation::class);
    }

    /**
     * Register authentication event listeners for logging.
     */
    private function registerAuthEventListeners(): void
    {
        Event::listen(Login::class, LogSuccessfulLogin::class);
        Event::listen(Login::class, MergeCartOnLogin::class);
        Event::listen(Logout::class, LogSuccessfulLogout::class);
        Event::listen(Failed::class, LogFailedLogin::class);
        Event::listen(Lockout::class, LogLockout::class);
    }

    /**
     * Register checkout event listeners.
     */
    private function registerCheckoutEventListeners(): void
    {
        Event::listen(OrderCreated::class, SendOrderConfirmationEmail::class);
    }

    /**
     * Configure the rate limiters for the application.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('admin-login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
