<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <flux:button href="{{ route('admin.orders.index') }}" variant="ghost" size="sm">
                <flux:icon name="arrow-left" class="size-4" />
                {{ __('Voltar') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ $order->order_number }}</flux:heading>
                <flux:subheading>
                    {{ __('Criado em') }} {{ $order->placed_at?->format('d/m/Y H:i') }}
                </flux:subheading>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <flux:badge color="{{ $order->status->color() }}" size="lg">
                {{ $order->status->label() }}
            </flux:badge>
            <flux:badge color="{{ $order->payment_status->color() }}" size="lg">
                {{ $order->payment_status->label() }}
            </flux:badge>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Main Content --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Order Items --}}
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <flux:heading size="lg">{{ __('Itens do Pedido') }}</flux:heading>
                </div>
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($order->items as $item)
                        @php $primaryImage = $item->product?->primaryImage(); @endphp
                        <div wire:key="item-{{ $item->id }}" class="flex items-center gap-4 p-4">
                            @if ($primaryImage)
                                <img src="{{ $primaryImage->thumb_url }}" alt="{{ $item->product_name }}" class="size-16 rounded-lg object-cover">
                            @else
                                <div class="flex size-16 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                    <flux:icon name="photo" class="size-8 text-zinc-400" />
                                </div>
                            @endif
                            <div class="flex-1">
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $item->product_name }}</p>
                                @if ($item->product_sku)
                                    <p class="text-sm text-zinc-500">SKU: {{ $item->product_sku }}</p>
                                @endif
                                @if ($item->variant_name)
                                    <p class="text-sm text-zinc-500">{{ $item->variant_name }}</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-zinc-500">{{ $item->quantity }} x R$ {{ number_format($item->getEffectivePrice() / 100, 2, ',', '.') }}</p>
                                <p class="font-medium text-zinc-900 dark:text-white">R$ {{ number_format($item->subtotal / 100, 2, ',', '.') }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-zinc-500">
                            {{ __('Nenhum item encontrado') }}
                        </div>
                    @endforelse
                </div>
                {{-- Order Totals --}}
                <div class="border-t border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-600 dark:text-zinc-400">{{ __('Subtotal') }}</span>
                            <span class="text-zinc-900 dark:text-white">R$ {{ number_format($order->subtotal / 100, 2, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-600 dark:text-zinc-400">{{ __('Frete') }}</span>
                            <span class="text-zinc-900 dark:text-white">R$ {{ number_format($order->shipping_cost / 100, 2, ',', '.') }}</span>
                        </div>
                        @if ($order->discount > 0)
                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-600 dark:text-zinc-400">
                                    {{ __('Desconto') }}
                                    @if ($order->coupon_code)
                                        <span class="ml-1 rounded bg-zinc-200 px-1 py-0.5 text-xs dark:bg-zinc-700">{{ $order->coupon_code }}</span>
                                    @endif
                                </span>
                                <span class="text-lime-600 dark:text-lime-400">- R$ {{ number_format($order->discount / 100, 2, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between border-t border-zinc-200 pt-2 text-lg font-semibold dark:border-zinc-600">
                            <span class="text-zinc-900 dark:text-white">{{ __('Total') }}</span>
                            <span class="text-zinc-900 dark:text-white">R$ {{ number_format($order->total / 100, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Shipping Information --}}
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <flux:heading size="lg">{{ __('Informacoes de Entrega') }}</flux:heading>
                </div>
                <div class="p-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-sm font-medium text-zinc-500">{{ __('Endereco') }}</p>
                            <p class="mt-1 text-zinc-900 dark:text-white">
                                {{ $order->shipping_street }}, {{ $order->shipping_number }}
                                @if ($order->shipping_complement)
                                    - {{ $order->shipping_complement }}
                                @endif
                            </p>
                            <p class="text-zinc-600 dark:text-zinc-400">
                                {{ $order->shipping_neighborhood }}, {{ $order->shipping_city }}/{{ $order->shipping_state }}
                            </p>
                            <p class="text-zinc-600 dark:text-zinc-400">CEP: {{ $order->shipping_zipcode }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-zinc-500">{{ __('Metodo de Envio') }}</p>
                            <p class="mt-1 text-zinc-900 dark:text-white">{{ $order->shipping_method }}</p>
                            @if ($order->shipping_carrier)
                                <p class="text-zinc-600 dark:text-zinc-400">{{ $order->shipping_carrier }}</p>
                            @endif
                            <p class="text-zinc-600 dark:text-zinc-400">R$ {{ number_format($order->shipping_cost / 100, 2, ',', '.') }}</p>
                            @if ($order->shipping_days)
                                <p class="text-sm text-zinc-500">{{ $order->shipping_days }} dias uteis</p>
                            @endif
                        </div>
                    </div>
                    @if ($order->tracking_number)
                        <div class="mt-4 rounded-lg bg-sky-50 p-3 dark:bg-sky-900/20">
                            <p class="text-sm font-medium text-sky-800 dark:text-sky-300">{{ __('Codigo de Rastreio') }}</p>
                            <p class="mt-1 font-mono text-sky-900 dark:text-sky-200">{{ $order->tracking_number }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Customer Notes --}}
            @if ($order->notes)
                <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                        <flux:heading size="lg">{{ __('Observacoes do Cliente') }}</flux:heading>
                    </div>
                    <div class="p-4">
                        <p class="text-zinc-700 dark:text-zinc-300">{{ $order->notes }}</p>
                    </div>
                </div>
            @endif

            {{-- Internal Notes --}}
            <livewire:admin.orders.order-notes :order="$order" />
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Actions --}}
            <livewire:admin.orders.order-actions :order="$order" />

            {{-- Timeline --}}
            <livewire:admin.orders.order-timeline :order="$order" />

            {{-- Customer Information --}}
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <flux:heading size="lg">{{ __('Cliente') }}</flux:heading>
                </div>
                <div class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <flux:icon name="user" class="size-5 text-zinc-500" />
                        </div>
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-white">
                                {{ $order->customer_name ?? '-' }}
                            </p>
                            <p class="text-sm text-zinc-500">
                                {{ $order->isGuest() ? 'Visitante' : 'Cliente cadastrado' }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-4 space-y-2">
                        <div class="flex items-center gap-2 text-sm">
                            <flux:icon name="envelope" class="size-4 text-zinc-400" />
                            <span class="text-zinc-600 dark:text-zinc-400">{{ $order->customer_email ?? '-' }}</span>
                        </div>
                        @if ($order->customer_phone)
                            <div class="flex items-center gap-2 text-sm">
                                <flux:icon name="phone" class="size-4 text-zinc-400" />
                                <span class="text-zinc-600 dark:text-zinc-400">{{ $order->customer_phone }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Payment Information --}}
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <flux:heading size="lg">{{ __('Pagamento') }}</flux:heading>
                </div>
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-600 dark:text-zinc-400">{{ __('Status') }}</span>
                        <flux:badge color="{{ $order->payment_status->color() }}" size="sm">
                            {{ $order->payment_status->label() }}
                        </flux:badge>
                    </div>
                    @if ($order->payments->count() > 0)
                        <div class="mt-3 space-y-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                            @foreach ($order->payments as $payment)
                                <div class="text-sm">
                                    <p class="text-zinc-600 dark:text-zinc-400">{{ $payment->method->label() }}</p>
                                    <p class="font-medium text-zinc-900 dark:text-white">R$ {{ number_format($payment->amount / 100, 2, ',', '.') }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
