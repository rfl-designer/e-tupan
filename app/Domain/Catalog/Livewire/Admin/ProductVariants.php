<?php

declare(strict_types = 1);

namespace App\Domain\Catalog\Livewire\Admin;

use App\Domain\Catalog\Models\{Attribute, AttributeValue, Product, ProductImage, ProductVariant};
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class ProductVariants extends Component
{
    public Product $product;

    /**
     * All available global attributes.
     *
     * @var Collection<int, Attribute>
     */
    public Collection $availableAttributes;

    /**
     * Selected attribute IDs for this product.
     *
     * @var array<int>
     */
    public array $selectedAttributes = [];

    /**
     * Selected attribute value IDs per attribute.
     *
     * @var array<int, array<int>>
     */
    public array $selectedValues = [];

    /**
     * Attributes marked for variations (not just informational).
     *
     * @var array<int>
     */
    public array $usedForVariations = [];

    /**
     * Editing variant data.
     *
     * @var array<int, array{sku: string, price: ?string, stock_quantity: int, weight: ?string, length: ?string, width: ?string, height: ?string, is_active: bool}>
     */
    public array $variantData = [];

    /**
     * Selected variants for bulk actions.
     *
     * @var array<int>
     */
    public array $selectedVariants = [];

    /**
     * Bulk edit mode.
     */
    public bool $showBulkEdit = false;

    /**
     * Bulk edit values.
     *
     * @var array{price: ?string, stock_quantity: ?int}
     */
    public array $bulkValues = [
        'price'          => null,
        'stock_quantity' => null,
    ];

    /**
     * Variant to assign image to.
     */
    public ?int $assignImageToVariant = null;

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->loadAvailableAttributes();
        $this->loadProductAttributes();
        $this->loadVariants();
    }

    /**
     * Load all available global attributes.
     */
    private function loadAvailableAttributes(): void
    {
        $this->availableAttributes = Attribute::query()
            ->with('values')
            ->orderBy('position')
            ->get();
    }

    /**
     * Load attributes already assigned to this product.
     */
    private function loadProductAttributes(): void
    {
        $productAttributes = DB::table('product_attributes')
            ->where('product_id', $this->product->id)
            ->get();

        $this->selectedAttributes = $productAttributes->pluck('attribute_id')->unique()->toArray();
        $this->usedForVariations  = $productAttributes
            ->where('used_for_variations', true)
            ->pluck('attribute_id')
            ->unique()
            ->toArray();

        // Group values by attribute
        foreach ($productAttributes as $pa) {
            if (!isset($this->selectedValues[$pa->attribute_id])) {
                $this->selectedValues[$pa->attribute_id] = [];
            }
            $this->selectedValues[$pa->attribute_id][] = $pa->attribute_value_id;
        }
    }

    /**
     * Load existing variants.
     */
    private function loadVariants(): void
    {
        $this->product->load(['variants.attributeValues.attribute', 'variants.images']);

        $this->variantData = [];

        foreach ($this->product->variants as $variant) {
            $this->variantData[$variant->id] = [
                'sku'            => $variant->sku,
                'price'          => $variant->price !== null ? number_format($variant->price / 100, 2, '.', '') : '',
                'stock_quantity' => $variant->stock_quantity,
                'weight'         => $variant->weight !== null ? (string) $variant->weight : '',
                'length'         => $variant->length !== null ? (string) $variant->length : '',
                'width'          => $variant->width !== null ? (string) $variant->width : '',
                'height'         => $variant->height !== null ? (string) $variant->height : '',
                'is_active'      => $variant->is_active,
            ];
        }
    }

    /**
     * Toggle an attribute selection.
     */
    public function toggleAttribute(int $attributeId): void
    {
        if (in_array($attributeId, $this->selectedAttributes)) {
            $this->selectedAttributes = array_filter(
                $this->selectedAttributes,
                fn ($id) => $id !== $attributeId,
            );
            unset($this->selectedValues[$attributeId]);
            $this->usedForVariations = array_filter(
                $this->usedForVariations,
                fn ($id) => $id !== $attributeId,
            );
        } else {
            $this->selectedAttributes[]         = $attributeId;
            $this->selectedValues[$attributeId] = [];
        }
    }

    /**
     * Toggle an attribute value selection.
     */
    public function toggleValue(int $attributeId, int $valueId): void
    {
        if (!isset($this->selectedValues[$attributeId])) {
            $this->selectedValues[$attributeId] = [];
        }

        if (in_array($valueId, $this->selectedValues[$attributeId])) {
            $this->selectedValues[$attributeId] = array_filter(
                $this->selectedValues[$attributeId],
                fn ($id) => $id !== $valueId,
            );
        } else {
            $this->selectedValues[$attributeId][] = $valueId;
        }
    }

    /**
     * Toggle an attribute for variations.
     */
    public function toggleVariation(int $attributeId): void
    {
        if (in_array($attributeId, $this->usedForVariations)) {
            $this->usedForVariations = array_filter(
                $this->usedForVariations,
                fn ($id) => $id !== $attributeId,
            );
        } else {
            $this->usedForVariations[] = $attributeId;
        }
    }

    /**
     * Save the product attributes.
     */
    public function saveAttributes(): void
    {
        DB::transaction(function () {
            // Delete existing product attributes
            DB::table('product_attributes')
                ->where('product_id', $this->product->id)
                ->delete();

            // Insert new product attributes
            $inserts = [];

            foreach ($this->selectedAttributes as $attributeId) {
                $values            = $this->selectedValues[$attributeId] ?? [];
                $usedForVariations = in_array($attributeId, $this->usedForVariations);

                foreach ($values as $valueId) {
                    $inserts[] = [
                        'product_id'          => $this->product->id,
                        'attribute_id'        => $attributeId,
                        'attribute_value_id'  => $valueId,
                        'used_for_variations' => $usedForVariations,
                    ];
                }
            }

            if (!empty($inserts)) {
                DB::table('product_attributes')->insert($inserts);
            }
        });

        $this->dispatch('notify', message: 'Atributos salvos com sucesso!', type: 'success');
    }

    /**
     * Generate variants based on selected attribute values for variations.
     */
    public function generateVariants(): void
    {
        // Get only attributes marked for variations with their values
        $attributesForVariation = [];

        foreach ($this->usedForVariations as $attributeId) {
            $values = $this->selectedValues[$attributeId] ?? [];

            if (!empty($values)) {
                $attributesForVariation[$attributeId] = $values;
            }
        }

        if (empty($attributesForVariation)) {
            $this->dispatch('notify', message: 'Selecione pelo menos um atributo com valores para gerar variantes.', type: 'error');

            return;
        }

        // Generate all combinations
        $combinations = $this->generateCombinations(array_values($attributesForVariation));

        if (count($combinations) > 100) {
            $this->dispatch('notify', message: 'Limite de 100 variantes excedido. Reduza a quantidade de valores selecionados.', type: 'error');

            return;
        }

        DB::transaction(function () use ($combinations, $attributesForVariation) {
            // First save the attributes
            $this->saveAttributes();

            $attributeKeys = array_keys($attributesForVariation);
            $existingSkus  = $this->product->variants->pluck('sku')->toArray();

            foreach ($combinations as $combination) {
                // Build SKU suffix from attribute values
                $skuParts = [];
                $valueIds = [];

                foreach ($combination as $index => $valueId) {
                    $value = AttributeValue::find($valueId);

                    if ($value) {
                        $skuParts[] = Str::upper(Str::substr($value->value, 0, 3));
                        $valueIds[] = $valueId;
                    }
                }

                $baseSku    = $this->product->sku ?? Str::upper(Str::slug($this->product->name, '-'));
                $variantSku = $baseSku . '-' . implode('-', $skuParts);

                // Ensure unique SKU
                $counter  = 1;
                $finalSku = $variantSku;
                while (in_array($finalSku, $existingSkus) || ProductVariant::where('sku', $finalSku)->exists()) {
                    $finalSku = $variantSku . '-' . $counter;
                    $counter++;
                }
                $existingSkus[] = $finalSku;

                // Check if variant with same attribute values already exists
                $existingVariant = $this->findExistingVariant($valueIds);

                if ($existingVariant) {
                    continue;
                }

                // Create the variant
                $variant = $this->product->variants()->create([
                    'sku'            => $finalSku,
                    'price'          => null, // Inherits from parent
                    'stock_quantity' => 0,
                    'is_active'      => true,
                ]);

                // Attach attribute values
                $variant->attributeValues()->attach($valueIds);
            }
        });

        $this->loadVariants();
        $this->dispatch('notify', message: 'Variantes geradas com sucesso!', type: 'success');
    }

    /**
     * Generate all combinations of attribute values.
     *
     * @param  array<array<int>>  $arrays
     * @return array<array<int>>
     */
    private function generateCombinations(array $arrays): array
    {
        if (empty($arrays)) {
            return [[]];
        }

        $result = [[]];

        foreach ($arrays as $array) {
            $newResult = [];

            foreach ($result as $combo) {
                foreach ($array as $value) {
                    $newResult[] = array_merge($combo, [$value]);
                }
            }
            $result = $newResult;
        }

        return $result;
    }

    /**
     * Find an existing variant with the same attribute values.
     *
     * @param  array<int>  $valueIds
     */
    private function findExistingVariant(array $valueIds): ?ProductVariant
    {
        sort($valueIds);

        foreach ($this->product->variants as $variant) {
            $variantValueIds = $variant->attributeValues->pluck('id')->sort()->values()->toArray();

            if ($variantValueIds === $valueIds) {
                return $variant;
            }
        }

        return null;
    }

    /**
     * Update a variant field.
     */
    public function updateVariant(int $variantId, string $field): void
    {
        $variant = ProductVariant::find($variantId);

        if (!$variant || $variant->product_id !== $this->product->id) {
            return;
        }

        $data = $this->variantData[$variantId] ?? [];

        $updateData = [];

        switch ($field) {
            case 'sku':
                $updateData['sku'] = $data['sku'] ?? $variant->sku;

                break;
            case 'price':
                $price               = $data['price'] ?? '';
                $updateData['price'] = $price !== '' ? (int) round((float) $price * 100) : null;

                break;
            case 'stock_quantity':
                $updateData['stock_quantity'] = (int) ($data['stock_quantity'] ?? 0);

                break;
            case 'weight':
                $weight               = $data['weight'] ?? '';
                $updateData['weight'] = $weight !== '' ? (float) $weight : null;

                break;
            case 'length':
                $length               = $data['length'] ?? '';
                $updateData['length'] = $length !== '' ? (float) $length : null;

                break;
            case 'width':
                $width               = $data['width'] ?? '';
                $updateData['width'] = $width !== '' ? (float) $width : null;

                break;
            case 'height':
                $height               = $data['height'] ?? '';
                $updateData['height'] = $height !== '' ? (float) $height : null;

                break;
            case 'is_active':
                $updateData['is_active'] = $data['is_active'] ?? true;

                break;
        }

        if (!empty($updateData)) {
            $variant->update($updateData);
        }
    }

    /**
     * Toggle variant active status.
     */
    public function toggleVariantActive(int $variantId): void
    {
        $variant = ProductVariant::find($variantId);

        if (!$variant || $variant->product_id !== $this->product->id) {
            return;
        }

        $variant->update(['is_active' => !$variant->is_active]);
        $this->variantData[$variantId]['is_active'] = !$this->variantData[$variantId]['is_active'];
    }

    /**
     * Delete a variant.
     */
    public function deleteVariant(int $variantId): void
    {
        $variant = ProductVariant::find($variantId);

        if (!$variant || $variant->product_id !== $this->product->id) {
            return;
        }

        $variant->attributeValues()->detach();
        $variant->images()->delete();
        $variant->delete();

        unset($this->variantData[$variantId]);
        $this->product->load('variants');

        $this->dispatch('notify', message: 'Variante excluída com sucesso!', type: 'success');
    }

    /**
     * Toggle variant selection for bulk actions.
     */
    public function toggleVariantSelection(int $variantId): void
    {
        if (in_array($variantId, $this->selectedVariants)) {
            $this->selectedVariants = array_filter(
                $this->selectedVariants,
                fn ($id) => $id !== $variantId,
            );
        } else {
            $this->selectedVariants[] = $variantId;
        }
    }

    /**
     * Select all variants.
     */
    public function selectAllVariants(): void
    {
        $this->selectedVariants = array_keys($this->variantData);
    }

    /**
     * Deselect all variants.
     */
    public function deselectAllVariants(): void
    {
        $this->selectedVariants = [];
    }

    /**
     * Apply bulk edit to selected variants.
     */
    public function applyBulkEdit(): void
    {
        if (empty($this->selectedVariants)) {
            $this->dispatch('notify', message: 'Selecione pelo menos uma variante.', type: 'error');

            return;
        }

        DB::transaction(function () {
            foreach ($this->selectedVariants as $variantId) {
                $variant = ProductVariant::find($variantId);

                if (!$variant || $variant->product_id !== $this->product->id) {
                    continue;
                }

                $updateData = [];

                if ($this->bulkValues['price'] !== null && $this->bulkValues['price'] !== '') {
                    $updateData['price']                    = (int) round((float) $this->bulkValues['price'] * 100);
                    $this->variantData[$variantId]['price'] = $this->bulkValues['price'];
                }

                if ($this->bulkValues['stock_quantity'] !== null) {
                    $updateData['stock_quantity']                    = (int) $this->bulkValues['stock_quantity'];
                    $this->variantData[$variantId]['stock_quantity'] = (int) $this->bulkValues['stock_quantity'];
                }

                if (!empty($updateData)) {
                    $variant->update($updateData);
                }
            }
        });

        $this->showBulkEdit     = false;
        $this->bulkValues       = ['price' => null, 'stock_quantity' => null];
        $this->selectedVariants = [];

        $this->dispatch('notify', message: 'Variantes atualizadas com sucesso!', type: 'success');
    }

    /**
     * Open image assignment modal for a variant.
     */
    public function openImageAssignment(int $variantId): void
    {
        $this->assignImageToVariant = $variantId;
    }

    /**
     * Close image assignment modal.
     */
    public function closeImageAssignment(): void
    {
        $this->assignImageToVariant = null;
    }

    /**
     * Assign an image to a variant.
     */
    public function assignImage(int $imageId): void
    {
        if (!$this->assignImageToVariant) {
            return;
        }

        $image = ProductImage::find($imageId);

        if (!$image || $image->product_id !== $this->product->id) {
            return;
        }

        // Update the image to associate with this variant
        $image->update(['variant_id' => $this->assignImageToVariant]);

        $this->product->load(['variants.images', 'images']);
        $this->closeImageAssignment();

        $this->dispatch('notify', message: 'Imagem associada com sucesso!', type: 'success');
    }

    /**
     * Remove image assignment from variant.
     */
    public function removeImageFromVariant(int $imageId): void
    {
        $image = ProductImage::find($imageId);

        if (!$image || $image->product_id !== $this->product->id) {
            return;
        }

        $image->update(['variant_id' => null]);

        $this->product->load(['variants.images', 'images']);

        $this->dispatch('notify', message: 'Imagem desassociada da variante!', type: 'success');
    }

    /**
     * Delete all variants.
     */
    public function deleteAllVariants(): void
    {
        DB::transaction(function () {
            foreach ($this->product->variants as $variant) {
                $variant->attributeValues()->detach();
                $variant->images()->update(['variant_id' => null]);
                $variant->delete();
            }
        });

        $this->variantData = [];
        $this->product->load('variants');

        $this->dispatch('notify', message: 'Todas as variantes foram excluídas!', type: 'success');
    }

    public function render()
    {
        return view('livewire.admin.product-variants');
    }
}
