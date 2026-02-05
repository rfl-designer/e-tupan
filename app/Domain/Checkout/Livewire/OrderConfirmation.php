<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Livewire;

use App\Domain\Checkout\Enums\PaymentMethod;
use App\Domain\Checkout\Models\{Order, Payment};
use Illuminate\Contracts\View\View;
use Livewire\Component;

class OrderConfirmation extends Component
{
    /**
     * The order being displayed.
     */
    public Order $order;

    /**
     * Whether this is a guest order.
     */
    public bool $isGuest = false;

    /**
     * The latest payment for this order.
     */
    public ?Payment $payment = null;

    /**
     * Mount the component.
     */
    public function mount(Order $order, bool $isGuest = false): void
    {
        $this->order   = $order;
        $this->isGuest = $isGuest || $order->isGuest();
        $this->order->load(['items', 'payments']);
        $this->payment = $order->latestPayment();
    }

    /**
     * Get the formatted subtotal.
     */
    public function getFormattedSubtotalProperty(): string
    {
        return 'R$ ' . number_format($this->order->subtotal / 100, 2, ',', '.');
    }

    /**
     * Get the formatted shipping cost.
     */
    public function getFormattedShippingCostProperty(): string
    {
        return 'R$ ' . number_format($this->order->shipping_cost / 100, 2, ',', '.');
    }

    /**
     * Get the formatted discount.
     */
    public function getFormattedDiscountProperty(): string
    {
        return 'R$ ' . number_format($this->order->discount / 100, 2, ',', '.');
    }

    /**
     * Get the formatted total.
     */
    public function getFormattedTotalProperty(): string
    {
        return 'R$ ' . number_format($this->order->total / 100, 2, ',', '.');
    }

    /**
     * Get the payment status label.
     */
    public function getPaymentStatusLabelProperty(): string
    {
        return $this->order->payment_status->label();
    }

    /**
     * Check if payment is pending (Pix or Bank Slip).
     */
    public function getIsPendingPaymentProperty(): bool
    {
        return $this->order->isPendingPayment();
    }

    /**
     * Check if payment method is Pix.
     */
    public function getIsPixPaymentProperty(): bool
    {
        return $this->payment?->method === PaymentMethod::Pix;
    }

    /**
     * Check if payment method is Bank Slip.
     */
    public function getIsBankSlipPaymentProperty(): bool
    {
        return $this->payment?->method === PaymentMethod::BankSlip;
    }

    /**
     * Get the delivery estimate text.
     */
    public function getDeliveryEstimateProperty(): string
    {
        if ($this->order->shipping_days === null) {
            return 'A calcular';
        }

        return $this->order->shipping_days . ' dias uteis';
    }

    /**
     * Get the estimated delivery date formatted.
     */
    public function getEstimatedDeliveryDateProperty(): ?string
    {
        $date = $this->order->estimatedDeliveryDate;

        if ($date === null) {
            return null;
        }

        return $date->format('d/m/Y');
    }

    /**
     * Copy Pix code to clipboard.
     */
    public function copyPixCode(): void
    {
        $this->dispatch('copy-to-clipboard', text: $this->payment?->pix_code ?? '');
    }

    /**
     * Copy barcode to clipboard.
     */
    public function copyBarcode(): void
    {
        $this->dispatch('copy-to-clipboard', text: $this->payment?->bank_slip_barcode ?? '');
    }

    public function render(): View
    {
        return view('livewire.checkout.order-confirmation');
    }
}
