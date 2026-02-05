<?php declare(strict_types = 1);

use App\Domain\Customer\Livewire\AddressManager;
use App\Domain\Customer\Models\Address;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('guest cannot access addresses page', function () {
    $this->get(route('customer.addresses'))
        ->assertRedirect(route('login'));
});

test('authenticated user can view addresses page', function () {
    $this->actingAs($this->user)
        ->get(route('customer.addresses'))
        ->assertOk()
        ->assertSeeLivewire(AddressManager::class);
});

test('can view addresses list', function () {
    $addresses = Address::factory()
        ->count(2)
        ->create(['user_id' => $this->user->id]);

    Livewire::actingAs($this->user)
        ->test(AddressManager::class)
        ->assertSee($addresses[0]->recipient_name)
        ->assertSee($addresses[1]->recipient_name)
        ->assertSee($addresses[0]->street)
        ->assertSee($addresses[1]->street);
});

test('shows empty state when no addresses', function () {
    Livewire::actingAs($this->user)
        ->test(AddressManager::class)
        ->assertSee('Nenhum endereco cadastrado');
});

test('can create address', function () {
    Livewire::actingAs($this->user)
        ->test(AddressManager::class)
        ->call('create')
        ->assertSet('showForm', true)
        ->set('recipient_name', 'Joao Silva')
        ->set('zipcode', '01310-100')
        ->set('street', 'Avenida Paulista')
        ->set('number', '1000')
        ->set('complement', 'Sala 101')
        ->set('neighborhood', 'Bela Vista')
        ->set('city', 'Sao Paulo')
        ->set('state', 'SP')
        ->set('label', 'Trabalho')
        ->set('is_default', true)
        ->call('save')
        ->assertSet('showForm', false)
        ->assertDispatched('notify');

    expect(Address::where('user_id', $this->user->id)->count())->toBe(1);

    $address = Address::where('user_id', $this->user->id)->first();
    expect($address->recipient_name)->toBe('Joao Silva')
        ->and($address->zipcode)->toBe('01310-100')
        ->and($address->street)->toBe('Avenida Paulista')
        ->and($address->number)->toBe('1000')
        ->and($address->complement)->toBe('Sala 101')
        ->and($address->neighborhood)->toBe('Bela Vista')
        ->and($address->city)->toBe('Sao Paulo')
        ->and($address->state)->toBe('SP')
        ->and($address->label)->toBe('Trabalho')
        ->and($address->is_default)->toBeTrue();
});

test('can edit address', function () {
    $address = Address::factory()->create([
        'user_id'        => $this->user->id,
        'recipient_name' => 'Nome Original',
        'street'         => 'Rua Original',
    ]);

    Livewire::actingAs($this->user)
        ->test(AddressManager::class)
        ->call('edit', $address->id)
        ->assertSet('showForm', true)
        ->assertSet('editingAddressId', $address->id)
        ->assertSet('recipient_name', 'Nome Original')
        ->assertSet('street', 'Rua Original')
        ->set('recipient_name', 'Nome Atualizado')
        ->set('street', 'Rua Atualizada')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertDispatched('notify');

    $address->refresh();
    expect($address->recipient_name)->toBe('Nome Atualizado')
        ->and($address->street)->toBe('Rua Atualizada');
});

test('can delete address', function () {
    $address = Address::factory()->create(['user_id' => $this->user->id]);

    expect(Address::where('id', $address->id)->exists())->toBeTrue();

    Livewire::actingAs($this->user)
        ->test(AddressManager::class)
        ->call('confirmDelete', $address->id)
        ->assertSet('showDeleteModal', true)
        ->assertSet('deletingAddressId', $address->id)
        ->call('delete')
        ->assertSet('showDeleteModal', false)
        ->assertDispatched('notify');

    expect(Address::where('id', $address->id)->exists())->toBeFalse();
});

test('can cancel delete', function () {
    $address = Address::factory()->create(['user_id' => $this->user->id]);

    Livewire::actingAs($this->user)
        ->test(AddressManager::class)
        ->call('confirmDelete', $address->id)
        ->assertSet('showDeleteModal', true)
        ->call('cancelDelete')
        ->assertSet('showDeleteModal', false)
        ->assertSet('deletingAddressId', null);

    expect(Address::where('id', $address->id)->exists())->toBeTrue();
});

