<div>
    <flux:heading size="lg" class="mb-6">
        Revisao do Pedido
    </flux:heading>

    @if ($error)
        <flux:callout variant="danger" class="mb-6">
            {{ $error }}
        </flux:callout>
    @endif

    <div class="space-y-6">
        {{-- Customer Info --}}
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <div class="flex items-center justify-between mb-3">
                <flux:heading size="sm">Dados do Cliente</flux:heading>
                <flux:button variant="ghost" size="sm" wire:click="$parent.goToStep('identification')">
                    Editar
                </flux:button>
            </div>
            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $this->customerName }}</p>
                <p>{{ $this->customerEmail }}</p>
                @if (!$isAuthenticated && !empty($guestData['cpf']))
                    <p>CPF: {{ $guestData['cpf'] }}</p>
                @endif
                @if (!$isAuthenticated && !empty($guestData['phone']))
                    <p>Telefone: {{ $guestData['phone'] }}</p>
                @endif
            </div>
        </div>

        {{-- Shipping Address --}}
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <div class="flex items-center justify-between mb-3">
                <flux:heading size="sm">Endereco de Entrega</flux:heading>
                <flux:button variant="ghost" size="sm" wire:click="$parent.goToStep('address')">
                    Editar
                </flux:button>
            </div>
            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                @if (!empty($addressData['shipping_recipient_name']))
                    <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $addressData['shipping_recipient_name'] }}</p>
                @endif
                <p>{{ $this->formattedAddress }}</p>
            </div>
        </div>

        {{-- Shipping Method --}}
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <div class="flex items-center justify-between mb-3">
                <flux:heading size="sm">Metodo de Entrega</flux:heading>
                <flux:button variant="ghost" size="sm" wire:click="$parent.goToStep('shipping')">
                    Editar
                </flux:button>
            </div>
            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $shippingData['shipping_carrier'] ?? 'Nao selecionado' }}
                        </p>
                        @if (!empty($shippingData['shipping_days']))
                            <p>Entrega em ate {{ $shippingData['shipping_days'] }} {{ $shippingData['shipping_days'] == 1 ? 'dia util' : 'dias uteis' }}</p>
                        @endif
                    </div>
                    <span class="font-medium text-zinc-900 dark:text-zinc-100">
                        @if (($shippingData['shipping_cost'] ?? 0) === 0)
                            Gratis
                        @else
                            R$ {{ number_format($shippingData['shipping_cost'] / 100, 2, ',', '.') }}
                        @endif
                    </span>
                </div>
            </div>
        </div>

        {{-- Payment Method --}}
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <div class="flex items-center justify-between mb-3">
                <flux:heading size="sm">Forma de Pagamento</flux:heading>
                <flux:button variant="ghost" size="sm" wire:click="$parent.goToStep('payment')">
                    Editar
                </flux:button>
            </div>
            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $this->paymentMethodLabel }}</p>
            </div>
        </div>

        {{-- Order Items --}}
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <flux:heading size="sm" class="mb-4">Itens do Pedido</flux:heading>
            <div class="space-y-3">
                @foreach ($this->cartItems as $item)
                    <div wire:key="review-item-{{ $item->id }}" class="flex items-center gap-3">
                        @php
                            $image = $item->product->images->first();
                        @endphp
                        <div class="w-12 h-12 bg-white dark:bg-zinc-800 rounded overflow-hidden flex-shrink-0">
                            @if ($image)
                                <img src="{{ Storage::url($image->path) }}" alt="{{ $item->product->name }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-zinc-400">
                                    <flux:icon name="photo" class="size-4" />
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate">
                                {{ $item->product->name }}
                            </p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Qtd: {{ $item->quantity }} x R$ {{ number_format($item->getEffectivePrice() / 100, 2, ',', '.') }}
                            </p>
                        </div>
                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                            R$ {{ number_format($item->getSubtotal() / 100, 2, ',', '.') }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Order Summary --}}
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <flux:heading size="sm" class="mb-4">Resumo</flux:heading>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-zinc-600 dark:text-zinc-400">Subtotal</span>
                    <span class="text-zinc-900 dark:text-zinc-100">R$ {{ number_format($this->subtotal / 100, 2, ',', '.') }}</span>
                </div>
                @if ($this->discount > 0)
                    <div class="flex justify-between text-green-600 dark:text-green-400">
                        <span>Desconto</span>
                        <span>- R$ {{ number_format($this->discount / 100, 2, ',', '.') }}</span>
                    </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-zinc-600 dark:text-zinc-400">Frete</span>
                    <span class="text-zinc-900 dark:text-zinc-100">
                        @if ($this->shippingCost === 0)
                            Gratis
                        @else
                            R$ {{ number_format($this->shippingCost / 100, 2, ',', '.') }}
                        @endif
                    </span>
                </div>
                <div class="flex justify-between pt-2 border-t border-zinc-200 dark:border-zinc-700">
                    <span class="font-semibold text-zinc-900 dark:text-zinc-100">Total</span>
                    <span class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                        R$ {{ number_format($this->total / 100, 2, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Terms & Conditions --}}
        <div class="p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg">
            <flux:checkbox
                wire:model="termsAccepted"
                label="Li e aceito os Termos de Uso e Politica de Privacidade"
            />
        </div>
    </div>

    <div class="pt-6 border-t border-zinc-200 dark:border-zinc-700 mt-6">
        <flux:button
            wire:click="placeOrder"
            variant="primary"
            class="w-full py-3"
            wire:loading.attr="disabled"
            :disabled="!$termsAccepted || $isProcessing"
        >
            @if ($isProcessing)
                <span class="flex items-center justify-center gap-2">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                    Processando pedido...
                </span>
            @else
                <span class="flex items-center justify-center gap-2">
                    <flux:icon name="lock-closed" class="size-4" />
                    Finalizar Pedido
                </span>
            @endif
        </flux:button>
        <p class="mt-3 text-center text-xs text-zinc-500 dark:text-zinc-400">
            Ao finalizar o pedido, voce sera redirecionado para a pagina de pagamento.
        </p>
    </div>
</div>
