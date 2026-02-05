<?php declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use App\Domain\Catalog\Enums\AttributeType;
use App\Domain\Catalog\Models\{Attribute, AttributeValue, Product};

beforeEach(function () {
    $this->admin = Admin::factory()->master()->withTwoFactor()->create();
});

describe('AttributeController@index', function () {
    it('requires authentication', function () {
        $this->get(route('admin.attributes.index'))
            ->assertRedirect(route('admin.login'));
    });

    it('displays attributes list', function () {
        $attribute = Attribute::factory()->create();

        actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.attributes.index'))
            ->assertOk()
            ->assertViewIs('admin.attributes.index')
            ->assertViewHas('attributes');
    });

    it('shows attribute values count', function () {
        $attribute = Attribute::factory()->create();
        AttributeValue::factory()->count(3)->create(['attribute_id' => $attribute->id]);

        actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.attributes.index'))
            ->assertOk()
            ->assertViewHas('attributes', function ($attributes) {
                return $attributes->first()->values_count === 3;
            });
    });
});

describe('AttributeController@create', function () {
    it('requires authentication', function () {
        $this->get(route('admin.attributes.create'))
            ->assertRedirect(route('admin.login'));
    });

    it('displays create form', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.attributes.create'))
            ->assertOk()
            ->assertViewIs('admin.attributes.create')
            ->assertViewHas('types');
    });

    it('shows all attribute types', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.attributes.create'))
            ->assertViewHas('types', function ($types) {
                return count($types) === 3
                    && isset($types['select'])
                    && isset($types['color'])
                    && isset($types['text']);
            });
    });
});

describe('AttributeController@store', function () {
    it('requires authentication', function () {
        $this->post(route('admin.attributes.store'), [])
            ->assertRedirect(route('admin.login'));
    });

    it('creates an attribute with valid data', function () {
        $data = [
            'name' => 'Tamanho',
            'slug' => 'tamanho',
            'type' => 'select',
        ];

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.attributes.store'), $data)
            ->assertRedirect(route('admin.attributes.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attributes', [
            'name' => 'Tamanho',
            'slug' => 'tamanho',
            'type' => 'select',
        ]);
    });

    it('creates a color type attribute', function () {
        $data = [
            'name' => 'Cor',
            'type' => 'color',
        ];

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.attributes.store'), $data)
            ->assertRedirect(route('admin.attributes.index'));

        $this->assertDatabaseHas('attributes', [
            'name' => 'Cor',
            'type' => 'color',
        ]);
    });

    it('creates a text type attribute', function () {
        $data = [
            'name' => 'Personalizacao',
            'type' => 'text',
        ];

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.attributes.store'), $data)
            ->assertRedirect(route('admin.attributes.index'));

        $this->assertDatabaseHas('attributes', [
            'name' => 'Personalizacao',
            'type' => 'text',
        ]);
    });

    it('generates slug automatically when not provided', function () {
        $data = [
            'name' => 'Material do Produto',
            'type' => 'select',
        ];

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.attributes.store'), $data)
            ->assertRedirect(route('admin.attributes.index'));

        $this->assertDatabaseHas('attributes', [
            'name' => 'Material do Produto',
            'slug' => 'material-do-produto',
        ]);
    });

    it('validates required name', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.attributes.store'), ['type' => 'select'])
            ->assertSessionHasErrors('name');
    });

    it('validates required type', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.attributes.store'), ['name' => 'Test'])
            ->assertSessionHasErrors('type');
    });

    it('validates unique slug', function () {
        Attribute::factory()->create(['slug' => 'existing-slug']);

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.attributes.store'), [
                'name' => 'New Attribute',
                'slug' => 'existing-slug',
                'type' => 'select',
            ])
            ->assertSessionHasErrors('slug');
    });

    it('validates type enum', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.attributes.store'), [
                'name' => 'Test',
                'type' => 'invalid-type',
            ])
            ->assertSessionHasErrors('type');
    });

    it('validates name max length', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.attributes.store'), [
                'name' => str_repeat('a', 256),
                'type' => 'select',
            ])
            ->assertSessionHasErrors('name');
    });
});

