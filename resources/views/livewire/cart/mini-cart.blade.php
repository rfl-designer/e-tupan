<div
    class="relative"
    x-data="{ open: $wire.entangle('isOpen') }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
>
    {{-- Cart Icon Button --}}
    <button
        @click="open = !open"
        type="button"
        class="relative flex size-9 items-center justify-center rounded-lg text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-700 sm:size-10 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
        aria-label="Carrinho"
    >
        <flux:icon name="shopping-cart" class="size-5" />

        {{-- Item Count Badge --}}
        @if ($this->itemCount > 0)
            <span class="absolute -right-1 -top-1 flex size-4 min-w-4 items-center justify-center rounded-full bg-primary-600 px-0.5 text-[10px] font-medium text-white sm:size-5 sm:min-w-5 sm:text-xs">
                {{ $this->itemCount > 99 ? '99+' : $this->itemCount }}
            </span>
        @else
            <span class="absolute -right-1 -top-1 flex size-4 items-center justify-center rounded-full bg-zinc-200 text-[10px] font-medium text-zinc-500 sm:size-5 sm:text-xs dark:bg-zinc-700 dark:text-zinc-400">
                0
            </span>
        @endif
    </button>

    {{-- Dropdown Menu --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-x-2 top-16 z-50 origin-top-right rounded-xl bg-white shadow-lg ring-1 ring-zinc-200 sm:absolute sm:inset-auto sm:right-0 sm:mt-2 sm:w-80 dark:bg-zinc-800 dark:ring-zinc-700"
        x-cloak
    >
        <div class="p-4">
            {{-- Header --}}
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="sm">
                    Carrinho
                </flux:heading>
                @if (!$this->isEmpty)
                    <span class="text-xs text-zinc-500 sm:text-sm dark:text-zinc-400">
                        {{ $this->itemCount }} {{ $this->itemCount === 1 ? 'item' : 'itens' }}
                    </span>
                @endif
            </div>

            {{-- Items List or Empty State --}}
            @if ($this->isEmpty)
                <div class="py-8 text-center">
                    <flux:icon name="shopping-cart" class="mx-auto size-10 text-zinc-300 sm:size-12 dark:text-zinc-600" />
                    <p class="mt-2 text-xs text-zinc-500 sm:text-sm dark:text-zinc-400">
                        Seu carrinho esta vazio
                    </p>
                    <a
                        href="{{ route('home') }}"
                        class="mt-4 inline-block text-xs font-medium text-primary-600 hover:text-primary-700 sm:text-sm dark:text-primary-400 dark:hover:text-primary-300"
                    >
                        Continuar comprando
                    </a>
                </div>
            @else
                {{-- Items --}}
                <div class="max-h-52 space-y-3 overflow-y-auto sm:max-h-64">
                    @foreach ($this->items as $item)
                        <div wire:key="mini-cart-item-{{ $item->id }}" class="flex gap-3">
                            {{-- Product Image --}}
                            @php $primaryImage = $item->product->primaryImage(); @endphp
                            <div class="size-12 shrink-0 overflow-hidden rounded-lg bg-zinc-100 sm:size-14 dark:bg-zinc-700">
                                @if ($primaryImage)
                                    <img
                                        src="{{ $primaryImage->thumb_url }}"
                                        alt="{{ $item->product->name }}"
                                        class="size-full object-cover"
                                    >
                                @else
                                    <div class="flex size-full items-center justify-center">
                                        <flux:icon name="photo" class="size-5 text-zinc-400 sm:size-6" />
                                    </div>
                                @endif
                            </div>

                            {{-- Product Info --}}
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-medium text-zinc-900 sm:text-sm dark:text-zinc-100">
                                    {{ $item->product->name }}
                                </p>
                                @if ($item->variant)
                                    <p class="truncate text-[10px] text-zinc-500 sm:text-xs dark:text-zinc-400">
                                        {{ $item->variant->name }}
                                    </p>
                                @endif
                                <div class="mt-1 flex items-center justify-between">
                                    <span class="text-[10px] text-zinc-500 sm:text-xs dark:text-zinc-400">
                                        Qtd: {{ $item->quantity }}
                                    </span>
                                    <span class="text-xs font-medium text-zinc-900 sm:text-sm dark:text-zinc-100">
                                        R$ {{ number_format($item->getSubtotal() / 100, 2, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- More items indicator --}}
                @if ($this->itemCount > $maxItems)
                    <p class="mt-2 text-center text-[10px] text-zinc-500 sm:text-xs dark:text-zinc-400">
                        + {{ $this->itemCount - $maxItems }} {{ ($this->itemCount - $maxItems) === 1 ? 'item' : 'itens' }} no carrinho
                    </p>
                @endif

                {{-- Subtotal --}}
                <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-zinc-600 sm:text-sm dark:text-zinc-400">
                            Subtotal
                        </span>
                        <span class="text-sm font-semibold text-zinc-900 sm:text-base dark:text-zinc-100">
                            R$ {{ number_format($this->subtotal / 100, 2, ',', '.') }}
                        </span>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="mt-4 space-y-2">
                    <flux:button
                        href="{{ route('cart.index') }}"
                        variant="outline"
                        class="w-full"
                    >
                        Ver Carrinho
                    </flux:button>
                    <flux:button
                        href="{{ route('cart.index') }}"
                        variant="primary"
                        class="w-full"
                    >
                        Finalizar Compra
                    </flux:button>
                </div>
            @endif
        </div>
    </div>
</div>
