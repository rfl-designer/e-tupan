<div>
    <flux:heading size="lg" class="mb-6">
        Opcoes de Entrega
    </flux:heading>

    @if ($error)
        <flux:callout variant="danger" class="mb-6">
            {{ $error }}
        </flux:callout>
    @endif

    @if ($isLoading)
        <div class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
            <span class="ml-3 text-zinc-600 dark:text-zinc-400">Calculando opcoes de entrega...</span>
        </div>
    @elseif (empty($shippingOptions))
        <div class="text-center py-12">
            <flux:icon name="truck" class="mx-auto h-12 w-12 text-zinc-400" />
            <flux:heading size="md" class="mt-4">
                Nenhuma opcao disponivel
            </flux:heading>
            <flux:text class="mt-2">
                Nao foi possivel calcular o frete para o CEP {{ $zipcode }}.
            </flux:text>
            <div class="mt-4">
                <flux:button variant="ghost" wire:click="calculateShipping">
                    <flux:icon name="arrow-path" class="size-4 mr-2" />
                    Tentar novamente
                </flux:button>
            </div>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($shippingOptions as $option)
                <div
                    wire:key="shipping-{{ $option['code'] }}"
                    wire:click="selectShipping('{{ $option['code'] }}')"
                    class="p-4 border rounded-lg cursor-pointer transition-colors
                        {{ $selectedMethod === $option['code']
                            ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                            : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600' }}"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="flex-shrink-0">
                                @if ($selectedMethod === $option['code'])
                                    <flux:icon name="check-circle" class="size-5 text-primary-600" />
                                @else
                                    <div class="size-5 rounded-full border-2 border-zinc-300 dark:border-zinc-600"></div>
                                @endif
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $option['name'] }}
                                    </span>
                                    <span class="text-sm text-zinc-500 dark:text-zinc-400">
                                        ({{ $option['carrier'] }})
                                    </span>
                                </div>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                    Entrega em ate {{ $option['delivery_days'] }} {{ $option['delivery_days'] === 1 ? 'dia util' : 'dias uteis' }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            @if ($option['price'] === 0)
                                <span class="text-lg font-bold text-green-600 dark:text-green-400">
                                    Gratis
                                </span>
                            @else
                                <span class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                                    R$ {{ number_format($option['price'] / 100, 2, ',', '.') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <div class="flex items-start gap-3">
                <flux:icon name="information-circle" class="size-5 text-zinc-400 flex-shrink-0 mt-0.5" />
                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                    <p>Os prazos de entrega comecam a contar apos a confirmacao do pagamento.</p>
                    <p class="mt-1">O prazo pode variar em periodos de alta demanda ou em caso de eventos externos.</p>
                </div>
            </div>
        </div>

        <div class="pt-6 border-t border-zinc-200 dark:border-zinc-700 mt-6">
            <div class="flex justify-end">
                <flux:button
                    wire:click="continueToPayment"
                    variant="primary"
                    wire:loading.attr="disabled"
                    :disabled="$selectedMethod === null"
                >
                    <span wire:loading.remove wire:target="continueToPayment">Continuar para pagamento</span>
                    <span wire:loading wire:target="continueToPayment">Processando...</span>
                    <flux:icon name="arrow-right" class="size-4 ml-2" wire:loading.remove wire:target="continueToPayment" />
                </flux:button>
            </div>
        </div>
    @endif
</div>