test('can set default address', function () {
    $address1 = Address::factory()->default()->create(['user_id' => $this->user->id]);
    $address2 = Address::factory()->create(['user_id' => $this->user->id]);

    expect($address1->is_default)->toBeTrue()
        ->and($address2->is_default)->toBeFalse();

    Livewire::actingAs($this->user)
        ->test(AddressManager::class)
        ->call('setDefault', $address2->id)
        ->assertDispatched('notify');

    $address1->refresh();
    $address2->refresh();

    expect($address1->is_default)->toBeFalse()
        ->and($address2->is_default)->toBeTrue();
});

test('cannot create more than 5 addresses', function () {
    Address::factory()->count(5)->create(['user_id' => $this->user->id]);

    Livewire::actingAs($this->user)
        ->test(AddressManager::class)
        ->call('create')
        ->assertSet('showForm', false)
        ->assertDispatched('notify', message: 'Voce atingiu o limite maximo de 5 enderecos.', type: 'error');
});

test('cannot save when limit reached', function () {
    Address::factory()->count(5)->create(['user_id' => $this->user->id]);

    $component = Livewire::actingAs($this->user)
        ->test(AddressManager::class);

    // Force showForm to true to bypass create() check
    $component->set('showForm', true)
        ->set('recipient_name', 'Novo Nome')
        ->set('zipcode', '01310-100')
        ->set('street', 'Nova Rua')
        ->set('number', '123')
        ->set('neighborhood', 'Centro')
        ->set('city', 'Sao Paulo')
        ->set('state', 'SP')
        ->call('save')
        ->assertDispatched('notify', message: 'Voce atingiu o limite maximo de 5 enderecos.', type: 'error');

    expect(Address::where('user_id', $this->user->id)->count())->toBe(5);
});

test('validates required fields', function () {
    Livewire::actingAs($this->user)
        ->test(AddressManager::class)
        ->call('create')
        ->set('recipient_name', '')
        ->set('zipcode', '')
        ->set('street', '')
        ->set('number', '')
        ->set('neighborhood', '')
        ->set('city', '')
        ->set('state', '')
        ->call('save')
        ->assertHasErrors([
            'recipient_name',
            'zipcode',
            'street',
            'number',
            'neighborhood',
            'city',
            'state',
        ]);
});

test('validates zipcode format', function () {
    Livewire::actingAs($this->user)
        ->test(AddressManager::class)
        ->call('create')
        ->set('zipcode', '12345678')
        ->call('save')
        ->assertHasErrors(['zipcode']);

    Livewire::actingAs($this->user)
        ->test(AddressManager::class)
        ->call('create')
        ->set('zipcode', '1234-567')
        ->call('save')
        ->assertHasErrors(['zipcode']);

    Livewire::actingAs($this->user)
        ->test(AddressManager::class)
        ->call('create')
        ->set('zipcode', '12345-67')
        ->call('save')
        ->assertHasErrors(['zipcode']);
});

test('accepts valid zipcode format', function () {
    Livewire::actingAs($this->user)
        ->test(AddressManager::class)
        ->call('create')
        ->set('recipient_name', 'Joao Silva')
        ->set('zipcode', '01310-100')
        ->set('street', 'Avenida Paulista')
        ->set('number', '1000')
        ->set('neighborhood', 'Bela Vista')
        ->set('city', 'Sao Paulo')
        ->set('state', 'SP')
        ->call('save')
        ->assertHasNoErrors(['zipcode']);
});

test('validates state must be 2 characters', function () {
    Livewire::actingAs($this->user)
        ->test(AddressManager::class)
        ->call('create')
        ->set('state', 'SAO')
        ->call('save')
        ->assertHasErrors(['state']);

    Livewire::actingAs($this->user)
        ->test(AddressManager::class)
        ->call('create')
        ->set('state', 'S')
        ->call('save')
        ->assertHasErrors(['state']);
});

test('can cancel form', function () {
    Livewire::actingAs($this->user)
        ->test(AddressManager::class)
        ->call('create')
        ->assertSet('showForm', true)
        ->set('recipient_name', 'Teste')
        ->call('cancel')
        ->assertSet('showForm', false)
        ->assertSet('recipient_name', '')
        ->assertSet('editingAddressId', null);
});

