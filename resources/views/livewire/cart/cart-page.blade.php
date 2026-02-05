<div class="max-w-6xl mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <flux:heading size="xl">
                Carrinho de Compras
            </flux:heading>
            @if (!$this->isEmpty)
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $this->itemCount }} {{ $this->itemCount === 1 ? 'item' : 'itens' }}
                </p>
            @endif
        </div>
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
            <flux:icon name="arrow-left" class="size-4" />
            Continuar comprando
        </a>
    </div>

    {{-- Validation Alerts --}}
    @if (!empty($validationAlerts))
        <div class="mb-6 space-y-2">
            @foreach ($validationAlerts as $alert)
                <flux:callout variant="warning" dismissible wire:key="alert-{{ $loop->index }}">
                    {{ $alert }}
                </flux:callout>
            @endforeach
        </div>
    @endif

    @if ($this->isEmpty)
        {{-- Empty State --}}
        <div class="text-center py-16 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
            <flux:icon name="shopping-cart" class="mx-auto h-16 w-16 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg" class="mt-6">
                Seu carrinho esta vazio
            </flux:heading>
            <flux:text class="mt-2 max-w-sm mx-auto">
                Parece que voce ainda nao adicionou nenhum produto ao carrinho.
            </flux:text>
            <div class="mt-8">
                <flux:button href="{{ route('home') }}" variant="primary">
                    Continuar comprando
                </flux:button>
            </div>
        </div>
    @else
        <div class="lg:grid lg:grid-cols-12 lg:gap-8">
            {{-- Cart Items --}}
            <div class="lg:col-span-8">
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    {{-- Table Header (Desktop) --}}
                    <div class="hidden sm:grid sm:grid-cols-12 gap-4 px-6 py-3 bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700 text-sm font-medium text-zinc-500 dark:text-zinc-400">
                        <div class="col-span-5">Produto</div>
                        <div class="col-span-2 text-center">Preco</div>
                        <div class="col-span-3 text-center">Quantidade</div>
                        <div class="col-span-2 text-right">Subtotal</div>
                    </div>

                    {{-- Cart Items --}}
                    <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($this->items as $item)
                            <livewire:cart.cart-item-row :item-id="$item->id" :key="'cart-item-'.$item->id" />
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Order Summary --}}
            <div class="lg:col-span-4 mt-8 lg:mt-0">
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 sticky top-4">
                    <flux:heading size="lg" class="mb-6">
                        Resumo do Pedido
                    </flux:heading>

                    <div class="space-y-4">
                        {{-- Subtotal --}}
                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-600 dark:text-zinc-400">Subtotal</span>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                R$ {{ number_format($this->subtotal / 100, 2, ',', '.') }}
                            </span>
                        </div>

                        {{-- Discount --}}
                        @if ($this->discount > 0)
                            <div class="flex justify-between text-sm text-green-600 dark:text-green-400">
                                <span>Desconto</span>
                                <span>- R$ {{ number_format($this->discount / 100, 2, ',', '.') }}</span>
                            </div>
                        @endif

                        {{-- Shipping --}}
                        @if ($this->shippingCost !== null)
                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-600 dark:text-zinc-400">Frete</span>
                                @if ($this->shippingCost === 0)
                                    <span class="font-medium text-green-600 dark:text-green-400">Gratis</span>
                                @else
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                        R$ {{ number_format($this->shippingCost / 100, 2, ',', '.') }}
                                    </span>
                                @endif
                            </div>
                        @else
                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-600 dark:text-zinc-400">Frete</span>
                                <span class="text-zinc-500 dark:text-zinc-400">Calcular no checkout</span>
                            </div>
                        @endif
                    </div>

                    {{-- Free Shipping Indicator --}}
                    @if ($this->freeShippingMessage)
                        <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-700">
                            <div class="flex items-center gap-2 text-sm text-amber-700 dark:text-amber-400">
                                <flux:icon name="truck" class="size-5 flex-shrink-0" />
                                <span>{{ $this->freeShippingMessage }}</span>
                            </div>
                        </div>
                    @elseif ($this->isEligibleForFreeShipping)
                        <div class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-700">
                            <div class="flex items-center gap-2 text-sm text-green-700 dark:text-green-400">
                                <flux:icon name="check-circle" class="size-5 flex-shrink-0" />
                                <span>Parabens! Voce ganhou frete gratis!</span>
                            </div>
                        </div>
                    @endif

                    {{-- Total --}}
                    <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                        <div class="flex justify-between items-center">
                            <span class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Total</span>
                            <span class="text-xl font-bold text-zinc-900 dark:text-zinc-100">
                                R$ {{ number_format($this->total / 100, 2, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="mt-6 space-y-3">
                        <flux:button variant="primary" class="w-full py-3">
                            Finalizar Compra
                        </flux:button>
                        <flux:button href="{{ route('home') }}" variant="ghost" class="w-full">
                            Continuar comprando
                        </flux:button>
                    </div>

                    {{-- Security badges --}}
                    <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center justify-center gap-4 text-xs text-zinc-500 dark:text-zinc-400">
                            <div class="flex items-center gap-1">
                                <flux:icon name="lock-closed" class="size-4" />
                                <span>Compra segura</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <flux:icon name="shield-check" class="size-4" />
                                <span>Dados protegidos</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mobile Fixed Bottom Summary --}}
        <div class="fixed bottom-0 left-0 right-0 p-4 bg-white dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700 lg:hidden z-40">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm text-zinc-600 dark:text-zinc-400">Total ({{ $this->itemCount }} {{ $this->itemCount === 1 ? 'item' : 'itens' }})</span>
                <span class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                    R$ {{ number_format($this->total / 100, 2, ',', '.') }}
                </span>
            </div>
            <flux:button variant="primary" class="w-full py-3">
                Finalizar Compra
            </flux:button>
        </div>

        {{-- Spacer for mobile fixed bottom --}}
        <div class="h-28 lg:hidden"></div>
    @endif
</div>
