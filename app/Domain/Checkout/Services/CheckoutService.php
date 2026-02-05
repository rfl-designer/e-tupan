<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Services;

use App\Domain\Cart\Enums\CartStatus;
use App\Domain\Cart\Models\Cart;
use App\Domain\Cart\Services\{CartService, CartValidationService};
use App\Domain\Checkout\DTOs\CardData;
use App\Domain\Checkout\Enums\{OrderStatus, PaymentMethod, PaymentStatus};
use App\Domain\Checkout\Events\OrderCreated;
use App\Domain\Checkout\Models\{Order, OrderItem, Payment};
use App\Domain\Customer\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\{DB, Log};

class CheckoutService
{
    public function __construct(
        protected CartService $cartService,
        protected CartValidationService $cartValidationService,
        protected PaymentService $paymentService,
    ) {
    }

    /**
     * Create checkout session data from cart.
     *
     * @return array<string, mixed>
     */
    public function initializeCheckout(Cart $cart, ?User $user = null): array
    {
        // Validate cart first
        $validation = $this->cartValidationService->validateCart($cart);

        if ($cart->isEmpty()) {
            return [
                'success' => false,
                'error'   => 'Carrinho vazio',
                'alerts'  => $validation['alerts'],
            ];
        }

        return [
            'success' => true,
            'cart'    => $cart,
            'user'    => $user,
            'alerts'  => $validation['alerts'],
            'step'    => $this->determineInitialStep($user),
        ];
    }

    /**
     * Determine the initial checkout step based on user state.
     */
    protected function determineInitialStep(?User $user): string
    {
        if ($user === null) {
            return 'identification';
        }

        // If user has default address, skip to shipping
        if ($user->defaultAddress() !== null) {
            return 'shipping';
        }

        return 'address';
    }

