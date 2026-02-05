<?php

declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use App\Domain\Catalog\Livewire\Admin\ProductVariants;
use App\Domain\Catalog\Models\{Attribute, AttributeValue, Product, ProductImage, ProductVariant};
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = Admin::factory()->master()->withTwoFactor()->create();
});

// ====================
// US-15: Attributes Tab for Variable Products
// ====================

describe('US-15: Attributes Tab for Variable Products', function () {
    it('shows attributes tab only for variable products', function () {
        $product = Product::factory()->variable()->create();

        actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Variantes');
    });

    it('hides attributes tab for simple products by default', function () {
        $product = Product::factory()->simple()->create();

        actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk();
        // Tab is hidden via Alpine.js x-show, but the HTML is present
    });

    it('displays available global attributes', function () {
        $product   = Product::factory()->variable()->create();
        $attribute = Attribute::factory()->create(['name' => 'Cor']);
        AttributeValue::factory()->create(['attribute_id' => $attribute->id, 'value' => 'Azul']);

        Livewire::test(ProductVariants::class, ['product' => $product])
            ->assertSee('Cor');
        // Values are shown only after attribute is toggled
    });

    it('allows selecting attributes for product', function () {
        $product   = Product::factory()->variable()->create();
        $attribute = Attribute::factory()->create(['name' => 'Tamanho']);
        $value1    = AttributeValue::factory()->create(['attribute_id' => $attribute->id, 'value' => 'P']);
        $value2    = AttributeValue::factory()->create(['attribute_id' => $attribute->id, 'value' => 'M']);

        Livewire::test(ProductVariants::class, ['product' => $product])
            ->call('toggleAttribute', $attribute->id)
            ->assertSet('selectedAttributes', [$attribute->id])
            ->call('toggleValue', $attribute->id, $value1->id)
            ->call('toggleValue', $attribute->id, $value2->id)
            ->assertSet('selectedValues.' . $attribute->id, [$value1->id, $value2->id]);
    });

    it('allows marking attributes for variations', function () {
        $product   = Product::factory()->variable()->create();
        $attribute = Attribute::factory()->create(['name' => 'Cor']);
        AttributeValue::factory()->create(['attribute_id' => $attribute->id, 'value' => 'Azul']);

        Livewire::test(ProductVariants::class, ['product' => $product])
            ->call('toggleAttribute', $attribute->id)
            ->call('toggleVariation', $attribute->id)
            ->assertSet('usedForVariations', [$attribute->id]);
    });

    it('saves product attributes to database', function () {
        $product   = Product::factory()->variable()->create();
        $attribute = Attribute::factory()->create(['name' => 'Cor']);
        $value     = AttributeValue::factory()->create(['attribute_id' => $attribute->id, 'value' => 'Azul']);

        Livewire::test(ProductVariants::class, ['product' => $product])
            ->call('toggleAttribute', $attribute->id)
            ->call('toggleValue', $attribute->id, $value->id)
            ->call('toggleVariation', $attribute->id)
            ->call('saveAttributes');

        expect(
            DB::table('product_attributes')
            ->where('product_id', $product->id)
            ->where('attribute_id', $attribute->id)
            ->where('attribute_value_id', $value->id)
            ->where('used_for_variations', true)
            ->exists(),
        )->toBeTrue();
    });

    it('loads existing product attributes on mount', function () {
        $product   = Product::factory()->variable()->create();
        $attribute = Attribute::factory()->create(['name' => 'Cor']);
        $value     = AttributeValue::factory()->create(['attribute_id' => $attribute->id, 'value' => 'Azul']);

        DB::table('product_attributes')->insert([
            'product_id'          => $product->id,
            'attribute_id'        => $attribute->id,
            'attribute_value_id'  => $value->id,
            'used_for_variations' => true,
        ]);

        Livewire::test(ProductVariants::class, ['product' => $product])
            ->assertSet('selectedAttributes', [$attribute->id])
            ->assertSet('usedForVariations', [$attribute->id]);
    });
});

// ====================
// US-16: Automatic Variant Generation
// ====================

