<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <flux:button href="{{ route('admin.customers.index') }}" variant="ghost" size="sm">
                <flux:icon name="arrow-left" class="size-4" />
                {{ __('Voltar') }}
            </flux:button>
            <div class="flex items-center gap-3">
                <div class="flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                    <span class="text-lg font-medium text-zinc-600 dark:text-zinc-300">
                        {{ strtoupper(substr($customer->name, 0, 2)) }}
                    </span>
                </div>
                <div>
                    <flux:heading size="xl">{{ $customer->name }}</flux:heading>
                    <flux:subheading>
                        {{ __('Cliente desde') }} {{ $customer->created_at->format('d/m/Y') }}
                    </flux:subheading>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Main Content --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Order Statistics --}}
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-sm text-zinc-500">{{ __('Total de Pedidos') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $ordersCount }}</p>
                </div>
                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-sm text-zinc-500">{{ __('Total Gasto') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">R$ {{ number_format($totalSpent / 100, 2, ',', '.') }}</p>
                </div>
                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-sm text-zinc-500">{{ __('Ticket Medio') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">R$ {{ number_format($averageOrderValue / 100, 2, ',', '.') }}</p>
                </div>
            </div>

            {{-- Order History --}}
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <flux:heading size="lg">{{ __('Historico de Pedidos') }}</flux:heading>
                </div>
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($customer->orders as $order)
                        <a wire:key="order-{{ $order->id }}" href="{{ route('admin.orders.show', $order) }}" class="flex items-center justify-between p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $order->order_number }}</p>
                                <p class="text-sm text-zinc-500">{{ $order->placed_at?->format('d/m/Y H:i') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-medium text-zinc-900 dark:text-white">R$ {{ number_format($order->total / 100, 2, ',', '.') }}</p>
                                <flux:badge color="{{ $order->status->color() }}" size="sm">
                                    {{ $order->status->label() }}
                                </flux:badge>
                            </div>
                        </a>
                    @empty
                        <div class="p-8 text-center">
                            <flux:icon name="shopping-bag" class="mx-auto size-12 text-zinc-300 dark:text-zinc-600" />
                            <p class="mt-4 text-zinc-500">{{ __('Nenhum pedido ainda') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Contact Information --}}
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <flux:heading size="lg">{{ __('Informacoes de Contato') }}</flux:heading>
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex items-center gap-2 text-sm">
                        <flux:icon name="envelope" class="size-4 text-zinc-400" />
                        <span class="text-zinc-600 dark:text-zinc-400">{{ $customer->email }}</span>
                    </div>
                    @if ($customer->phone)
                        <div class="flex items-center gap-2 text-sm">
                            <flux:icon name="phone" class="size-4 text-zinc-400" />
                            <span class="text-zinc-600 dark:text-zinc-400">{{ $customer->phone }}</span>
                        </div>
                    @endif
                    @if ($customer->cpf)
                        <div class="flex items-center gap-2 text-sm">
                            <flux:icon name="identification" class="size-4 text-zinc-400" />
                            <span class="text-zinc-600 dark:text-zinc-400">{{ $customer->cpf }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Security Status --}}
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <flux:heading size="lg">{{ __('Seguranca') }}</flux:heading>
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-600 dark:text-zinc-400">{{ __('Email Verificado') }}</span>
                        @if ($customer->email_verified_at)
                            <flux:badge color="lime" size="sm">{{ __('Sim') }}</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">{{ __('Nao') }}</flux:badge>
                        @endif
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-600 dark:text-zinc-400">{{ __('Autenticacao 2FA') }}</span>
                        @if ($customer->two_factor_secret)
                            <flux:badge color="lime" size="sm">{{ __('2FA Ativo') }}</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">{{ __('Desativado') }}</flux:badge>
                        @endif
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-600 dark:text-zinc-400">{{ __('Status') }}</span>
                        @if ($customer->is_active)
                            <flux:badge color="lime" size="sm">{{ __('Ativo') }}</flux:badge>
                        @else
                            <flux:badge color="red" size="sm">{{ __('Inativo') }}</flux:badge>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Addresses --}}
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <flux:heading size="lg">{{ __('Enderecos') }}</flux:heading>
                </div>
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($customer->addresses as $address)
                        <div wire:key="address-{{ $address->id }}" class="p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $address->label ?? 'Endereco' }}</p>
                                @if ($address->is_default)
                                    <flux:badge color="sky" size="sm">{{ __('Padrao') }}</flux:badge>
                                @endif
                            </div>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $address->street }}, {{ $address->number }}
                                @if ($address->complement)
                                    - {{ $address->complement }}
                                @endif
                            </p>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $address->neighborhood }}, {{ $address->city }}/{{ $address->state }}
                            </p>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                CEP: {{ $address->zipcode }}
                            </p>
                        </div>
                    @empty
                        <div class="p-4 text-center text-zinc-500">
                            {{ __('Nenhum endereco cadastrado') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
