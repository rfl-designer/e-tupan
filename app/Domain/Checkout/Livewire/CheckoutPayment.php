<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Livewire;

use App\Domain\Checkout\Enums\PaymentMethod;
use App\Rules\ValidCreditCard;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CheckoutPayment extends Component
{
    /**
     * Selected payment method.
     */
    public ?string $paymentMethod = null;

    /**
     * Order total in cents.
     */
    public int $total = 0;

    /**
     * Credit card data.
     *
     * @var array<string, string>
     */
    public array $cardData = [
        'number' => '',
        'name'   => '',
        'expiry' => '',
        'cvv'    => '',
    ];

    /**
     * Number of installments.
     */
    public int $installments = 1;

    /**
     * Mount the component.
     */
    public function mount(?string $paymentMethod = null, int $total = 0): void
    {
        $this->paymentMethod = $paymentMethod;
        $this->total         = $total;
    }

    /**
     * Get available payment methods.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPaymentMethodsProperty(): array
    {
        return collect(PaymentMethod::cases())
            ->map(fn (PaymentMethod $method) => [
                'value'       => $method->value,
                'label'       => $method->label(),
                'icon'        => $method->icon(),
                'description' => $method->description(),
                'color'       => $method->color(),
            ])
            ->toArray();
    }

    /**
     * Get installment options.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getInstallmentOptionsProperty(): array
    {
        if ($this->total === 0) {
            return [];
        }

        $options             = [];
        $maxInstallments     = 12;
        $minInstallmentValue = 1000; // R$ 10,00

        for ($i = 1; $i <= $maxInstallments; $i++) {
            $installmentValue = (int) ceil($this->total / $i);

            if ($installmentValue < $minInstallmentValue && $i > 1) {
                break;
            }

            $options[] = [
                'value' => $i,
                'label' => $i === 1
                    ? 'A vista - R$ ' . number_format($this->total / 100, 2, ',', '.')
                    : "{$i}x de R$ " . number_format($installmentValue / 100, 2, ',', '.') . ' sem juros',
                'amount' => $installmentValue,
            ];
        }

        return $options;
    }

    /**
     * Select payment method.
     */
    public function selectMethod(string $method): void
    {
        $this->paymentMethod = $method;

        // Reset card data when changing method
        $this->cardData = [
            'number' => '',
            'name'   => '',
            'expiry' => '',
            'cvv'    => '',
        ];
        $this->installments = 1;
    }

    /**
     * Format card number as user types.
     */
    public function updatedCardDataNumber(): void
    {
        $number = preg_replace('/\D/', '', $this->cardData['number']);
        $number = substr($number, 0, 16);

        $formatted = '';

        for ($i = 0; $i < strlen($number); $i++) {
            if ($i > 0 && $i % 4 === 0) {
                $formatted .= ' ';
            }
            $formatted .= $number[$i];
        }

        $this->cardData['number'] = $formatted;
    }

    /**
     * Format card expiry as user types.
     */
    public function updatedCardDataExpiry(): void
    {
        $expiry = preg_replace('/\D/', '', $this->cardData['expiry']);
        $expiry = substr($expiry, 0, 4);

        if (strlen($expiry) >= 2) {
            $this->cardData['expiry'] = substr($expiry, 0, 2) . '/' . substr($expiry, 2);
        } else {
            $this->cardData['expiry'] = $expiry;
        }
    }

    /**
     * Format CVV as user types.
     */
    public function updatedCardDataCvv(): void
    {
        $this->cardData['cvv'] = preg_replace('/\D/', '', $this->cardData['cvv']);
        $this->cardData['cvv'] = substr($this->cardData['cvv'], 0, 4);
    }

    /**
     * Continue to review.
     */
    public function continueToReview(): void
    {
        if ($this->paymentMethod === null) {
            $this->addError('paymentMethod', 'Selecione uma forma de pagamento.');

            return;
        }

        // Validate credit card data if selected
        if ($this->paymentMethod === PaymentMethod::CreditCard->value) {
            $this->validate([
                'cardData.number' => ['required', 'string', 'min:19', 'max:19', new ValidCreditCard()],
                'cardData.name'   => 'required|string|min:3|max:100',
                'cardData.expiry' => 'required|string|size:5|regex:/^\d{2}\/\d{2}$/',
                'cardData.cvv'    => 'required|string|min:3|max:4',
            ], [
                'cardData.number.required' => 'O numero do cartao e obrigatorio.',
                'cardData.number.min'      => 'Numero do cartao invalido.',
                'cardData.name.required'   => 'O nome no cartao e obrigatorio.',
                'cardData.expiry.required' => 'A validade e obrigatoria.',
                'cardData.expiry.regex'    => 'Formato invalido. Use MM/AA.',
                'cardData.cvv.required'    => 'O codigo de seguranca e obrigatorio.',
                'cardData.cvv.min'         => 'Codigo de seguranca invalido.',
            ]);
        }

        $this->dispatch('payment-method-selected', $this->paymentMethod);
    }

    public function render(): View
    {
        return view('livewire.checkout.checkout-payment');
    }
}