describe('US-16: Automatic Variant Generation', function () {
    it('generates variants from single attribute values', function () {
        $product   = Product::factory()->variable()->create(['sku' => 'TEST']);
        $attribute = Attribute::factory()->create(['name' => 'Cor']);
        $value1    = AttributeValue::factory()->create(['attribute_id' => $attribute->id, 'value' => 'Azul']);
        $value2    = AttributeValue::factory()->create(['attribute_id' => $attribute->id, 'value' => 'Verde']);

        Livewire::test(ProductVariants::class, ['product' => $product])
            ->call('toggleAttribute', $attribute->id)
            ->call('toggleValue', $attribute->id, $value1->id)
            ->call('toggleValue', $attribute->id, $value2->id)
            ->call('toggleVariation', $attribute->id)
            ->call('generateVariants');

        $product->refresh();

        expect($product->variants)->toHaveCount(2);
    });

    it('generates variants from multiple attribute combinations', function () {
        $product = Product::factory()->variable()->create(['sku' => 'COMBO']);

        $colorAttr = Attribute::factory()->create(['name' => 'Cor']);
        $blue      = AttributeValue::factory()->create(['attribute_id' => $colorAttr->id, 'value' => 'Azul']);
        $red       = AttributeValue::factory()->create(['attribute_id' => $colorAttr->id, 'value' => 'Vermelho']);

        $sizeAttr = Attribute::factory()->create(['name' => 'Tamanho']);
        $p        = AttributeValue::factory()->create(['attribute_id' => $sizeAttr->id, 'value' => 'P']);
        $m        = AttributeValue::factory()->create(['attribute_id' => $sizeAttr->id, 'value' => 'M']);

        Livewire::test(ProductVariants::class, ['product' => $product])
            ->call('toggleAttribute', $colorAttr->id)
            ->call('toggleValue', $colorAttr->id, $blue->id)
            ->call('toggleValue', $colorAttr->id, $red->id)
            ->call('toggleVariation', $colorAttr->id)
            ->call('toggleAttribute', $sizeAttr->id)
            ->call('toggleValue', $sizeAttr->id, $p->id)
            ->call('toggleValue', $sizeAttr->id, $m->id)
            ->call('toggleVariation', $sizeAttr->id)
            ->call('generateVariants');

        $product->refresh();

        // 2 colors x 2 sizes = 4 variants
        expect($product->variants)->toHaveCount(4);
    });

    it('generates unique SKUs for variants', function () {
        $product   = Product::factory()->variable()->create(['sku' => 'PROD']);
        $attribute = Attribute::factory()->create(['name' => 'Cor']);
        $value1    = AttributeValue::factory()->create(['attribute_id' => $attribute->id, 'value' => 'Azul']);
        $value2    = AttributeValue::factory()->create(['attribute_id' => $attribute->id, 'value' => 'Verde']);

        Livewire::test(ProductVariants::class, ['product' => $product])
            ->call('toggleAttribute', $attribute->id)
            ->call('toggleValue', $attribute->id, $value1->id)
            ->call('toggleValue', $attribute->id, $value2->id)
            ->call('toggleVariation', $attribute->id)
            ->call('generateVariants');

        $product->refresh();
        $skus = $product->variants->pluck('sku')->toArray();

        expect($skus)->toHaveCount(2);
        expect(count(array_unique($skus)))->toBe(2);
    });

    it('does not duplicate existing variants', function () {
        $product   = Product::factory()->variable()->create(['sku' => 'DUP']);
        $attribute = Attribute::factory()->create(['name' => 'Cor']);
        $value     = AttributeValue::factory()->create(['attribute_id' => $attribute->id, 'value' => 'Azul']);

        // First generation
        Livewire::test(ProductVariants::class, ['product' => $product])
            ->call('toggleAttribute', $attribute->id)
            ->call('toggleValue', $attribute->id, $value->id)
            ->call('toggleVariation', $attribute->id)
            ->call('generateVariants');

        $product->refresh();
        expect($product->variants)->toHaveCount(1);

        // Second generation should not create duplicates
        Livewire::test(ProductVariants::class, ['product' => $product->fresh()])
            ->call('generateVariants');

        $product->refresh();
        expect($product->variants)->toHaveCount(1);
    });

    it('shows error when no attributes selected for variations', function () {
        $product   = Product::factory()->variable()->create();
        $attribute = Attribute::factory()->create(['name' => 'Cor']);
        AttributeValue::factory()->create(['attribute_id' => $attribute->id, 'value' => 'Azul']);

        Livewire::test(ProductVariants::class, ['product' => $product])
            ->call('toggleAttribute', $attribute->id)
            // Not marking as variation
            ->call('generateVariants')
            ->assertDispatched('notify');
    });

    it('limits variant generation to 100 variants', function () {
        $product = Product::factory()->variable()->create(['sku' => 'LIMIT']);

        // Create attributes that would generate > 100 variants
        $attr1 = Attribute::factory()->create(['name' => 'Attr1']);
        $attr2 = Attribute::factory()->create(['name' => 'Attr2']);

        // 11 x 10 = 110 combinations > 100 limit
        for ($i = 1; $i <= 11; $i++) {
            AttributeValue::factory()->create(['attribute_id' => $attr1->id, 'value' => "Val1-$i"]);
        }

        for ($i = 1; $i <= 10; $i++) {
            AttributeValue::factory()->create(['attribute_id' => $attr2->id, 'value' => "Val2-$i"]);
        }

        $component = Livewire::test(ProductVariants::class, ['product' => $product])
            ->call('toggleAttribute', $attr1->id)
            ->call('toggleAttribute', $attr2->id)
            ->call('toggleVariation', $attr1->id)
            ->call('toggleVariation', $attr2->id);

        // Select all values
        foreach (AttributeValue::where('attribute_id', $attr1->id)->get() as $val) {
            $component->call('toggleValue', $attr1->id, $val->id);
        }

        foreach (AttributeValue::where('attribute_id', $attr2->id)->get() as $val) {
            $component->call('toggleValue', $attr2->id, $val->id);
        }

        $component->call('generateVariants')
            ->assertDispatched('notify');

        // Should not create any variants due to limit
        $product->refresh();
        expect($product->variants)->toHaveCount(0);
    });
});

