<div class="max-w-3xl mx-auto">
    {{-- Success Header --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/30 mb-4">
            <flux:icon name="check-circle" class="size-10 text-green-600 dark:text-green-400" />
        </div>
        <flux:heading size="xl">Pedido realizado com sucesso!</flux:heading>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">
            Seu pedido <span class="font-semibold">{{ $order->order_number }}</span> foi recebido.
        </p>
    </div>

    {{-- Payment Status Alert --}}
    @if ($this->isPendingPayment)
        <flux:callout variant="warning" class="mb-6">
            <flux:callout.heading>Aguardando pagamento</flux:callout.heading>
            <flux:callout.text>
                @if ($this->isPixPayment)
                    Escaneie o QR Code ou copie o codigo Pix para realizar o pagamento.
                @elseif ($this->isBankSlipPayment)
                    Pague o boleto ate a data de vencimento para confirmar seu pedido.
                @endif
            </flux:callout.text>
        </flux:callout>
    @else
        <flux:callout variant="success" class="mb-6">
            <flux:callout.heading>Pagamento {{ $this->paymentStatusLabel }}</flux:callout.heading>
            <flux:callout.text>
                Voce recebera um email de confirmacao em breve.
            </flux:callout.text>
        </flux:callout>
    @endif

    {{-- Pix Payment Section --}}
    @if ($this->isPendingPayment && $this->isPixPayment && $payment)
        <div class="mb-6 p-6 bg-teal-50 dark:bg-teal-900/20 rounded-lg">
            <div class="flex items-center gap-2 mb-4">
                <flux:icon name="qr-code" class="size-6 text-teal-600 dark:text-teal-400" />
                <flux:heading size="md" class="text-teal-900 dark:text-teal-100">Pague com Pix</flux:heading>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                {{-- QR Code --}}
                <div class="flex justify-center">
                    @if ($payment->pix_qr_code)
                        <div class="p-4 bg-white rounded-lg">
                            <img
                                src="data:image/png;base64,{{ $payment->pix_qr_code }}"
                                alt="QR Code Pix"
                                class="w-48 h-48"
                            />
                        </div>
                    @endif
                </div>

                {{-- Copy & Paste --}}
                <div>
                    <p class="text-sm text-teal-700 dark:text-teal-300 mb-2">
                        Ou copie o codigo Pix:
                    </p>
                    @if ($payment->pix_code)
                        <div class="p-3 bg-white dark:bg-zinc-800 rounded-lg font-mono text-xs break-all mb-3">
                            {{ $payment->pix_code }}
                        </div>
                        <flux:button
                            wire:click="copyPixCode"
                            variant="outline"
                            size="sm"
                        >
                            <flux:icon name="clipboard" class="size-4 mr-1" />
                            Copiar
                        </flux:button>
                    @endif

                    @if ($payment->expires_at)
                        <p class="mt-4 text-sm text-teal-600 dark:text-teal-400">
                            <flux:icon name="clock" class="size-4 inline mr-1" />
                            Valido por {{ $payment->expires_at->diffForHumans(null, true) }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Bank Slip Payment Section --}}
    @if ($this->isPendingPayment && $this->isBankSlipPayment && $payment)
        <div class="mb-6 p-6 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
            <div class="flex items-center gap-2 mb-4">
                <flux:icon name="document-text" class="size-6 text-amber-600 dark:text-amber-400" />
                <flux:heading size="md" class="text-amber-900 dark:text-amber-100">Boleto Bancario</flux:heading>
            </div>

            @if ($payment->bank_slip_barcode)
                <p class="text-sm text-amber-700 dark:text-amber-300 mb-2">Linha digitavel:</p>
                <div class="p-3 bg-white dark:bg-zinc-800 rounded-lg font-mono text-sm break-all mb-3">
                    {{ $payment->bank_slip_barcode }}
                </div>
                <div class="flex gap-2 flex-wrap">
                    <flux:button
                        wire:click="copyBarcode"
                        variant="outline"
                        size="sm"
                    >
                        <flux:icon name="clipboard" class="size-4 mr-1" />
                        Copiar
                    </flux:button>
                    @if ($payment->bank_slip_url)
                        <flux:button
                            href="{{ $payment->bank_slip_url }}"
                            target="_blank"
                            variant="outline"
                            size="sm"
                        >
                            <flux:icon name="arrow-down-tray" class="size-4 mr-1" />
                            Baixar boleto
                        </flux:button>
                    @endif
                </div>
            @endif

            @if ($payment->expires_at)
                <p class="mt-4 text-sm text-amber-600 dark:text-amber-400">
                    <flux:icon name="calendar" class="size-4 inline mr-1" />
                    Vencimento: {{ $payment->expires_at->format('d/m/Y') }}
                </p>
            @endif
        </div>
    @endif

    {{-- Order Details --}}
    <div class="space-y-6">
        {{-- Items --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="md">Itens do pedido</flux:heading>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($order->items as $item)
                    <div wire:key="item-{{ $item->id }}" class="p-4 flex gap-4">
                        <div class="flex-1">
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $item->product_name }}
                            </p>
                            @if ($item->variant_name)
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $item->variant_name }}
                                </p>
                            @endif
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                Qtd: {{ $item->quantity }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">
                                R$ {{ number_format($item->subtotal / 100, 2, ',', '.') }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Shipping Address --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <flux:heading size="md" class="mb-3">Endereco de entrega</flux:heading>
            <div class="text-zinc-700 dark:text-zinc-300">
                <p class="font-medium">{{ $order->shipping_recipient_name }}</p>
                <p>{{ $order->shipping_street }}, {{ $order->shipping_number }}</p>
                @if ($order->shipping_complement)
                    <p>{{ $order->shipping_complement }}</p>
                @endif
                <p>{{ $order->shipping_neighborhood }}</p>
                <p>{{ $order->shipping_city }}/{{ $order->shipping_state }}</p>
                <p>CEP: {{ $order->shipping_zipcode }}</p>
            </div>
            @if ($order->shipping_days)
                <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        <flux:icon name="truck" class="size-4 inline mr-1" />
                        Previsao de entrega: {{ $this->deliveryEstimate }}
                    </p>
                </div>
            @endif
        </div>

        {{-- Order Summary --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <flux:heading size="md" class="mb-3">Resumo</flux:heading>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-zinc-600 dark:text-zinc-400">Subtotal</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $this->formattedSubtotal }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-600 dark:text-zinc-400">Frete ({{ $order->shipping_carrier }})</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $this->formattedShippingCost }}</dd>
                </div>
                @if ($order->discount > 0)
                    <div class="flex justify-between text-green-600 dark:text-green-400">
                        <dt>
                            Desconto
                            @if ($order->coupon_code)
                                <span class="font-mono text-xs">({{ $order->coupon_code }})</span>
                            @endif
                        </dt>
                        <dd class="font-medium">-{{ $this->formattedDiscount }}</dd>
                    </div>
                @endif
                <div class="flex justify-between pt-3 border-t border-zinc-200 dark:border-zinc-700">
                    <dt class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Total</dt>
                    <dd class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ $this->formattedTotal }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Actions --}}
    <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
        @if (!$isGuest)
            <flux:button
                href="{{ route('customer.dashboard') }}"
                variant="primary"
            >
                Ver meus pedidos
            </flux:button>
        @else
            <flux:button
                href="{{ route('register') }}"
                variant="outline"
            >
                Criar conta
            </flux:button>
        @endif
        <flux:button
            href="{{ route('home') }}"
            variant="{{ $isGuest ? 'primary' : 'outline' }}"
        >
            Continuar comprando
        </flux:button>
    </div>
</div>

@script
<script>
    $wire.on('copy-to-clipboard', ({ text }) => {
        navigator.clipboard.writeText(text).then(() => {
            $dispatch('notify', { message: 'Copiado!' });
        });
    });
</script>
@endscript
