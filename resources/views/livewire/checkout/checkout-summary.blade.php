<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6">
    <flux:heading size="lg" class="mb-4">
        Resumo do Pedido
    </flux:heading>

    {{-- Cart Items --}}
    <div class="space-y-4 mb-6">
        @forelse($this->cartItems as $item)
            <div wire:key="summary-item-{{ $item->id }}" class="flex gap-4">
                {{-- Product Image --}}
                @php
                    $primaryImage = $item->product->primaryImage();
                @endphp
                <div class="w-16 h-16 flex-shrink-0 rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-700">
                    @if($primaryImage)
                        <img
                            src="{{ $primaryImage->thumb_url }}"
                            alt="{{ $item->product->name }}"
                            class="w-full h-full object-cover"
                        />
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <flux:icon name="photo" class="w-6 h-6 text-zinc-400" />
                        </div>
                    @endif
                </div>

                {{-- Product Info --}}
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate">
                        {{ $item->product->name }}
                    </p>

                    @if($item->variant)
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $item->variant->name }}
                        </p>
                    @endif

                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Qtd: {{ $item->quantity }}
                    </p>
                </div>

                {{-- Price --}}
                <div class="text-right">
                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                        R$ {{ number_format(($item->unit_price * $item->quantity) / 100, 2, ',', '.') }}
                    </p>
                </div>
            </div>
        @empty
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Nenhum item no carrinho.
            </p>
        @endforelse
    </div>

    {{-- Divider --}}
    <div class="border-t border-zinc-200 dark:border-zinc-700 my-4"></div>

    {{-- Totals --}}
    <div class="space-y-2">
        {{-- Subtotal --}}
        <div class="flex justify-between text-sm">
            <span class="text-zinc-600 dark:text-zinc-400">Subtotal ({{ $this->itemCount }} itens)</span>
            <span class="text-zinc-900 dark:text-zinc-100">
                R$ {{ number_format($this->subtotal / 100, 2, ',', '.') }}
            </span>
        </div>

        {{-- Discount --}}
        @if($this->discount > 0)
            <div class="flex justify-between text-sm">
                <span class="text-green-600 dark:text-green-400">Desconto</span>
                <span class="text-green-600 dark:text-green-400">
                    - R$ {{ number_format($this->discount / 100, 2, ',', '.') }}
                </span>
            </div>
        @endif

        {{-- Shipping --}}
        <div class="flex justify-between text-sm">
            <span class="text-zinc-600 dark:text-zinc-400">
                Frete
                @if($shippingMethod)
                    <span class="text-xs">({{ $shippingMethod }})</span>
                @endif
            </span>
            @if($shippingCost > 0)
                <span class="text-zinc-900 dark:text-zinc-100">
                    R$ {{ number_format($shippingCost / 100, 2, ',', '.') }}
                </span>
            @elseif($shippingCost === 0 && $shippingMethod)
                <span class="text-green-600 dark:text-green-400">Gratis</span>
            @else
                <span class="text-zinc-500 dark:text-zinc-400">A calcular</span>
            @endif
        </div>

        {{-- Delivery Estimate --}}
        @if($deliveryDays)
            <div class="flex justify-between text-sm">
                <span class="text-zinc-600 dark:text-zinc-400">Prazo estimado</span>
                <span class="text-zinc-900 dark:text-zinc-100">
                    {{ $deliveryDays }} {{ $deliveryDays === 1 ? 'dia util' : 'dias uteis' }}
                </span>
            </div>
        @endif
    </div>

    {{-- Divider --}}
    <div class="border-t border-zinc-200 dark:border-zinc-700 my-4"></div>

    {{-- Total --}}
    <div class="flex justify-between items-center">
        <span class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Total</span>
        <span class="text-xl font-bold text-zinc-900 dark:text-zinc-100">
            R$ {{ number_format($this->total / 100, 2, ',', '.') }}
        </span>
    </div>

    {{-- Shipping Address --}}
    @if($this->formattedAddress)
        <div class="mt-6 pt-4 border-t border-zinc-200 dark:border-zinc-700">
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-2">
                Endereco de Entrega
            </p>
            @if(!empty($addressData['shipping_recipient_name']))
                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                    {{ $addressData['shipping_recipient_name'] }}
                </p>
            @endif
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ $this->formattedAddress }}
            </p>
        </div>
    @endif

    {{-- Edit Cart Link --}}
    <div class="mt-4">
        <a
            href="{{ route('cart.index') }}"
            class="text-sm text-blue-600 dark:text-blue-400 hover:underline"
        >
            Editar carrinho
        </a>
    </div>
</div>