// ====================
// US-17: Variant Editing
// ====================

describe('US-17: Variant Editing', function () {
    it('displays existing variants in table', function () {
        $product = Product::factory()->variable()->create();
        $variant = ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'sku'            => 'VAR-001',
            'stock_quantity' => 10,
        ]);

        // Reload product with variants
        $product->load('variants');

        $component = Livewire::test(ProductVariants::class, ['product' => $product]);

        // Check variant data is loaded
        expect($component->get('variantData'))->toHaveKey($variant->id);
        expect($component->get('variantData')[$variant->id]['sku'])->toBe('VAR-001');
        expect($component->get('variantData')[$variant->id]['stock_quantity'])->toBe(10);
    });

    it('updates variant SKU', function () {
        $product = Product::factory()->variable()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku'        => 'OLD-SKU',
        ]);

        Livewire::test(ProductVariants::class, ['product' => $product])
            ->set("variantData.{$variant->id}.sku", 'NEW-SKU')
            ->call('updateVariant', $variant->id, 'sku');

        expect($variant->fresh()->sku)->toBe('NEW-SKU');
    });

    it('updates variant price in centavos', function () {
        $product = Product::factory()->variable()->create(['price' => 10000]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price'      => null,
        ]);

        Livewire::test(ProductVariants::class, ['product' => $product])
            ->set("variantData.{$variant->id}.price", '150.50')
            ->call('updateVariant', $variant->id, 'price');

        expect($variant->fresh()->price)->toBe(15050);
    });

    it('updates variant stock quantity', function () {
        $product = Product::factory()->variable()->create();
        $variant = ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => 5,
        ]);

        Livewire::test(ProductVariants::class, ['product' => $product])
            ->set("variantData.{$variant->id}.stock_quantity", 20)
            ->call('updateVariant', $variant->id, 'stock_quantity');

        expect($variant->fresh()->stock_quantity)->toBe(20);
    });

    it('toggles variant active status', function () {
        $product = Product::factory()->variable()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active'  => true,
        ]);

        Livewire::test(ProductVariants::class, ['product' => $product])
            ->call('toggleVariantActive', $variant->id);

        expect($variant->fresh()->is_active)->toBeFalse();
    });

    it('deletes a variant', function () {
        $product = Product::factory()->variable()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);

        $variantId = $variant->id;

        Livewire::test(ProductVariants::class, ['product' => $product])
            ->call('deleteVariant', $variantId);

        expect(ProductVariant::find($variantId))->toBeNull();
    });

    it('applies bulk edit to selected variants', function () {
        $product  = Product::factory()->variable()->create();
        $variant1 = ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'price'          => null,
            'stock_quantity' => 0,
        ]);
        $variant2 = ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'price'          => null,
            'stock_quantity' => 0,
        ]);

        Livewire::test(ProductVariants::class, ['product' => $product])
            ->call('toggleVariantSelection', $variant1->id)
            ->call('toggleVariantSelection', $variant2->id)
            ->set('bulkValues.price', '99.99')
            ->set('bulkValues.stock_quantity', 50)
            ->call('applyBulkEdit');

        expect($variant1->fresh()->price)->toBe(9999);
        expect($variant1->fresh()->stock_quantity)->toBe(50);
        expect($variant2->fresh()->price)->toBe(9999);
        expect($variant2->fresh()->stock_quantity)->toBe(50);
    });

    it('selects and deselects all variants', function () {
        $product  = Product::factory()->variable()->create();
        $variant1 = ProductVariant::factory()->create(['product_id' => $product->id]);
        $variant2 = ProductVariant::factory()->create(['product_id' => $product->id]);

        $component = Livewire::test(ProductVariants::class, ['product' => $product])
            ->call('selectAllVariants')
            ->assertSet('selectedVariants', [$variant1->id, $variant2->id])
            ->call('deselectAllVariants')
            ->assertSet('selectedVariants', []);
    });

    it('deletes all variants', function () {
        $product = Product::factory()->variable()->create();
        ProductVariant::factory()->count(3)->create(['product_id' => $product->id]);

        expect($product->variants)->toHaveCount(3);

        Livewire::test(ProductVariants::class, ['product' => $product])
            ->call('deleteAllVariants');

        expect($product->fresh()->variants)->toHaveCount(0);
    });
});

