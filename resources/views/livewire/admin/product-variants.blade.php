<div class="space-y-6">
    {{-- Notification listener --}}
    <div
        x-data
        x-on:notify.window="
            const type = $event.detail.type || 'success';
            const message = $event.detail.message;
            // Simple notification - could integrate with a toast library
            alert(message);
        "
    ></div>

    {{-- Attributes Selection Section --}}
    <div class="border-b border-neutral-800 pb-6">
        <flux:heading size="sm" class="mb-4">{{ __('Atributos do Produto') }}</flux:heading>
        <flux:subheading class="mb-4">{{ __('Selecione os atributos e seus valores para este produto. Marque quais atributos serão usados para gerar variantes.') }}</flux:subheading>

        @if ($availableAttributes->isEmpty())
            <flux:callout variant="warning">
                {{ __('Nenhum atributo cadastrado. Crie atributos em Catálogo > Atributos para usar variantes.') }}
            </flux:callout>
        @else
            <div class="space-y-4">
                @foreach ($availableAttributes as $attribute)
                    <div class="rounded-lg border border-neutral-800 bg-neutral-800/50 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    wire:click="toggleAttribute({{ $attribute->id }})"
                                    @checked(in_array($attribute->id, $selectedAttributes))
                                    class="rounded border-neutral-600 bg-neutral-700 text-blue-500 focus:ring-blue-500"
                                >
                                <span class="font-medium text-white">{{ $attribute->name }}</span>
                                <flux:badge size="sm" color="zinc">{{ $attribute->type->label() }}</flux:badge>
                            </label>

                            @if (in_array($attribute->id, $selectedAttributes))
                                <label class="flex items-center gap-2 text-sm cursor-pointer">
                                    <input
                                        type="checkbox"
                                        wire:click="toggleVariation({{ $attribute->id }})"
                                        @checked(in_array($attribute->id, $usedForVariations))
                                        class="rounded border-neutral-600 bg-neutral-700 text-green-500 focus:ring-green-500"
                                    >
                                    <span class="text-neutral-300">{{ __('Usar para variantes') }}</span>
                                </label>
                            @endif
                        </div>

                        @if (in_array($attribute->id, $selectedAttributes))
                            <div class="flex flex-wrap gap-2 pl-8">
                                @foreach ($attribute->values as $value)
                                    <label
                                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm cursor-pointer transition-colors
                                            {{ in_array($value->id, $selectedValues[$attribute->id] ?? []) ? 'bg-blue-600 text-white' : 'bg-neutral-700 text-neutral-300 hover:bg-neutral-600' }}"
                                    >
                                        <input
                                            type="checkbox"
                                            wire:click="toggleValue({{ $attribute->id }}, {{ $value->id }})"
                                            @checked(in_array($value->id, $selectedValues[$attribute->id] ?? []))
                                            class="hidden"
                                        >
                                        @if ($value->hasColor())
                                            <span
                                                class="inline-block size-4 rounded-full border border-neutral-500"
                                                style="background-color: {{ $value->color_hex }}"
                                            ></span>
                                        @endif
                                        {{ $value->value }}
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="flex items-center gap-3 mt-4">
                <flux:button wire:click="saveAttributes" variant="primary" size="sm">
                    {{ __('Salvar Atributos') }}
                </flux:button>

                @if (!empty($usedForVariations))
                    <flux:button wire:click="generateVariants" variant="filled" size="sm">
                        <flux:icon name="sparkles" class="size-4 mr-1" />
                        {{ __('Gerar Variantes') }}
                    </flux:button>
                @endif
            </div>
        @endif
    </div>

    {{-- Variants Table --}}
    @if ($product->variants->isNotEmpty())
        <div>
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="sm">{{ __('Variantes') }} ({{ $product->variants->count() }})</flux:heading>
                <div class="flex items-center gap-2">
                    @if (!empty($selectedVariants))
                        <flux:button wire:click="$set('showBulkEdit', true)" variant="ghost" size="sm">
                            <flux:icon name="pencil-square" class="size-4 mr-1" />
                            {{ __('Editar em Lote') }} ({{ count($selectedVariants) }})
                        </flux:button>
                    @endif
                    <flux:button
                        wire:click="deleteAllVariants"
                        wire:confirm="{{ __('Tem certeza que deseja excluir todas as variantes? Esta ação não pode ser desfeita.') }}"
                        variant="ghost"
                        size="sm"
                        class="text-red-400 hover:text-red-300"
                    >
                        <flux:icon name="trash" class="size-4 mr-1" />
                        {{ __('Excluir Todas') }}
                    </flux:button>
                </div>
            </div>

            {{-- Bulk Actions Bar --}}
            <div class="flex items-center gap-4 mb-4 p-3 bg-neutral-800/50 rounded-lg">
                <label class="flex items-center gap-2 text-sm cursor-pointer">
                    <input
                        type="checkbox"
                        wire:click="{{ count($selectedVariants) === count($variantData) ? 'deselectAllVariants' : 'selectAllVariants' }}"
                        @checked(count($selectedVariants) === count($variantData) && count($variantData) > 0)
                        class="rounded border-neutral-600 bg-neutral-700 text-blue-500 focus:ring-blue-500"
                    >
                    <span class="text-neutral-300">{{ __('Selecionar todas') }}</span>
                </label>
            </div>

            {{-- Variants Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-800">
                    <thead>
                        <tr class="text-left text-xs text-neutral-400 uppercase tracking-wider">
                            <th class="p-3 w-10"></th>
                            <th class="p-3">{{ __('Variante') }}</th>
                            <th class="p-3">{{ __('SKU') }}</th>
                            <th class="p-3">{{ __('Preço (R$)') }}</th>
                            <th class="p-3">{{ __('Estoque') }}</th>
                            <th class="p-3">{{ __('Imagem') }}</th>
                            <th class="p-3">{{ __('Status') }}</th>
                            <th class="p-3 w-20">{{ __('Ações') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-800">
                        @foreach ($product->variants as $variant)
                            <tr wire:key="variant-{{ $variant->id }}" class="hover:bg-neutral-800/50">
                                <td class="p-3">
                                    <input
                                        type="checkbox"
                                        wire:click="toggleVariantSelection({{ $variant->id }})"
                                        @checked(in_array($variant->id, $selectedVariants))
                                        class="rounded border-neutral-600 bg-neutral-700 text-blue-500 focus:ring-blue-500"
                                    >
                                </td>
                                <td class="p-3">
                                    <div class="flex items-center gap-2">
                                        @foreach ($variant->attributeValues as $attrValue)
                                            @if ($attrValue->hasColor())
                                                <span
                                                    class="inline-block size-5 rounded-full border border-neutral-500"
                                                    style="background-color: {{ $attrValue->color_hex }}"
                                                    title="{{ $attrValue->attribute->name }}: {{ $attrValue->value }}"
                                                ></span>
                                            @endif
                                            <span class="text-sm text-neutral-300">{{ $attrValue->value }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="p-3">
                                    <input
                                        type="text"
                                        wire:model.blur="variantData.{{ $variant->id }}.sku"
                                        wire:change="updateVariant({{ $variant->id }}, 'sku')"
                                        class="w-32 px-2 py-1 text-sm bg-neutral-800 border border-neutral-700 rounded focus:border-blue-500 focus:ring-0 text-white"
                                    >
                                </td>
                                <td class="p-3">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        wire:model.blur="variantData.{{ $variant->id }}.price"
                                        wire:change="updateVariant({{ $variant->id }}, 'price')"
                                        placeholder="{{ number_format($product->price / 100, 2, '.', '') }}"
                                        class="w-24 px-2 py-1 text-sm bg-neutral-800 border border-neutral-700 rounded focus:border-blue-500 focus:ring-0 text-white placeholder-neutral-500"
                                    >
                                </td>
                                <td class="p-3">
                                    <input
                                        type="number"
                                        min="0"
                                        wire:model.blur="variantData.{{ $variant->id }}.stock_quantity"
                                        wire:change="updateVariant({{ $variant->id }}, 'stock_quantity')"
                                        class="w-20 px-2 py-1 text-sm bg-neutral-800 border border-neutral-700 rounded focus:border-blue-500 focus:ring-0 text-white"
                                    >
                                </td>
                                <td class="p-3">
                                    @if ($variant->images->isNotEmpty())
                                        <div class="flex items-center gap-2">
                                            <img
                                                src="{{ Storage::url($variant->images->first()->path . '_thumb.webp') }}"
                                                alt="{{ $variant->images->first()->alt_text }}"
                                                class="size-10 object-cover rounded"
                                            >
                                            <button
                                                type="button"
                                                wire:click="removeImageFromVariant({{ $variant->images->first()->id }})"
                                                class="text-red-400 hover:text-red-300"
                                                title="{{ __('Remover imagem') }}"
                                            >
                                                <flux:icon name="x-mark" class="size-4" />
                                            </button>
                                        </div>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="openImageAssignment({{ $variant->id }})"
                                            class="text-neutral-400 hover:text-white transition-colors"
                                            title="{{ __('Associar imagem') }}"
                                        >
                                            <flux:icon name="photo" class="size-8" />
                                        </button>
                                    @endif
                                </td>
                                <td class="p-3">
                                    <button
                                        type="button"
                                        wire:click="toggleVariantActive({{ $variant->id }})"
                                        class="transition-colors"
                                    >
                                        @if ($variantData[$variant->id]['is_active'] ?? $variant->is_active)
                                            <flux:badge color="green">{{ __('Ativo') }}</flux:badge>
                                        @else
                                            <flux:badge color="red">{{ __('Inativo') }}</flux:badge>
                                        @endif
                                    </button>
                                </td>
                                <td class="p-3">
                                    <button
                                        type="button"
                                        wire:click="deleteVariant({{ $variant->id }})"
                                        wire:confirm="{{ __('Tem certeza que deseja excluir esta variante?') }}"
                                        class="text-red-400 hover:text-red-300 transition-colors"
                                        title="{{ __('Excluir variante') }}"
                                    >
                                        <flux:icon name="trash" class="size-5" />
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        @if (!empty($usedForVariations))
            <flux:callout variant="info">
                {{ __('Nenhuma variante gerada. Clique em "Gerar Variantes" para criar as combinações automaticamente.') }}
            </flux:callout>
        @endif
    @endif

    {{-- Bulk Edit Modal --}}
    @if ($showBulkEdit)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-6 w-full max-w-md">
                <flux:heading size="lg" class="mb-4">{{ __('Editar em Lote') }}</flux:heading>
                <p class="text-sm text-neutral-400 mb-4">{{ __('Os valores preenchidos serão aplicados a todas as variantes selecionadas.') }}</p>

                <div class="space-y-4">
                    <flux:input
                        wire:model="bulkValues.price"
                        label="{{ __('Preço (R$)') }}"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="{{ __('Deixe vazio para não alterar') }}"
                    />

                    <flux:input
                        wire:model="bulkValues.stock_quantity"
                        label="{{ __('Quantidade em Estoque') }}"
                        type="number"
                        min="0"
                        placeholder="{{ __('Deixe vazio para não alterar') }}"
                    />
                </div>

                <div class="flex items-center justify-end gap-3 mt-6">
                    <flux:button wire:click="$set('showBulkEdit', false)" variant="ghost">
                        {{ __('Cancelar') }}
                    </flux:button>
                    <flux:button wire:click="applyBulkEdit" variant="primary">
                        {{ __('Aplicar') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    {{-- Image Assignment Modal --}}
    @if ($assignImageToVariant)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-6 w-full max-w-2xl">
                <flux:heading size="lg" class="mb-4">{{ __('Selecionar Imagem para Variante') }}</flux:heading>

                @if ($product->images->where('variant_id', null)->isEmpty())
                    <flux:callout variant="warning">
                        {{ __('Nenhuma imagem disponível. Faça upload de imagens na aba "Imagens" primeiro.') }}
                    </flux:callout>
                @else
                    <div class="grid grid-cols-4 gap-4">
                        @foreach ($product->images->where('variant_id', null) as $image)
                            <button
                                type="button"
                                wire:click="assignImage({{ $image->id }})"
                                class="aspect-square rounded-lg overflow-hidden border-2 border-transparent hover:border-blue-500 transition-colors"
                            >
                                <img
                                    src="{{ Storage::url($image->path . '_thumb.webp') }}"
                                    alt="{{ $image->alt_text }}"
                                    class="w-full h-full object-cover"
                                >
                            </button>
                        @endforeach
                    </div>
                @endif

                <div class="flex items-center justify-end mt-6">
                    <flux:button wire:click="closeImageAssignment" variant="ghost">
                        {{ __('Cancelar') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
