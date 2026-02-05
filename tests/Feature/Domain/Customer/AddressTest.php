<?php declare(strict_types = 1);

use App\Domain\Customer\Models\Address;
use App\Models\User;

it('belongs to a user', function () {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    expect($address->user)->toBeInstanceOf(User::class)
        ->and($address->user->id)->toBe($user->id);
});

it('user has many addresses', function () {
    $user      = User::factory()->create();
    $addresses = Address::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->addresses)->toHaveCount(3)
        ->and($user->addresses->first())->toBeInstanceOf(Address::class);
});

it('only one address can be default per user', function () {
    $user = User::factory()->create();

    $address1 = Address::factory()->default()->create(['user_id' => $user->id]);
    $address2 = Address::factory()->default()->create(['user_id' => $user->id]);

    $address1->refresh();
    $address2->refresh();

    expect($address1->is_default)->toBeFalse()
        ->and($address2->is_default)->toBeTrue();
});

it('setting new default unsets previous default', function () {
    $user = User::factory()->create();

    $address1 = Address::factory()->default()->create(['user_id' => $user->id]);

    expect($address1->is_default)->toBeTrue();

    $address2             = Address::factory()->create(['user_id' => $user->id]);
    $address2->is_default = true;
    $address2->save();

    $address1->refresh();

    expect($address1->is_default)->toBeFalse()
        ->and($address2->is_default)->toBeTrue();
});

it('updating existing address to default unsets others', function () {
    $user = User::factory()->create();

    $address1 = Address::factory()->default()->create(['user_id' => $user->id]);
    $address2 = Address::factory()->create(['user_id' => $user->id]);

    expect($address1->is_default)->toBeTrue()
        ->and($address2->is_default)->toBeFalse();

    $address2->update(['is_default' => true]);

    $address1->refresh();

    expect($address1->is_default)->toBeFalse()
        ->and($address2->is_default)->toBeTrue();
});

it('address is deleted when user is deleted (cascade)', function () {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    expect(Address::where('id', $address->id)->exists())->toBeTrue();

    $user->delete();

    expect(Address::where('id', $address->id)->exists())->toBeFalse();
});

it('different users can have their own default addresses', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $address1 = Address::factory()->default()->create(['user_id' => $user1->id]);
    $address2 = Address::factory()->default()->create(['user_id' => $user2->id]);

    $address1->refresh();
    $address2->refresh();

    expect($address1->is_default)->toBeTrue()
        ->and($address2->is_default)->toBeTrue();
});

it('can get user default address', function () {
    $user = User::factory()->create();

    Address::factory()->create(['user_id' => $user->id]);
    $defaultAddress = Address::factory()->default()->create(['user_id' => $user->id]);
    Address::factory()->create(['user_id' => $user->id]);

    expect($user->defaultAddress())->toBeInstanceOf(Address::class)
        ->and($user->defaultAddress()->id)->toBe($defaultAddress->id);
});

it('returns null when user has no default address', function () {
    $user = User::factory()->create();

    Address::factory()->create(['user_id' => $user->id, 'is_default' => false]);

    expect($user->defaultAddress())->toBeNull();
});

it('can scope default addresses', function () {
    $user = User::factory()->create();

    Address::factory()->count(2)->create(['user_id' => $user->id]);
    $defaultAddress = Address::factory()->default()->create(['user_id' => $user->id]);

    $defaults = Address::default()->get();

    expect($defaults)->toHaveCount(1)
        ->and($defaults->first()->id)->toBe($defaultAddress->id);
});

it('generates full address attribute correctly', function () {
    $address = Address::factory()->create([
        'street'       => 'Rua das Flores',
        'number'       => '123',
        'complement'   => 'Apto 45',
        'neighborhood' => 'Centro',
        'city'         => 'Sao Paulo',
        'state'        => 'SP',
        'zipcode'      => '01234-567',
    ]);

    expect($address->full_address)->toBe('Rua das Flores, 123, Apto 45, Centro, Sao Paulo/SP, 01234-567');
});

it('generates full address without complement', function () {
    $address = Address::factory()->create([
        'street'       => 'Rua das Flores',
        'number'       => '123',
        'complement'   => null,
        'neighborhood' => 'Centro',
        'city'         => 'Sao Paulo',
        'state'        => 'SP',
        'zipcode'      => '01234-567',
    ]);

    expect($address->full_address)->toBe('Rua das Flores, 123, Centro, Sao Paulo/SP, 01234-567');
});

it('casts is_default to boolean', function () {
    $address = Address::factory()->create(['is_default' => 1]);

    expect($address->is_default)->toBeBool()
        ->and($address->is_default)->toBeTrue();
});

it('has correct fillable attributes', function () {
    $address = new Address();

    expect($address->getFillable())->toBe([
        'user_id',
        'label',
        'recipient_name',
        'zipcode',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'is_default',
    ]);
});
