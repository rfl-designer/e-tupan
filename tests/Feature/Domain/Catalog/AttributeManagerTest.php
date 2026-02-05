<?php declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use App\Domain\Catalog\Livewire\Admin\AttributeManager;
use App\Domain\Catalog\Models\{Attribute, AttributeValue, ProductVariant};
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = Admin::factory()->master()->withTwoFactor()->create();
});

describe('AttributeManager component', function () {
    it('renders with attribute values', function () {
        $attribute = Attribute::factory()->create();
        $value     = AttributeValue::factory()->create(['attribute_id' => $attribute->id]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(AttributeManager::class, ['attribute' => $attribute])
            ->assertOk()
            ->assertSee($value->value);
    });

    it('can add a new value', function () {
        $attribute = Attribute::factory()->create();

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(AttributeManager::class, ['attribute' => $attribute])
            ->set('newValue', 'Pequeno')
            ->call('addValue')
            ->assertDispatched('notify', type: 'success', message: 'Valor adicionado!');

        $this->assertDatabaseHas('attribute_values', [
            'attribute_id' => $attribute->id,
            'value'        => 'Pequeno',
        ]);
    });

    it('validates required value when adding', function () {
        $attribute = Attribute::factory()->create();

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(AttributeManager::class, ['attribute' => $attribute])
            ->set('newValue', '')
            ->call('addValue')
            ->assertHasErrors('newValue');
    });

    it('requires color hex for color type attributes', function () {
        $attribute = Attribute::factory()->color()->create();

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(AttributeManager::class, ['attribute' => $attribute])
            ->set('newValue', 'Vermelho')
            ->set('newColorHex', null)
            ->call('addValue')
            ->assertHasErrors('newColorHex');
    });

    it('can add color value with hex code', function () {
        $attribute = Attribute::factory()->color()->create();

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(AttributeManager::class, ['attribute' => $attribute])
            ->set('newValue', 'Vermelho')
            ->set('newColorHex', '#FF0000')
            ->call('addValue')
            ->assertDispatched('notify', type: 'success', message: 'Valor adicionado!');

        $this->assertDatabaseHas('attribute_values', [
            'attribute_id' => $attribute->id,
            'value'        => 'Vermelho',
            'color_hex'    => '#FF0000',
        ]);
    });

    it('validates color hex format', function () {
        $attribute = Attribute::factory()->color()->create();

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(AttributeManager::class, ['attribute' => $attribute])
            ->set('newValue', 'Vermelho')
            ->set('newColorHex', 'invalid')
            ->call('addValue')
            ->assertHasErrors('newColorHex');
    });

    it('can start editing a value', function () {
        $attribute = Attribute::factory()->create();
        $value     = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
            'value'        => 'Original',
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(AttributeManager::class, ['attribute' => $attribute])
            ->call('startEditing', $value->id)
            ->assertSet('editingValueId', $value->id)
            ->assertSet('editingValue', 'Original');
    });

    it('can save edited value', function () {
        $attribute = Attribute::factory()->create();
        $value     = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
            'value'        => 'Original',
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(AttributeManager::class, ['attribute' => $attribute])
            ->call('startEditing', $value->id)
            ->set('editingValue', 'Updated')
            ->call('saveEditing')
            ->assertDispatched('notify', type: 'success', message: 'Valor atualizado!');

        $this->assertDatabaseHas('attribute_values', [
            'id'    => $value->id,
            'value' => 'Updated',
        ]);
    });

    it('can cancel editing', function () {
        $attribute = Attribute::factory()->create();
        $value     = AttributeValue::factory()->create(['attribute_id' => $attribute->id]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(AttributeManager::class, ['attribute' => $attribute])
            ->call('startEditing', $value->id)
            ->call('cancelEditing')
            ->assertSet('editingValueId', null)
            ->assertSet('editingValue', '');
    });

    it('can delete a value', function () {
        $attribute = Attribute::factory()->create();
        $value     = AttributeValue::factory()->create(['attribute_id' => $attribute->id]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(AttributeManager::class, ['attribute' => $attribute])
            ->call('deleteValue', $value->id)
            ->assertDispatched('notify', type: 'success', message: 'Valor removido!');

        $this->assertDatabaseMissing('attribute_values', ['id' => $value->id]);
    });

    it('prevents deleting value in use by variants', function () {
        $attribute = Attribute::factory()->create();
        $value     = AttributeValue::factory()->create(['attribute_id' => $attribute->id]);
        $variant   = ProductVariant::factory()->create();

        // Attach value to variant
        $variant->attributeValues()->attach($value->id);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(AttributeManager::class, ['attribute' => $attribute])
            ->call('deleteValue', $value->id)
            ->assertDispatched('notify', type: 'error', message: 'Este valor está em uso por variantes de produtos.');

        $this->assertDatabaseHas('attribute_values', ['id' => $value->id]);
    });

    it('can reorder values', function () {
        $attribute = Attribute::factory()->create();
        $value1    = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
            'position'     => 1,
        ]);
        $value2 = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
            'position'     => 2,
        ]);
        $value3 = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
            'position'     => 3,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(AttributeManager::class, ['attribute' => $attribute])
            ->call('reorderValues', [$value3->id, $value1->id, $value2->id])
            ->assertDispatched('notify', type: 'success', message: 'Ordem atualizada!');

        expect($value3->fresh()->position)->toBe(1)
            ->and($value1->fresh()->position)->toBe(2)
            ->and($value2->fresh()->position)->toBe(3);
    });

    it('sets correct position for new values', function () {
        $attribute = Attribute::factory()->create();
        AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
            'position'     => 5,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(AttributeManager::class, ['attribute' => $attribute])
            ->set('newValue', 'New Value')
            ->call('addValue');

        $newValue = AttributeValue::where('value', 'New Value')->first();
        expect($newValue->position)->toBe(6);
    });

    it('clears form after adding value', function () {
        $attribute = Attribute::factory()->color()->create();

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(AttributeManager::class, ['attribute' => $attribute])
            ->set('newValue', 'Azul')
            ->set('newColorHex', '#0000FF')
            ->call('addValue')
            ->assertSet('newValue', '')
            ->assertSet('newColorHex', null);
    });
});
