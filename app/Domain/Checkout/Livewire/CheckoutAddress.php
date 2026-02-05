<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Livewire;

use App\Domain\Customer\Models\Address;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Auth, Http};
use Livewire\Attributes\Computed;
use Livewire\Component;

class CheckoutAddress extends Component
{
    /**
     * Selected address ID (for logged in users with saved addresses).
     */
    public ?string $selectedAddressId = null;

    /**
     * Whether to show the new address form.
     */
    public bool $showNewAddressForm = false;

    /**
     * Address form data.
     *
     * @var array<string, string|null>
     */
    public array $form = [
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
     * Whether the user is authenticated.
     */
    public bool $isAuthenticated = false;

    /**
     * Whether to save the address for future use.
     */
    public bool $saveAddress = false;

    /**
     * Loading state for CEP lookup.
     */
    public bool $isLoadingCep = false;

    /**
     * CEP error message.
     */
    public ?string $cepError = null;

    /**
     * Mount the component.
     *
     * @param  array<string, string|null>  $addressData
     */
    public function mount(array $addressData = [], bool $isAuthenticated = false): void
    {
        $this->isAuthenticated = $isAuthenticated;

        // Pre-fill form with existing data
        foreach ($addressData as $key => $value) {
            if (array_key_exists($key, $this->form)) {
                $this->form[$key] = $value ?? '';
            }
        }

        // Set selected address if provided
        if (!empty($addressData['shipping_address_id'])) {
            $this->selectedAddressId = $addressData['shipping_address_id'];
        }

        // If authenticated user has no addresses, show the form
        if ($this->isAuthenticated && $this->savedAddresses->isEmpty()) {
            $this->showNewAddressForm = true;
        }

        // If no selected address and user has addresses, select the default
        if ($this->isAuthenticated && $this->selectedAddressId === null && $this->savedAddresses->isNotEmpty()) {
            /** @var Address $defaultAddress */
            $defaultAddress          = $this->savedAddresses->firstWhere('is_default', true) ?? $this->savedAddresses->first();
            $this->selectedAddressId = (string) $defaultAddress->id;
            $this->fillFormFromAddress($defaultAddress);
        }
    }

    /**
     * Get saved addresses for authenticated user.
     *
     * @return Collection<int, Address>
     */
    #[Computed]
    public function savedAddresses(): Collection
    {
        if (!$this->isAuthenticated) {
            return collect();
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        return $user->addresses()
            ->orderByDesc('is_default')
            ->orderBy('label')
            ->get();
    }

    /**
     * Fill form from a saved address.
     */
    protected function fillFormFromAddress(Address $address): void
    {
        $this->form = [
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

    /**
     * Handle address selection.
     */
    public function selectAddress(string $addressId): void
    {
        $this->selectedAddressId  = $addressId;
        $this->showNewAddressForm = false;

        /** @var Address|null $address */
        $address = $this->savedAddresses->firstWhere('id', (int) $addressId);

        if ($address !== null) {
            $this->fillFormFromAddress($address);
        }
    }

    /**
     * Show new address form.
     */
    public function addNewAddress(): void
    {
        $this->showNewAddressForm = true;
        $this->selectedAddressId  = null;
        $this->form               = [
            'shipping_recipient_name' => '',
            'shipping_zipcode'        => '',
            'shipping_street'         => '',
            'shipping_number'         => '',
            'shipping_complement'     => '',
            'shipping_neighborhood'   => '',
            'shipping_city'           => '',
            'shipping_state'          => '',
        ];
    }

    /**
     * Cancel new address form.
     */
    public function cancelNewAddress(): void
    {
        $this->showNewAddressForm = false;

        if ($this->savedAddresses->isNotEmpty()) {
            /** @var Address $defaultAddress */
            $defaultAddress = $this->savedAddresses->firstWhere('is_default', true) ?? $this->savedAddresses->first();
            $this->selectAddress((string) $defaultAddress->id);
        }
    }

    /**
     * Format zipcode as user types.
     */
    public function updatedFormShippingZipcode(): void
    {
        // Remove non-numeric characters
        $zipcode = preg_replace('/\D/', '', $this->form['shipping_zipcode']);

        // Format with mask
        if (strlen($zipcode) >= 8) {
            $zipcode                        = substr($zipcode, 0, 8);
            $this->form['shipping_zipcode'] = sprintf(
                '%s-%s',
                substr($zipcode, 0, 5),
                substr($zipcode, 5, 3),
            );

            // Auto-lookup CEP
            $this->lookupCep();
        } elseif (strlen($zipcode) >= 5) {
            $this->form['shipping_zipcode'] = sprintf(
                '%s-%s',
                substr($zipcode, 0, 5),
                substr($zipcode, 5),
            );
        }
    }

    /**
     * Lookup CEP using ViaCEP API.
     */
    public function lookupCep(): void
    {
        $this->cepError = null;
        $zipcode        = preg_replace('/\D/', '', $this->form['shipping_zipcode']);

        if (strlen($zipcode) !== 8) {
            return;
        }

        $this->isLoadingCep = true;

        try {
            $response = Http::timeout(5)->get("https://viacep.com.br/ws/{$zipcode}/json/");

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['erro']) && $data['erro']) {
                    $this->cepError     = 'CEP nao encontrado.';
                    $this->isLoadingCep = false;

                    return;
                }

                $this->form['shipping_street']       = $data['logradouro'] ?? '';
                $this->form['shipping_neighborhood'] = $data['bairro'] ?? '';
                $this->form['shipping_city']         = $data['localidade'] ?? '';
                $this->form['shipping_state']        = $data['uf'] ?? '';
            } else {
                $this->cepError = 'Erro ao consultar CEP. Tente novamente.';
            }
        } catch (\Exception $e) {
            $this->cepError = 'Erro ao consultar CEP. Verifique sua conexao.';
        }

        $this->isLoadingCep = false;
    }

    /**
     * Validation rules.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'form.shipping_recipient_name' => 'required|string|min:3|max:255',
            'form.shipping_zipcode'        => 'required|string|size:9',
            'form.shipping_street'         => 'required|string|max:255',
            'form.shipping_number'         => 'required|string|max:20',
            'form.shipping_complement'     => 'nullable|string|max:255',
            'form.shipping_neighborhood'   => 'required|string|max:255',
            'form.shipping_city'           => 'required|string|max:255',
            'form.shipping_state'          => 'required|string|size:2',
        ];
    }

    /**
     * Validation messages.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'form.shipping_recipient_name.required' => 'O nome do destinatario e obrigatorio.',
            'form.shipping_recipient_name.min'      => 'O nome deve ter pelo menos 3 caracteres.',
            'form.shipping_zipcode.required'        => 'O CEP e obrigatorio.',
            'form.shipping_zipcode.size'            => 'O CEP deve ter 9 caracteres (com hifen).',
            'form.shipping_street.required'         => 'O endereco e obrigatorio.',
            'form.shipping_number.required'         => 'O numero e obrigatorio.',
            'form.shipping_neighborhood.required'   => 'O bairro e obrigatorio.',
            'form.shipping_city.required'           => 'A cidade e obrigatoria.',
            'form.shipping_state.required'          => 'O estado e obrigatorio.',
            'form.shipping_state.size'              => 'O estado deve ter 2 caracteres (sigla).',
        ];
    }

    /**
     * Continue to next step.
     */
    public function continueToShipping(): void
    {
        $this->validate();

        // Save address if requested and user is authenticated
        if ($this->isAuthenticated && $this->saveAddress && $this->showNewAddressForm) {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $address = $user->addresses()->create([
                'label'          => 'Endereco de entrega',
                'recipient_name' => $this->form['shipping_recipient_name'],
                'zipcode'        => $this->form['shipping_zipcode'],
                'street'         => $this->form['shipping_street'],
                'number'         => $this->form['shipping_number'],
                'complement'     => $this->form['shipping_complement'] ?: null,
                'neighborhood'   => $this->form['shipping_neighborhood'],
                'city'           => $this->form['shipping_city'],
                'state'          => $this->form['shipping_state'],
                'is_default'     => $this->savedAddresses->isEmpty(),
            ]);

            $this->selectedAddressId = (string) $address->id;
        }

        $this->dispatch('address-data-submitted', array_merge(
            $this->form,
            ['shipping_address_id' => $this->selectedAddressId],
        ));
    }

    public function render(): View
    {
        return view('livewire.checkout.checkout-address');
    }
}
