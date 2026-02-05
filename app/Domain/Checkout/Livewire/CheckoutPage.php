<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Livewire;

use App\Domain\Cart\Models\Cart;
use App\Domain\Cart\Services\{CartService, CartValidationService};
use App\Domain\Checkout\Services\CheckoutSessionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\{Computed, On};
use Livewire\Component;

class CheckoutPage extends Component
{
    /**
     * Current checkout step.
     */
    public string $currentStep = 'identification';

    /**
     * Guest customer data.
     *
     * @var array<string, string|null>
     */
    public array $guestData = [
        'email' => '',
        'name'  => '',
        'cpf'   => '',
        'phone' => '',
    ];

    /**
     * Shipping address data.
     *
     * @var array<string, string|null>
     */
    public array $addressData = [
        'shipping_address_id'     => null,
        'shipping_recipient_name' => '',
        'shipping_zipcode'        => '',
        'shipping_street'         => '',
        'shipping_number'         => '',
        'shipping_complement'     => '',
        'shipping_neighborhood'   => '',
        'shipping_city'           => '',
        'shipping_state'          => '',
    ];

    /**
     * Shipping method data.
     *
     * @var array<string, string|int|null>
     */
    public array $shippingData = [
        'shipping_method'  => null,
        'shipping_carrier' => null,
        'shipping_cost'    => null,
        'shipping_days'    => null,
    ];

    /**
     * Payment method.
     */
    public ?string $paymentMethod = null;

    /**
     * Validation alerts.
     *
     * @var list<string>
     */
    public array $validationAlerts = [];

    /**
     * Whether the user is logged in.
     */
    public bool $isAuthenticated = false;

    public function mount(): void
    {
        $cart = $this->getCart();

        // Redirect to cart if empty
        if ($cart === null || $cart->isEmpty()) {
            session()->flash('info', 'Seu carrinho esta vazio.');
            $this->redirect(route('cart.index'));

            return;
        }

        $this->isAuthenticated = Auth::check();

        // Validate cart
        $validationService      = new CartValidationService();
        $result                 = $validationService->validateCart($cart);
        $this->validationAlerts = $result['alerts'];

        // Check if cart is still valid after validation
        if ($cart->isEmpty()) {
            session()->flash('warning', 'Os itens do seu carrinho nao estao mais disponiveis.');
            $this->redirect(route('cart.index'));

            return;
        }

        // Restore checkout session data if available
        $this->restoreFromSession();

        // Determine initial step
        $this->currentStep = $this->determineInitialStep();

        // Pre-fill data if user is authenticated
        if ($this->isAuthenticated) {
            $this->prefillUserData();
        }
    }

    /**
     * Restore checkout data from session.
     */
    protected function restoreFromSession(): void
    {
        $sessionService = new CheckoutSessionService();

        if (!$sessionService->hasData()) {
            return;
        }

        $data = $sessionService->get();

        if (!empty($data['guestData'])) {
            $this->guestData = array_merge($this->guestData, $data['guestData']);
        }

        if (!empty($data['addressData'])) {
            $this->addressData = array_merge($this->addressData, $data['addressData']);
        }

        if (!empty($data['shippingData'])) {
            $this->shippingData = array_merge($this->shippingData, $data['shippingData']);
        }

        if (!empty($data['paymentMethod'])) {
            $this->paymentMethod = $data['paymentMethod'];
        }

        if (!empty($data['currentStep'])) {
            $this->currentStep = $data['currentStep'];
        }

        // Refresh session timestamp
        $sessionService->refreshTimestamp();
    }

    /**
     * Save current checkout state to session.
     */
    protected function saveToSession(): void
    {
        $sessionService = new CheckoutSessionService();

        $sessionService->save([
            'guestData'     => $this->guestData,
            'addressData'   => $this->addressData,
            'shippingData'  => $this->shippingData,
            'paymentMethod' => $this->paymentMethod,
            'currentStep'   => $this->currentStep,
        ]);
    }

    /**
     * Clear checkout session after order completion.
     */
    public function clearSession(): void
    {
        $sessionService = new CheckoutSessionService();
        $sessionService->clear();
    }