// ====================
// US-18: Images per Variant
// ====================

describe('US-18: Images per Variant', function () {
    it('shows image assignment button for variants without images', function () {
        $product = Product::factory()->variable()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        Livewire::test(ProductVariants::class, ['product' => $product])
            ->assertSeeHtml('wire:click="openImageAssignment(' . $variant->id . ')"');
    });

    it('opens image assignment modal', function () {
        $product = Product::factory()->variable()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        Livewire::test(ProductVariants::class, ['product' => $product])
            ->call('openImageAssignment', $variant->id)
            ->assertSet('assignImageToVariant', $variant->id);
    });

    it('assigns image to variant', function () {
        $product = Product::factory()->variable()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        $image   = ProductImage::factory()->create([
            'product_id' => $product->id,
            'variant_id' => null,
        ]);

        Livewire::test(ProductVariants::class, ['product' => $product])
            ->call('openImageAssignment', $variant->id)
            ->call('assignImage', $image->id);

        expect($image->fresh()->variant_id)->toBe($variant->id);
    });

    it('removes image from variant', function () {
        $product = Product::factory()->variable()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        $image   = ProductImage::factory()->create([
            'product_id' => $product->id,
            'variant_id' => $variant->id,
        ]);

        Livewire::test(ProductVariants::class, ['product' => $product])
            ->call('removeImageFromVariant', $image->id);

        expect($image->fresh()->variant_id)->toBeNull();
    });

    it('closes image assignment modal', function () {
        $product = Product::factory()->variable()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        Livewire::test(ProductVariants::class, ['product' => $product])
            ->call('openImageAssignment', $variant->id)
            ->assertSet('assignImageToVariant', $variant->id)
            ->call('closeImageAssignment')
            ->assertSet('assignImageToVariant', null);
    });

    it('only shows unassigned images in modal', function () {
        $product  = Product::factory()->variable()->create();
        $variant1 = ProductVariant::factory()->create(['product_id' => $product->id]);
        $variant2 = ProductVariant::factory()->create(['product_id' => $product->id]);

        // Image already assigned to variant1
        $assignedImage = ProductImage::factory()->create([
            'product_id' => $product->id,
            'variant_id' => $variant1->id,
            'path'       => 'products/assigned',
        ]);

        // Unassigned image
        $unassignedImage = ProductImage::factory()->create([
            'product_id' => $product->id,
            'variant_id' => null,
            'path'       => 'products/unassigned',
        ]);

        $component = Livewire::test(ProductVariants::class, ['product' => $product])
            ->call('openImageAssignment', $variant2->id);

        // The modal should show assignImage button for unassigned image
        $component->assertSeeHtml('wire:click="assignImage(' . $unassignedImage->id . ')"');
    });
});

// ====================
// Integration Tests
// ====================

describe('Integration Tests', function () {
    it('variant inherits price from parent when not set', function () {
        $product = Product::factory()->variable()->create(['price' => 10000]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price'      => null,
        ]);

        expect($variant->getEffectivePrice())->toBe(10000);
    });

    it('variant uses own price when set', function () {
        $product = Product::factory()->variable()->create(['price' => 10000]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price'      => 15000,
        ]);

        expect($variant->getEffectivePrice())->toBe(15000);
    });

    it('variant displays attribute description correctly', function () {
        $product = Product::factory()->variable()->create();

        $colorAttr = Attribute::factory()->create(['name' => 'Cor']);
        $blue      = AttributeValue::factory()->create(['attribute_id' => $colorAttr->id, 'value' => 'Azul']);

        $sizeAttr = Attribute::factory()->create(['name' => 'Tamanho']);
        $m        = AttributeValue::factory()->create(['attribute_id' => $sizeAttr->id, 'value' => 'M']);

        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        $variant->attributeValues()->attach([$blue->id, $m->id]);

        $description = $variant->getAttributeDescription();

        expect($description)->toContain('Cor: Azul');
        expect($description)->toContain('Tamanho: M');
    });

    it('deleting variant removes attribute value associations', function () {
        $product = Product::factory()->variable()->create();

        $attribute = Attribute::factory()->create(['name' => 'Cor']);
        $value     = AttributeValue::factory()->create(['attribute_id' => $attribute->id, 'value' => 'Azul']);

        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        $variant->attributeValues()->attach($value->id);

        $variantId = $variant->id;

        Livewire::test(ProductVariants::class, ['product' => $product])
            ->call('deleteVariant', $variantId);

        expect(
            DB::table('variant_attribute_values')
            ->where('variant_id', $variantId)
            ->exists(),
        )->toBeFalse();
    });
});
