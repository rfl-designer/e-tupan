<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    {{-- Breadcrumb --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400 mb-2">
            <a href="{{ route('customer.dashboard') }}" class="hover:text-zinc-700 dark:hover:text-zinc-200" wire:navigate>
                {{ __('Minha Conta') }}
            </a>
            <flux:icon name="chevron-right" class="size-4" />
            <a href="{{ route('customer.orders') }}" class="hover:text-zinc-700 dark:hover:text-zinc-200" wire:navigate>
                {{ __('Pedidos') }}
            </a>
            <flux:icon name="chevron-right" class="size-4" />
            <span class="font-medium text-zinc-900 dark:text-white">{{ $order->order_number }}</span>
        </div>

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('Pedido') }} {{ $order->order_number }}</flux:heading>
                <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
                    {{ __('Realizado em') }} {{ $order->placed_at->format('d/m/Y \à\s H:i') }}
                </flux:text>
            </div>
            <div class="flex items-center gap-3">
                <flux:badge :color="$order->status->color()" size="sm">
                    <flux:icon :name="$order->status->icon()" class="size-3" />
                    {{ $order->status->label() }}
                </flux:badge>
            </div>
        </div>
    </div>

    {{-- Status do Pedido --}}
    <div class="mb-6 rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Status') }}</flux:heading>
        </div>

        <div class="space-y-4 px-6 py-4">
            {{-- Status do Pedido e Pagamento --}}
            <div class="flex flex-wrap items-center gap-4">
                {{-- Status do Pedido --}}
                <div class="flex items-center gap-2">
                    <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Pedido') }}:</span>
                    <flux:badge :color="$order->status->color()" size="sm">
                        <flux:icon :name="$order->status->icon()" class="size-3" />
                        {{ $order->status->label() }}
                    </flux:badge>
                </div>

                {{-- Status do Pagamento --}}
                <div class="flex items-center gap-2">
                    <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Pagamento') }}:</span>
                    <flux:badge :color="$order->payment_status->color()" size="sm">
                        <flux:icon :name="$order->payment_status->icon()" class="size-3" />
                        {{ $order->payment_status->label() }}
                    </flux:badge>
                </div>
            </div>

            {{-- Datas --}}
            <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm">
                {{-- Data de Envio --}}
                @if ($order->shipped_at)
                    <div class="flex items-center gap-2">
                        <flux:icon name="truck" class="size-4 text-zinc-400" />
                        <span class="text-zinc-600 dark:text-zinc-400">{{ __('Enviado em') }}</span>
                        <span class="font-medium text-zinc-900 dark:text-white">{{ $order->shipped_at->format('d/m/Y') }}</span>
                    </div>
                @endif

                {{-- Data de Entrega --}}
                @if ($order->delivered_at)
                    <div class="flex items-center gap-2">
                        <flux:icon name="check-circle" class="size-4 text-zinc-400" />
                        <span class="text-zinc-600 dark:text-zinc-400">{{ __('Entregue em') }}</span>
                        <span class="font-medium text-zinc-900 dark:text-white">{{ $order->delivered_at->format('d/m/Y') }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Rastreamento --}}
    @if ($this->shouldShowTrackingSection())
        <div class="mb-6 rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Rastreamento') }}</flux:heading>
            </div>

            <div class="px-6 py-4">
                @if ($order->tracking_number)
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        {{-- Codigo de Rastreamento --}}
                        <div
                            class="flex items-center gap-3"
                            x-data="{ copied: false, copyToClipboard() { navigator.clipboard.writeText('{{ $order->tracking_number }}'); this.copied = true; setTimeout(() => this.copied = false, 2000); } }"
                        >
                            <div class="flex items-center gap-2 rounded-lg bg-zinc-100 px-4 py-2 dark:bg-zinc-800">
                                <flux:icon name="qr-code" class="size-5 text-zinc-500" />
                                <span class="font-mono font-medium text-zinc-900 dark:text-white">{{ $order->tracking_number }}</span>
                            </div>
                            <button
                                type="button"
                                class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
                                x-on:click="copyToClipboard()"
                            >
                                <template x-if="!copied">
                                    <span class="flex items-center gap-1">
                                        <flux:icon name="clipboard" class="size-4" />
                                        {{ __('Copiar') }}
                                    </span>
                                </template>
                                <template x-if="copied">
                                    <span class="flex items-center gap-1 text-green-600 dark:text-green-400">
                                        <flux:icon name="check" class="size-4" />
                                        {{ __('Copiado!') }}
                                    </span>
                                </template>
                            </button>
                        </div>

                        {{-- Link para Rastrear --}}
                        <flux:button
                            :href="$this->getTrackingUrl()"
                            variant="primary"
                            icon="magnifying-glass"
                            target="_blank"
                        >
                            {{ __('Rastrear Pedido') }}
                        </flux:button>
                    </div>
                @else
                    <div class="flex items-center gap-3 text-zinc-500 dark:text-zinc-400">
                        <flux:icon name="clock" class="size-5" />
                        <span>{{ __('Código de rastreamento em breve') }}</span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Itens do Pedido --}}
    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Itens do Pedido') }}</flux:heading>
        </div>

        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @foreach ($items as $item)
                <div class="flex gap-4 p-4 sm:p-6" wire:key="item-{{ $item->id }}">
                    {{-- Imagem do Produto --}}
                    <div class="shrink-0">
                        @php
                            $product = $item->product;
                            $primaryImage = $product?->images->first();
                        @endphp

                        @if ($primaryImage)
                            <img
                                src="{{ asset('storage/' . $primaryImage->path) }}"
                                alt="{{ $item->product_name }}"
                                class="size-20 rounded-lg object-cover sm:size-24"
                            />
                        @else
                            <div class="flex size-20 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800 sm:size-24">
                                <flux:icon name="photo" class="size-8 text-zinc-400" />
                            </div>
                        @endif
                    </div>

                    {{-- Detalhes do Item --}}
                    <div class="flex min-w-0 flex-1 flex-col justify-between">
                        <div>
                            {{-- Nome do Produto --}}
                            @if ($product && !$product->trashed())
                                <a
                                    href="{{ route('products.show', $product->slug) }}"
                                    class="font-medium text-zinc-900 hover:text-zinc-700 dark:text-white dark:hover:text-zinc-200"
                                    wire:navigate
                                >
                                    {{ $item->product_name }}
                                </a>
                            @else
                                <span class="font-medium text-zinc-900 dark:text-white">
                                    {{ $item->product_name }}
                                </span>
                            @endif

                            {{-- Variante --}}
                            @if ($item->variant_name)
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $item->variant_name }}
                                </p>
                            @endif

                            {{-- SKU --}}
                            <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">
                                SKU: {{ $item->display_sku }}
                            </p>
                        </div>

                        {{-- Quantidade (mobile) --}}
                        <div class="mt-2 sm:hidden">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">
                                Qtd: {{ $item->quantity }}
                            </span>
                        </div>
                    </div>

                    {{-- Preços --}}
                    <div class="flex shrink-0 flex-col items-end justify-between">
                        <div class="text-right">
                            {{-- Preço Unitário --}}
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $this->formatPrice($item->unit_price) }}
                            </p>

                            {{-- Quantidade (desktop) --}}
                            <p class="mt-1 hidden text-sm text-zinc-500 dark:text-zinc-400 sm:block">
                                Qtd: {{ $item->quantity }}
                            </p>
                        </div>

                        {{-- Subtotal --}}
                        <p class="mt-2 font-medium text-zinc-900 dark:text-white">
                            {{ $this->formatPrice($item->subtotal) }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Resumo Financeiro --}}
    <div class="mt-6 rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Resumo') }}</flux:heading>
        </div>

        <div class="space-y-3 px-6 py-4">
            {{-- Subtotal --}}
            <div class="flex items-center justify-between">
                <span class="text-zinc-600 dark:text-zinc-400">{{ __('Subtotal') }}</span>
                <span class="text-zinc-900 dark:text-white">{{ $this->formatPrice($order->subtotal) }}</span>
            </div>

            {{-- Frete --}}
            <div class="flex items-center justify-between">
                <span class="text-zinc-600 dark:text-zinc-400">{{ __('Frete') }}</span>
                @if ($order->shipping_cost > 0)
                    <span class="text-zinc-900 dark:text-white">{{ $this->formatPrice($order->shipping_cost) }}</span>
                @else
                    <span class="text-green-600 dark:text-green-400">{{ __('Grátis') }}</span>
                @endif
            </div>

            {{-- Desconto (se houver) --}}
            @if ($order->discount > 0)
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-zinc-600 dark:text-zinc-400">{{ __('Desconto') }}</span>
                        @if ($order->coupon_code)
                            <flux:badge color="emerald" size="sm">{{ $order->coupon_code }}</flux:badge>
                        @endif
                    </div>
                    <span class="text-green-600 dark:text-green-400">-{{ $this->formatPrice($order->discount) }}</span>
                </div>
            @endif

            {{-- Total --}}
            <div class="flex items-center justify-between border-t border-zinc-200 pt-3 dark:border-zinc-700">
                <span class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Total') }}</span>
                <span class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->formatPrice($order->total) }}</span>
            </div>
        </div>
    </div>

    {{-- Informacoes de Entrega --}}
    <div class="mt-6 rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Informações de Entrega') }}</flux:heading>
        </div>

        <div class="space-y-4 px-6 py-4">
            {{-- Destinatario --}}
            <div class="flex items-start gap-3">
                <flux:icon name="user" class="mt-0.5 size-5 text-zinc-400" />
                <div>
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Destinatário') }}</span>
                    <p class="font-medium text-zinc-900 dark:text-white">{{ $order->shipping_recipient_name }}</p>
                </div>
            </div>

            {{-- Endereco --}}
            <div class="flex items-start gap-3">
                <flux:icon name="map-pin" class="mt-0.5 size-5 text-zinc-400" />
                <div>
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Endereço') }}</span>
                    <p class="font-medium text-zinc-900 dark:text-white">
                        {{ $order->shipping_street }}, {{ $order->shipping_number }}
                        @if ($order->shipping_complement)
                            - {{ $order->shipping_complement }}
                        @endif
                    </p>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $order->shipping_neighborhood }} - {{ $order->shipping_city }}/{{ $order->shipping_state }}
                    </p>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        CEP: {{ $order->shipping_zipcode }}
                    </p>
                </div>
            </div>

            {{-- Metodo de Envio --}}
            <div class="flex items-start gap-3">
                <flux:icon name="truck" class="mt-0.5 size-5 text-zinc-400" />
                <div>
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Método de Envio') }}</span>
                    <p class="font-medium text-zinc-900 dark:text-white">
                        {{ $this->formatShippingMethod($order->shipping_method) }}
                        @if ($order->shipping_carrier)
                            <span class="text-zinc-500 dark:text-zinc-400">- {{ $order->shipping_carrier }}</span>
                        @endif
                    </p>
                    @if ($order->shipping_cost > 0)
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $this->formatPrice($order->shipping_cost) }}
                        </p>
                    @else
                        <p class="text-sm text-green-600 dark:text-green-400">
                            {{ __('Grátis') }}
                        </p>
                    @endif
                </div>
            </div>

            {{-- Prazo de Entrega --}}
            @if ($order->shipping_days)
                <div class="flex items-start gap-3">
                    <flux:icon name="clock" class="mt-0.5 size-5 text-zinc-400" />
                    <div>
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Prazo Estimado') }}</span>
                        <p class="font-medium text-zinc-900 dark:text-white">
                            {{ $this->getShippingDaysText() }}
                        </p>
                        @if ($order->shipped_at && $order->estimated_delivery_date)
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ __('Previsão') }}: {{ $order->estimated_delivery_date->format('d/m/Y') }}
                            </p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Observacoes (Notas Visiveis ao Cliente) --}}
    @if ($customerNotes->isNotEmpty())
        <div class="mt-6 rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Observações') }}</flux:heading>
            </div>

            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($customerNotes as $note)
                    <div class="px-6 py-4" wire:key="note-{{ $note->id }}">
                        <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                            <flux:icon name="calendar" class="size-4" />
                            <span>{{ $note->created_at->format('d/m/Y') }}</span>
                        </div>
                        <p class="mt-2 text-zinc-700 dark:text-zinc-300">{{ $note->note }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Botao Voltar --}}
    <div class="mt-6">
        <flux:button href="{{ route('customer.orders') }}" variant="ghost" icon="arrow-left" wire:navigate>
            {{ __('Voltar para pedidos') }}
        </flux:button>
    </div>
</div>
