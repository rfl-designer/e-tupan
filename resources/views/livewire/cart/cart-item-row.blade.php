<div class="p-4 sm:px-6 sm:py-4">
    @if ($this->item)
        <div class="sm:grid sm:grid-cols-12 sm:gap-4 sm:items-center">
            {{-- Product Info --}}
            <div class="col-span-5 flex gap-4">
                {{-- Product Image --}}
                @php $primaryImage = $this->item->product->primaryImage(); @endphp
                <div class="flex-shrink-0 w-20 h-20 rounded-lg bg-zinc-100 dark:bg-zinc-700 overflow-hidden">
                    @if ($primaryImage)
                        <img
                            src="{{ $primaryImage->thumb_url }}"
                            alt="{{ $this->item->product->name }}"
                            class="w-full h-full object-cover"
                        >
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <flux:icon name="photo" class="size-8 text-zinc-400" />
                        </div>
                    @endif
                </div>

                {{-- Product Details --}}
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                        {{ $this->item->product->name }}
                    </h3>
                    @if ($this->item->variant)
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $this->item->variant->getAttributeDescription() ?: $this->item->variant->sku }}
                        </p>
                    @endif
                    @if ($this->item->isOnSale())
                        <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            Promocao
                        </span>
                    @endif

                    {{-- Mobile Price --}}
                    <div class="mt-2 sm:hidden">
                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                            R$ {{ number_format($this->item->getEffectivePrice() / 100, 2, ',', '.') }}
                        </span>
                        @if ($this->item->isOnSale())
                            <span class="ml-2 text-sm text-zinc-500 line-through">
                                R$ {{ number_format($this->item->unit_price / 100, 2, ',', '.') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Unit Price (Desktop) --}}
            <div class="hidden sm:block col-span-2 text-center">
                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                    R$ {{ number_format($this->item->getEffectivePrice() / 100, 2, ',', '.') }}
                </span>
                @if ($this->item->isOnSale())
                    <span class="block text-xs text-zinc-500 line-through">
                        R$ {{ number_format($this->item->unit_price / 100, 2, ',', '.') }}
                    </span>
                @endif
            </div>

            {{-- Quantity Controls --}}
            <div class="col-span-3 mt-4 sm:mt-0">
                <div class="flex items-center justify-center gap-1">
                    <button
                        wire:click="decrement"
                        type="button"
                        class="flex items-center justify-center w-8 h-8 rounded-lg bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-600 dark:text-zinc-300 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="$quantity <= 1"
                        aria-label="Diminuir quantidade"
                    >
                        <flux:icon name="minus" class="size-4" />
                    </button>

                    <input
                        wire:model.blur="quantity"
                        wire:change="updateQuantity"
                        type="number"
                        min="1"
                        max="{{ $maxQuantity }}"
                        inputmode="numeric"
                        class="w-14 h-8 text-center text-sm font-medium text-zinc-900 dark:text-zinc-100 bg-transparent border border-zinc-200 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    >

                    <button
                        wire:click="increment"
                        type="button"
                        class="flex items-center justify-center w-8 h-8 rounded-lg bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-600 dark:text-zinc-300 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="$quantity >= $maxQuantity"
                        aria-label="Aumentar quantidade"
                    >
                        <flux:icon name="plus" class="size-4" />
                    </button>
                </div>

                {{-- Stock warning --}}
                @if ($maxQuantity > 0 && $maxQuantity <= 5)
                    <p class="mt-1 text-xs text-center text-amber-600 dark:text-amber-400">
                        Apenas {{ $maxQuantity }} disponiveis
                    </p>
                @endif

                {{-- Error message --}}
                @if ($errorMessage)
                    <p class="mt-1 text-xs text-center text-red-600 dark:text-red-400">
                        {{ $errorMessage }}
                    </p>
                @endif
            </div>

            {{-- Subtotal and Remove --}}
            <div class="col-span-2 mt-4 sm:mt-0 text-right">
                <div class="flex items-center justify-end gap-3">
                    <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                        R$ {{ number_format($this->subtotal / 100, 2, ',', '.') }}
                    </span>
                    <button
                        wire:click="remove"
                        wire:confirm="Tem certeza que deseja remover este item do carrinho?"
                        type="button"
                        class="flex items-center justify-center w-8 h-8 rounded-lg text-zinc-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition-colors"
                        aria-label="Remover item"
                    >
                        <flux:icon name="trash" class="size-4" />
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile Remove Button --}}
        <div class="mt-4 sm:hidden flex justify-end">
            <button
                wire:click="remove"
                wire:confirm="Tem certeza que deseja remover este item do carrinho?"
                type="button"
                class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-red-600 dark:text-zinc-400 dark:hover:text-red-400"
            >
                <flux:icon name="trash" class="size-4" />
                Remover
            </button>
        </div>
    @endif
</div>