test('cannot edit address from another user', function () {
    $otherUser = User::factory()->create();
    $address   = Address::factory()->create(['user_id' => $otherUser->id]);

    Livewire::actingAs($this->user)
        ->test(AddressManager::class)
        ->call('edit', $address->id)
        ->assertSet('showForm', false)
        ->assertSet('editingAddressId', null);
});

test('cannot delete address from another user', function () {
    $otherUser = User::factory()->create();
    $address   = Address::factory()->create(['user_id' => $otherUser->id]);

    Livewire::actingAs($this->user)
        ->test(AddressManager::class)
        ->set('deletingAddressId', $address->id)
        ->set('showDeleteModal', true)
        ->call('delete');

    expect(Address::where('id', $address->id)->exists())->toBeTrue();
});

test('cannot set default on address from another user', function () {
    $otherUser = User::factory()->create();
    $address   = Address::factory()->create([
        'user_id'    => $otherUser->id,
        'is_default' => false,
    ]);

    Livewire::actingAs($this->user)
        ->test(AddressManager::class)
        ->call('setDefault', $address->id);

    $address->refresh();
    expect($address->is_default)->toBeFalse();
});

test('addresses are ordered by default first then by created_at desc', function () {
    $address1 = Address::factory()->create([
        'user_id'    => $this->user->id,
        'is_default' => false,
        'created_at' => now()->subDays(2),
    ]);

    $address2 = Address::factory()->default()->create([
        'user_id'    => $this->user->id,
        'created_at' => now()->subDay(),
    ]);

    $address3 = Address::factory()->create([
        'user_id'    => $this->user->id,
        'is_default' => false,
        'created_at' => now(),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(AddressManager::class);

    $addresses = $component->get('addresses');

    expect($addresses[0]->id)->toBe($address2->id) // default first
        ->and($addresses[1]->id)->toBe($address3->id) // then newest
        ->and($addresses[2]->id)->toBe($address1->id); // then oldest
});

test('label is optional and can be null', function () {
    Livewire::actingAs($this->user)
        ->test(AddressManager::class)
        ->call('create')
        ->set('recipient_name', 'Joao Silva')
        ->set('zipcode', '01310-100')
        ->set('street', 'Avenida Paulista')
        ->set('number', '1000')
        ->set('neighborhood', 'Bela Vista')
        ->set('city', 'Sao Paulo')
        ->set('state', 'SP')
        ->set('label', '')
        ->call('save')
        ->assertHasNoErrors();

    $address = Address::where('user_id', $this->user->id)->first();
    expect($address->label)->toBeNull();
});

test('complement is optional and can be null', function () {
    Livewire::actingAs($this->user)
        ->test(AddressManager::class)
        ->call('create')
        ->set('recipient_name', 'Joao Silva')
        ->set('zipcode', '01310-100')
        ->set('street', 'Avenida Paulista')
        ->set('number', '1000')
        ->set('neighborhood', 'Bela Vista')
        ->set('city', 'Sao Paulo')
        ->set('state', 'SP')
        ->set('complement', '')
        ->call('save')
        ->assertHasNoErrors();

    $address = Address::where('user_id', $this->user->id)->first();
    expect($address->complement)->toBeNull();
});

test('shows add button when under limit', function () {
    Address::factory()->count(4)->create(['user_id' => $this->user->id]);

    Livewire::actingAs($this->user)
        ->test(AddressManager::class)
        ->assertSee('Adicionar Endereco');
});

test('hides add button when at limit', function () {
    Address::factory()->count(5)->create(['user_id' => $this->user->id]);

    Livewire::actingAs($this->user)
        ->test(AddressManager::class)
        ->assertSee('Limite de 5 enderecos atingido')
        ->assertDontSee('Adicionar Endereco');
});

test('shows default badge on default address', function () {
    Address::factory()->default()->create([
        'user_id'        => $this->user->id,
        'recipient_name' => 'Endereco Padrao',
    ]);

    Livewire::actingAs($this->user)
        ->test(AddressManager::class)
        ->assertSee('Padrao');
});