    /**
     * Determine the initial checkout step.
     */
    protected function determineInitialStep(): string
    {
        if (!$this->isAuthenticated) {
            return 'identification';
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // If user has default address, go to shipping
        if ($user->defaultAddress() !== null) {
            return 'shipping';
        }

        return 'address';
    }

    /**
     * Pre-fill user data for authenticated users.
     */
    protected function prefillUserData(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Pre-fill address if available
        $address = $user->defaultAddress();

        if ($address !== null) {
            $this->addressData = [
                'shipping_address_id'     => (string) $address->id,
                'shipping_recipient_name' => $address->recipient_name,
                'shipping_zipcode'        => $address->zipcode,
                'shipping_street'         => $address->street,
                'shipping_number'         => $address->number,
                'shipping_complement'     => $address->complement ?? '',
                'shipping_neighborhood'   => $address->neighborhood,
                'shipping_city'           => $address->city,
                'shipping_state'          => $address->state,
            ];
        }
    }

    /**
     * Get the current cart.
     */
    protected function getCart(): ?Cart
    {
        $cartService = new CartService();

        if (Auth::check()) {
            return $cartService->getForUser(Auth::id());
        }

        $sessionId = session()->getId();

        if ($sessionId !== '') {
            return $cartService->getForSession($sessionId);
        }

        return null;
    }

    /**
     * Get cart items for display.
     *
     * @return Collection<int, \App\Domain\Cart\Models\CartItem>
     */
    #[Computed]
    public function cartItems(): Collection
    {
        $cart = $this->getCart();

        if ($cart === null) {
            return collect();
        }

        return $cart->items()
            ->with(['product.images', 'variant'])
            ->get();
    }

    /**
     * Get cart subtotal.
     */
    #[Computed]
    public function subtotal(): int
    {
        return $this->getCart()?->subtotal ?? 0;
    }

    /**
     * Get cart discount.
     */
    #[Computed]
    public function discount(): int
    {
        return $this->getCart()?->discount ?? 0;
    }

    /**
     * Get shipping cost.
     */
    #[Computed]
    public function shippingCost(): int
    {
        return $this->shippingData['shipping_cost'] ?? 0;
    }

    /**
     * Get cart total.
     */
    #[Computed]
    public function total(): int
    {
        $cart = $this->getCart();

        if ($cart === null) {
            return 0;
        }

        return $cart->subtotal - $cart->discount + $this->shippingCost;
    }

    /**
     * Get item count.
     */
    #[Computed]
    public function itemCount(): int
    {
        return $this->getCart()?->itemCount() ?? 0;
    }

    /**
     * Navigate to a specific step.
     */
    public function goToStep(string $step): void
    {
        $allowedSteps = ['identification', 'address', 'shipping', 'payment', 'review'];

        if (!in_array($step, $allowedSteps, true)) {
            return;
        }

        // Validate current step before proceeding
        if (!$this->canProceedToStep($step)) {
            return;
        }

        $this->currentStep = $step;
    }

    /**
     * Check if user can proceed to a specific step.
     */
    protected function canProceedToStep(string $step): bool
    {
        // If going back, always allow
        $stepOrder    = ['identification', 'address', 'shipping', 'payment', 'review'];
        $currentIndex = array_search($this->currentStep, $stepOrder);
        $targetIndex  = array_search($step, $stepOrder);

        if ($targetIndex < $currentIndex) {
            return true;
        }

        // Validate forward navigation
        return match ($this->currentStep) {
            'identification' => $this->validateIdentification(),
            'address'        => $this->validateAddress(),
            'shipping'       => $this->validateShipping(),
            'payment'        => $this->validatePayment(),
            default          => true,
        };
    }

    /**
     * Validate identification step.
     */
    protected function validateIdentification(): bool
    {
        if ($this->isAuthenticated) {
            return true;
        }

        return !empty($this->guestData['email'])
            && !empty($this->guestData['name'])
            && !empty($this->guestData['cpf']);
    }

    /**
     * Validate address step.
     */
    protected function validateAddress(): bool
    {
        return !empty($this->addressData['shipping_zipcode'])
            && !empty($this->addressData['shipping_street'])
            && !empty($this->addressData['shipping_number'])
            && !empty($this->addressData['shipping_city'])
            && !empty($this->addressData['shipping_state']);
    }

    /**
     * Validate shipping step.
     */
    protected function validateShipping(): bool
    {
        return $this->shippingData['shipping_method'] !== null;
    }

    /**
     * Validate payment step.
     */
    protected function validatePayment(): bool
    {
        return $this->paymentMethod !== null;
    }

    /**
     * Handle guest data submitted from identification component.
     *
     * @param  array<string, string>  $data
     */
    #[On('guest-data-submitted')]
    public function handleGuestData(array $data): void
    {
        $this->guestData = $data;
        $this->goToStep('address');
        $this->saveToSession();
    }

    /**
     * Handle user logged in during checkout.
     */
    #[On('checkout-user-logged-in')]
    public function handleUserLoggedIn(): void
    {
        $this->isAuthenticated = true;
        $this->prefillUserData();
        $this->goToStep($this->determineInitialStep());
        $this->saveToSession();
    }

    /**
     * Handle address data submitted.
     *
     * @param  array<string, string|null>  $data
     */
    #[On('address-data-submitted')]
    public function handleAddressData(array $data): void
    {
        $this->addressData = $data;
        $this->goToStep('shipping');
        $this->saveToSession();
    }

    /**
     * Handle shipping method selected.
     *
     * @param  array<string, string|int|null>  $data
     */
    #[On('shipping-method-selected')]
    public function handleShippingMethod(array $data): void
    {
        $this->shippingData = $data;
        unset($this->shippingCost, $this->total);
        $this->goToStep('payment');
        $this->saveToSession();
    }

    /**
     * Handle payment method selected.
     */
    #[On('payment-method-selected')]
    public function handlePaymentMethod(string $method): void
    {
        $this->paymentMethod = $method;
        $this->goToStep('review');
        $this->saveToSession();
    }

    /**
     * Refresh cart data when updated.
     */
    #[On('cart-updated')]
    public function refreshCart(): void
    {
        unset($this->cartItems, $this->subtotal, $this->discount, $this->total, $this->itemCount);
    }

    /**
     * Get step completion status.
     *
     * @return array<string, bool>
     */
    #[Computed]
    public function stepStatus(): array
    {
        return [
            'identification' => $this->validateIdentification(),
            'address'        => $this->validateAddress(),
            'shipping'       => $this->validateShipping(),
            'payment'        => $this->validatePayment(),
        ];
    }

    public function render(): View
    {
        return view('livewire.checkout.checkout-page');
    }
}