    /**
     * Create an order from a cart.
     *
     * @param  array<string, mixed>  $data
     */
    public function createOrder(Cart $cart, array $data): Order
    {
        return DB::transaction(function () use ($cart, $data) {
            // Calculate values
            $shippingCost = $data['shipping_cost'] ?? $cart->shipping_cost ?? 0;
            $discount     = $data['discount'] ?? $cart->discount ?? 0;
            $total        = $cart->subtotal + $shippingCost - $discount;

            // Create the order
            $order = Order::create([
                'user_id'                 => $data['user_id'] ?? $cart->user_id,
                'guest_email'             => $data['guest_email'] ?? null,
                'guest_name'              => $data['guest_name'] ?? null,
                'guest_cpf'               => $data['guest_cpf'] ?? null,
                'guest_phone'             => $data['guest_phone'] ?? null,
                'cart_id'                 => $cart->id,
                'status'                  => OrderStatus::Pending,
                'payment_status'          => PaymentStatus::Pending,
                'shipping_address_id'     => $data['shipping_address_id'] ?? null,
                'shipping_recipient_name' => $data['shipping_recipient_name'] ?? null,
                'shipping_zipcode'        => $data['shipping_zipcode'] ?? null,
                'shipping_street'         => $data['shipping_street'] ?? null,
                'shipping_number'         => $data['shipping_number'] ?? null,
                'shipping_complement'     => $data['shipping_complement'] ?? null,
                'shipping_neighborhood'   => $data['shipping_neighborhood'] ?? null,
                'shipping_city'           => $data['shipping_city'] ?? null,
                'shipping_state'          => $data['shipping_state'] ?? null,
                'shipping_method'         => $data['shipping_method'] ?? null,
                'shipping_carrier'        => $data['shipping_carrier'] ?? null,
                'shipping_days'           => $data['shipping_days'] ?? null,
                'coupon_id'               => $data['coupon_id'] ?? $cart->coupon_id,
                'coupon_code'             => $data['coupon_code'] ?? $cart->coupon?->code,
                'subtotal'                => $cart->subtotal,
                'shipping_cost'           => $shippingCost,
                'discount'                => $discount,
                'total'                   => $total,
                'notes'                   => $data['notes'] ?? null,
                'placed_at'               => now(),
            ]);

            // Create order items from cart items
            $cart->load(['items.product', 'items.variant']);

            foreach ($cart->items as $cartItem) {
                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $cartItem->product_id,
                    'variant_id'   => $cartItem->variant_id,
                    'product_name' => $cartItem->product->name,
                    'product_sku'  => $cartItem->product->sku,
                    'variant_name' => $cartItem->variant?->attributeValues->pluck('value')->join(' / '),
                    'variant_sku'  => $cartItem->variant?->sku,
                    'quantity'     => $cartItem->quantity,
                    'unit_price'   => $cartItem->unit_price,
                    'sale_price'   => $cartItem->sale_price,
                    'subtotal'     => $cartItem->getSubtotal(),
                ]);
            }

            event(new OrderCreated($order));

            return $order;
        });
    }

    /**
     * Mark the cart as converted after successful order creation.
     */
    public function convertCart(Cart $cart): void
    {
        $cart->status = CartStatus::Converted;
        $cart->save();
    }

    /**
     * Populate order address from user's saved address.
     *
     * @return array<string, mixed>
     */
    public function getAddressData(Address $address): array
    {
        return [
            'shipping_address_id'     => $address->id,
            'shipping_recipient_name' => $address->recipient_name,
            'shipping_zipcode'        => $address->zipcode,
            'shipping_street'         => $address->street,
            'shipping_number'         => $address->number,
            'shipping_complement'     => $address->complement,
            'shipping_neighborhood'   => $address->neighborhood,
            'shipping_city'           => $address->city,
            'shipping_state'          => $address->state,
        ];
    }

    /**
     * Calculate the order total with shipping.
     */
    public function calculateTotal(Cart $cart, int $shippingCost = 0): int
    {
        return $cart->subtotal + $shippingCost - $cart->discount;
    }

    /**
     * Check if the user can proceed to checkout.
     */
    public function canProceedToCheckout(Cart $cart): bool
    {
        if ($cart->isEmpty()) {
            return false;
        }

        // Validate cart
        $validation = $this->cartValidationService->validateCart($cart);

        // Check if any items were removed due to stock issues
        if (!empty($validation['removed_items'])) {
            return false;
        }

        return !$cart->isEmpty();
    }

    /**
     * Process payment for an order.
     */
    public function processPayment(
        Order $order,
        PaymentMethod $method,
        ?CardData $cardData = null,
    ): Payment {
        Log::info('Processing payment', [
            'order_id' => $order->id,
            'method'   => $method->value,
        ]);

        return match ($method) {
            PaymentMethod::CreditCard => $this->processCreditCardPayment($order, $cardData),
            PaymentMethod::Pix        => $this->processPixPayment($order),
            PaymentMethod::BankSlip   => $this->processBankSlipPayment($order),
        };
    }

    /**
     * Process credit card payment.
     */
    private function processCreditCardPayment(Order $order, ?CardData $cardData): Payment
    {
        if ($cardData === null) {
            throw new \InvalidArgumentException('Card data is required for credit card payments');
        }

        return $this->paymentService->processCard($order, $cardData);
    }

    /**
     * Process PIX payment.
     */
    private function processPixPayment(Order $order): Payment
    {
        return $this->paymentService->generatePix($order);
    }

    /**
     * Process bank slip payment.
     */
    private function processBankSlipPayment(Order $order): Payment
    {
        return $this->paymentService->generateBankSlip($order);
    }

    /**
     * Complete checkout process.
     *
     * @param  array<string, mixed>  $checkoutData
     * @return array{order: Order, payment: Payment}
     */
    public function completeCheckout(
        Cart $cart,
        array $checkoutData,
        PaymentMethod $paymentMethod,
        ?CardData $cardData = null,
    ): array {
        // Create order
        $order = $this->createOrder($cart, $checkoutData);

        // Mark cart as converted
        $this->convertCart($cart);

        // Process payment
        $payment = $this->processPayment($order, $paymentMethod, $cardData);

        // If payment is approved, update order status
        if ($payment->status === PaymentStatus::Approved) {
            $order->markAsPaid();
        }

        return [
            'order'   => $order,
            'payment' => $payment,
        ];
    }
}
