<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    {{-- Header --}}
    <div class="mb-8">
        <flux:heading size="xl">{{ __('Minha Conta') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
            {{ __('Gerencie suas informacoes pessoais e preferencias') }}
        </flux:text>
    </div>

    {{-- Grid de Cards --}}
    <div class="grid md:grid-cols-2 gap-6">
        {{-- Card Dados Pessoais --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <flux:heading size="lg" class="mb-4">{{ __('Dados Pessoais') }}</flux:heading>

            <div class="space-y-3 text-sm">
                <div class="flex items-start gap-2">
                    <span class="font-medium text-zinc-700 dark:text-zinc-300 min-w-20">{{ __('Nome:') }}</span>
                    <span class="text-zinc-600 dark:text-zinc-400">{{ $user->name }}</span>
                </div>

                <div class="flex items-start gap-2">
                    <span class="font-medium text-zinc-700 dark:text-zinc-300 min-w-20">{{ __('Email:') }}</span>
                    <span class="text-zinc-600 dark:text-zinc-400">{{ $user->email }}</span>
                </div>

                @if($this->maskedCpf)
                    <div class="flex items-start gap-2">
                        <span class="font-medium text-zinc-700 dark:text-zinc-300 min-w-20">{{ __('CPF:') }}</span>
                        <span class="text-zinc-600 dark:text-zinc-400">{{ $this->maskedCpf }}</span>
                    </div>
                @endif

                @if($user->phone)
                    <div class="flex items-start gap-2">
                        <span class="font-medium text-zinc-700 dark:text-zinc-300 min-w-20">{{ __('Telefone:') }}</span>
                        <span class="text-zinc-600 dark:text-zinc-400">{{ $user->phone }}</span>
                    </div>
                @endif
            </div>

            <div class="mt-6">
                <flux:button href="{{ route('profile.edit') }}" variant="ghost" icon="pencil">
                    {{ __('Editar Dados') }}
                </flux:button>
            </div>
        </div>

        {{-- Card Enderecos --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <flux:heading size="lg" class="mb-4">{{ __('Enderecos') }}</flux:heading>

            <div class="space-y-2">
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ trans_choice(':count endereco cadastrado|:count enderecos cadastrados', $addressCount, ['count' => $addressCount]) }}
                </p>

                @if($defaultAddress)
                    <div class="mt-3 p-3 bg-zinc-50 dark:bg-zinc-800 rounded-md">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Endereco padrao:') }}</p>
                        <p class="text-sm text-zinc-700 dark:text-zinc-300">
                            {{ $defaultAddress->street }}, {{ $defaultAddress->number }}
                            @if($defaultAddress->complement)
                                - {{ $defaultAddress->complement }}
                            @endif
                        </p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $defaultAddress->neighborhood }} - {{ $defaultAddress->city }}/{{ $defaultAddress->state }}
                        </p>
                    </div>
                @endif
            </div>

            <div class="mt-6">
                <flux:button href="{{ route('customer.addresses') }}" variant="ghost" icon="map-pin">
                    {{ __('Gerenciar Enderecos') }}
                </flux:button>
            </div>
        </div>

        {{-- Card Pedidos --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="lg">{{ __('Meus Pedidos') }}</flux:heading>
                <span class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $ordersCount }} {{ $ordersCount === 1 ? 'pedido' : 'pedidos' }}
                </span>
            </div>

            @if($recentOrders->isEmpty())
                {{-- Empty State --}}
                <div class="flex flex-col items-center justify-center py-4">
                    <div class="size-12 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-3">
                        <flux:icon name="shopping-bag" class="size-6 text-zinc-400" />
                    </div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 text-center">
                        {{ __('Nenhum pedido realizado ainda.') }}
                    </p>
                </div>

                <div class="mt-4">
                    <flux:button href="{{ route('products.index') }}" variant="ghost" icon="shopping-cart">
                        {{ __('Ir as Compras') }}
                    </flux:button>
                </div>
            @else
                {{-- Orders List --}}
                <div class="space-y-3">
                    @foreach($recentOrders as $order)
                        <div class="flex items-center justify-between py-2 border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                            <div class="flex-1 min-w-0">
                                <p class="font-mono text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $order->order_number }}
                                </p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $order->placed_at->format('d/m/Y') }}
                                </p>
                            </div>
                            <flux:badge :color="$order->status->color()" size="sm">
                                {{ $order->status->label() }}
                            </flux:badge>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    <flux:button href="{{ route('customer.orders') }}" variant="ghost" icon="arrow-right" wire:navigate>
                        {{ __('Ver todos os pedidos') }}
                    </flux:button>
                </div>
            @endif
        </div>

        {{-- Card Seguranca --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <flux:heading size="lg" class="mb-4">{{ __('Seguranca') }}</flux:heading>

            <div class="space-y-2">
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Altere sua senha e configure autenticacao em duas etapas para proteger sua conta.') }}
                </p>

                @if($user->two_factor_confirmed_at)
                    <div class="flex items-center gap-2 mt-3">
                        <flux:icon name="shield-check" class="w-5 h-5 text-green-500" />
                        <span class="text-sm text-green-600 dark:text-green-400">{{ __('2FA ativado') }}</span>
                    </div>
                @else
                    <div class="flex items-center gap-2 mt-3">
                        <flux:icon name="shield-exclamation" class="w-5 h-5 text-amber-500" />
                        <span class="text-sm text-amber-600 dark:text-amber-400">{{ __('2FA desativado') }}</span>
                    </div>
                @endif
            </div>

            <div class="mt-6 flex flex-wrap gap-2">
                <flux:button href="{{ route('user-password.edit') }}" variant="ghost" icon="key">
                    {{ __('Alterar Senha') }}
                </flux:button>
                <flux:button href="{{ route('two-factor.show') }}" variant="ghost" icon="shield-check">
                    {{ __('2FA') }}
                </flux:button>
            </div>
        </div>
    </div>
</div>
