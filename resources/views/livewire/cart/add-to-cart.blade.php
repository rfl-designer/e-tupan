<div>
    {{-- Variant Selection (for variable products) --}}
    @if ($requiresVariant && count($variants) > 1)
        <div class="mb-4">
            <flux:select
                wire:model.live="variantId"
                label="Selecione uma opcao"
                placeholder="Escolha uma variacao..."
            >
                @foreach ($variants as $variant)
                    <flux:select.option value="{{ $variant['id'] }}">
                        {{ $variant['name'] }} - R$ {{ number_format($variant['price'] / 100, 2, ',', '.') }}
                        @if ($variant['stock'] <= 5 && $variant['stock'] > 0)
                            ({{ $variant['stock'] }} restantes)
                        @elseif ($variant['stock'] === 0)
                            (Esgotado)
                        @endif
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
    @endif

    {{-- Quantity Selector --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
            Quantidade
        </label>
        <div class="flex items-center gap-2">
            <flux:button
                wire:click="decrement"
                variant="outline"
                size="sm"
                icon="minus"
                :disabled="$quantity <= 1"
                aria-label="Diminuir quantidade"
            />

            <flux:input
                wire:model.live.debounce.300ms="quantity"
                type="number"
                min="1"
                :max="$maxQuantity"
                class="w-20 text-center"
                inputmode="numeric"
            />

            <flux:button
                wire:click="increment"
                variant="outline"
                size="sm"
                icon="plus"
                :disabled="$quantity >= $maxQuantity"
                aria-label="Aumentar quantidade"
            />
        </div>

        @if ($maxQuantity > 0 && $maxQuantity < 10)
            <p class="mt-1 text-sm text-amber-600 dark:text-amber-400">
                Apenas {{ $maxQuantity }} disponiveis
            </p>
        @elseif ($maxQuantity === 0)
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">
                Produto esgotado
            </p>
        @endif
    </div>

    {{-- Error Message --}}
    @if ($errorMessage)
        <div class="mb-4">
            <flux:callout variant="danger" icon="exclamation-triangle">
                {{ $errorMessage }}
            </flux:callout>
        </div>
    @endif

    {{-- Add to Cart Button --}}
    <flux:button
        wire:click="add"
        variant="primary"
        class="w-full py-3"
        icon="shopping-cart"
        :disabled="$maxQuantity === 0 || ($requiresVariant && $variantId === null)"
    >
        Adicionar ao Carrinho
    </flux:button>

    {{-- Success Modal --}}
    <flux:modal wire:model="showModal" class="md:w-96">
        <div class="space-y-6">
            <div class="text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                    <flux:icon name="check" class="h-6 w-6 text-green-600 dark:text-green-400" />
                </div>
                <flux:heading size="lg" class="mt-4">
                    Produto adicionado!
                </flux:heading>
                <flux:text class="mt-2">
                    {{ $addedItemName }}
                </flux:text>
                @if ($addedItemPrice)
                    <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">
                        R$ {{ number_format($addedItemPrice / 100, 2, ',', '.') }}
                    </p>
                @endif
            </div>

            <div class="flex flex-col gap-2">
                <flux:button
                    wire:click="goToCart"
                    variant="primary"
                    class="w-full"
                    icon="shopping-cart"
                >
                    Ver Carrinho
                </flux:button>
                <flux:button
                    wire:click="closeModal"
                    variant="ghost"
                    class="w-full"
                >
                    Continuar Comprando
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