describe('AttributeController@edit', function () {
    it('requires authentication', function () {
        $attribute = Attribute::factory()->create();

        $this->get(route('admin.attributes.edit', $attribute))
            ->assertRedirect(route('admin.login'));
    });

    it('displays edit form', function () {
        $attribute = Attribute::factory()->create();

        actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.attributes.edit', $attribute))
            ->assertOk()
            ->assertViewIs('admin.attributes.edit')
            ->assertViewHas('attribute')
            ->assertViewHas('types');
    });

    it('loads attribute values', function () {
        $attribute = Attribute::factory()->create();
        AttributeValue::factory()->count(2)->create(['attribute_id' => $attribute->id]);

        actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.attributes.edit', $attribute))
            ->assertViewHas('attribute', function ($attr) {
                return $attr->values->count() === 2;
            });
    });
});

describe('AttributeController@update', function () {
    it('requires authentication', function () {
        $attribute = Attribute::factory()->create();

        $this->put(route('admin.attributes.update', $attribute), [])
            ->assertRedirect(route('admin.login'));
    });

    it('updates an attribute', function () {
        $attribute = Attribute::factory()->create(['name' => 'Old Name']);

        actingAsAdminWith2FA($this, $this->admin)
            ->put(route('admin.attributes.update', $attribute), [
                'name' => 'New Name',
                'slug' => $attribute->slug,
                'type' => $attribute->type->value,
            ])
            ->assertRedirect(route('admin.attributes.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attributes', [
            'id'   => $attribute->id,
            'name' => 'New Name',
        ]);
    });

    it('allows same slug for same attribute', function () {
        $attribute = Attribute::factory()->create(['slug' => 'my-slug']);

        actingAsAdminWith2FA($this, $this->admin)
            ->put(route('admin.attributes.update', $attribute), [
                'name' => 'Updated Name',
                'slug' => 'my-slug',
                'type' => $attribute->type->value,
            ])
            ->assertRedirect(route('admin.attributes.index'))
            ->assertSessionHas('success');
    });

    it('prevents duplicate slug from other attribute', function () {
        Attribute::factory()->create(['slug' => 'existing-slug']);
        $attribute = Attribute::factory()->create(['slug' => 'my-slug']);

        actingAsAdminWith2FA($this, $this->admin)
            ->put(route('admin.attributes.update', $attribute), [
                'name' => 'Updated Name',
                'slug' => 'existing-slug',
                'type' => $attribute->type->value,
            ])
            ->assertSessionHasErrors('slug');
    });

    it('can change attribute type', function () {
        $attribute = Attribute::factory()->create(['type' => AttributeType::Select]);

        actingAsAdminWith2FA($this, $this->admin)
            ->put(route('admin.attributes.update', $attribute), [
                'name' => $attribute->name,
                'slug' => $attribute->slug,
                'type' => 'color',
            ])
            ->assertRedirect(route('admin.attributes.index'));

        $this->assertDatabaseHas('attributes', [
            'id'   => $attribute->id,
            'type' => 'color',
        ]);
    });
});

describe('AttributeController@destroy', function () {
    it('requires authentication', function () {
        $attribute = Attribute::factory()->create();

        $this->delete(route('admin.attributes.destroy', $attribute))
            ->assertRedirect(route('admin.login'));
    });

    it('deletes an attribute without products', function () {
        $attribute = Attribute::factory()->create();

        actingAsAdminWith2FA($this, $this->admin)
            ->delete(route('admin.attributes.destroy', $attribute))
            ->assertRedirect(route('admin.attributes.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('attributes', ['id' => $attribute->id]);
    });

    it('deletes attribute values when attribute is deleted', function () {
        $attribute = Attribute::factory()->create();
        $value     = AttributeValue::factory()->create(['attribute_id' => $attribute->id]);

        actingAsAdminWith2FA($this, $this->admin)
            ->delete(route('admin.attributes.destroy', $attribute))
            ->assertRedirect(route('admin.attributes.index'));

        $this->assertDatabaseMissing('attributes', ['id' => $attribute->id]);
        $this->assertDatabaseMissing('attribute_values', ['id' => $value->id]);
    });

    it('prevents deleting attribute in use by products', function () {
        $attribute = Attribute::factory()->create();
        $value     = AttributeValue::factory()->create(['attribute_id' => $attribute->id]);
        $product   = Product::factory()->create();

        // Attach attribute to product via productAttributes relationship
        $product->productAttributes()->attach($attribute->id, [
            'attribute_value_id'  => $value->id,
            'used_for_variations' => false,
        ]);

        actingAsAdminWith2FA($this, $this->admin)
            ->delete(route('admin.attributes.destroy', $attribute))
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('attributes', ['id' => $attribute->id]);
    });
});
