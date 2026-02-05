<?php declare(strict_types = 1);

namespace App\Domain\Customer\Livewire;

use App\Domain\Customer\Models\Address;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\{Layout, Title};
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Meus Enderecos')]
class AddressManager extends Component
{
    public const int MAX_ADDRESSES = 5;

    /** @var Collection<int, Address> */
    public Collection $addresses;

    public ?int $editingAddressId = null;

    public bool $showForm = false;

    public bool $showDeleteModal = false;

    public ?int $deletingAddressId = null;

    // Form fields
    public string $label = '';

    public string $recipient_name = '';

    public string $zipcode = '';

    public string $street = '';

    public string $number = '';

    public string $complement = '';

    public string $neighborhood = '';

    public string $city = '';

    public string $state = '';

    public bool $is_default = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->loadAddresses();
    }

    /**
     * Load addresses for the authenticated user.
     */
    public function loadAddresses(): void
    {
        $this->addresses = Auth::user()
            ->addresses()
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Open form to create a new address.
     */
    public function create(): void
    {
        if ($this->addresses->count() >= self::MAX_ADDRESSES) {
            $this->dispatch('notify', message: 'Voce atingiu o limite maximo de ' . self::MAX_ADDRESSES . ' enderecos.', type: 'error');

            return;
        }

        $this->resetForm();
        $this->showForm = true;
    }

    /**
     * Open form to edit an existing address.
     */
    public function edit(int $addressId): void
    {
        $address = $this->findUserAddress($addressId);

        if (!$address) {
            return;
        }

        $this->editingAddressId = $address->id;
        $this->label            = $address->label ?? '';
        $this->recipient_name   = $address->recipient_name;
        $this->zipcode          = $address->zipcode;
        $this->street           = $address->street;
        $this->number           = $address->number;
        $this->complement       = $address->complement ?? '';
        $this->neighborhood     = $address->neighborhood;
        $this->city             = $address->city;
        $this->state            = $address->state;
        $this->is_default       = $address->is_default;
        $this->showForm         = true;
    }

    /**
     * Save the address (create or update).
     */
    public function save(): void
    {
        $validated = $this->validate();

        // Check limit when creating
        if (!$this->editingAddressId && $this->addresses->count() >= self::MAX_ADDRESSES) {
            $this->dispatch('notify', message: 'Voce atingiu o limite maximo de ' . self::MAX_ADDRESSES . ' enderecos.', type: 'error');

            return;
        }

        $data = [
            'label'          => $validated['label'] ?: null,
            'recipient_name' => $validated['recipient_name'],
            'zipcode'        => $validated['zipcode'],
            'street'         => $validated['street'],
            'number'         => $validated['number'],
            'complement'     => $validated['complement'] ?: null,
            'neighborhood'   => $validated['neighborhood'],
            'city'           => $validated['city'],
            'state'          => $validated['state'],
            'is_default'     => $validated['is_default'],
        ];

        if ($this->editingAddressId) {
            $address = $this->findUserAddress($this->editingAddressId);

            if ($address) {
                $address->update($data);
                $this->dispatch('notify', message: 'Endereco atualizado com sucesso!', type: 'success');
            }
        } else {
            Auth::user()->addresses()->create($data);
            $this->dispatch('notify', message: 'Endereco cadastrado com sucesso!', type: 'success');
        }

        $this->cancel();
        $this->loadAddresses();
    }

    /**
     * Confirm address deletion.
     */
    public function confirmDelete(int $addressId): void
    {
        $this->deletingAddressId = $addressId;
        $this->showDeleteModal   = true;
    }

    /**
     * Delete the address.
     */
    public function delete(): void
    {
        if (!$this->deletingAddressId) {
            return;
        }

        $address = $this->findUserAddress($this->deletingAddressId);

        if ($address) {
            $address->delete();
            $this->dispatch('notify', message: 'Endereco excluido com sucesso!', type: 'success');
        }

        $this->showDeleteModal   = false;
        $this->deletingAddressId = null;
        $this->loadAddresses();
    }

    /**
     * Set an address as default.
     */
    public function setDefault(int $addressId): void
    {
        $address = $this->findUserAddress($addressId);

        if ($address && !$address->is_default) {
            $address->update(['is_default' => true]);
            $this->dispatch('notify', message: 'Endereco definido como padrao!', type: 'success');
            $this->loadAddresses();
        }
    }

    /**
     * Cancel form and reset state.
     */
    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    /**
     * Cancel delete modal.
     */
    public function cancelDelete(): void
    {
        $this->showDeleteModal   = false;
        $this->deletingAddressId = null;
    }

    /**
     * Search address by zipcode using ViaCEP API.
     */
    public function searchZipcode(): void
    {
        $zipcode = preg_replace('/\D/', '', $this->zipcode);

        if (strlen($zipcode) !== 8) {
            return;
        }

        try {
            $response = file_get_contents("https://viacep.com.br/ws/{$zipcode}/json/");

            if ($response === false) {
                return;
            }

            $data = json_decode($response, true);

            if (isset($data['erro']) && $data['erro']) {
                $this->dispatch('notify', message: 'CEP nao encontrado.', type: 'error');

                return;
            }

            $this->street       = $data['logradouro'] ?? '';
            $this->neighborhood = $data['bairro'] ?? '';
            $this->city         = $data['localidade'] ?? '';
            $this->state        = $data['uf'] ?? '';
        } catch (\Exception $e) {
            // Silently fail - user can fill manually
        }
    }

    /**
     * Get validation rules.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'label'          => ['nullable', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'zipcode'        => ['required', 'string', 'size:9', 'regex:/^\d{5}-\d{3}$/'],
            'street'         => ['required', 'string', 'max:255'],
            'number'         => ['required', 'string', 'max:20'],
            'complement'     => ['nullable', 'string', 'max:255'],
            'neighborhood'   => ['required', 'string', 'max:255'],
            'city'           => ['required', 'string', 'max:255'],
            'state'          => ['required', 'string', 'size:2'],
            'is_default'     => ['boolean'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'recipient_name.required' => 'O nome do destinatario e obrigatorio.',
            'recipient_name.max'      => 'O nome do destinatario deve ter no maximo 255 caracteres.',
            'zipcode.required'        => 'O CEP e obrigatorio.',
            'zipcode.size'            => 'O CEP deve ter 9 caracteres (00000-000).',
            'zipcode.regex'           => 'O CEP deve estar no formato 00000-000.',
            'street.required'         => 'A rua e obrigatoria.',
            'street.max'              => 'A rua deve ter no maximo 255 caracteres.',
            'number.required'         => 'O numero e obrigatorio.',
            'number.max'              => 'O numero deve ter no maximo 20 caracteres.',
            'complement.max'          => 'O complemento deve ter no maximo 255 caracteres.',
            'neighborhood.required'   => 'O bairro e obrigatorio.',
            'neighborhood.max'        => 'O bairro deve ter no maximo 255 caracteres.',
            'city.required'           => 'A cidade e obrigatoria.',
            'city.max'                => 'A cidade deve ter no maximo 255 caracteres.',
            'state.required'          => 'O estado e obrigatorio.',
            'state.size'              => 'O estado deve ter 2 caracteres (UF).',
            'label.max'               => 'O rotulo deve ter no maximo 50 caracteres.',
        ];
    }

    /**
     * Reset form fields.
     */
    protected function resetForm(): void
    {
        $this->editingAddressId = null;
        $this->label            = '';
        $this->recipient_name   = '';
        $this->zipcode          = '';
        $this->street           = '';
        $this->number           = '';
        $this->complement       = '';
        $this->neighborhood     = '';
        $this->city             = '';
        $this->state            = '';
        $this->is_default       = false;
        $this->resetValidation();
    }

    /**
     * Find an address belonging to the authenticated user.
     */
    protected function findUserAddress(int $addressId): ?Address
    {
        return Auth::user()->addresses()->find($addressId);
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('livewire.customer.address-manager');
    }
}
