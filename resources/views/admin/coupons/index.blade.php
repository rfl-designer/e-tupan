<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="min-h-svh p-6 md:p-10">
            <div class="mx-auto max-w-6xl">
                {{-- Header --}}
                <div class="mb-8 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('admin.dashboard') }}" class="text-neutral-400 hover:text-white transition-colors">
                            <flux:icon name="arrow-left" class="size-5" />
                        </a>
                        <div>
                            <flux:heading size="xl">{{ __('Cupons de Desconto') }}</flux:heading>
                            <flux:subheading>{{ __('Gerencie os cupons de desconto da loja') }}</flux:subheading>
                        </div>
                    </div>
                    <a href="{{ route('admin.coupons.create') }}">
                        <flux:button variant="primary">
                            <flux:icon name="plus" class="size-4 mr-1" />
                            {{ __('Novo Cupom') }}
                        </flux:button>
                    </a>
                </div>

                {{-- Success Message --}}
                @if (session('success'))
                    <flux:callout variant="success" class="mb-6">
                        {{ session('success') }}
                    </flux:callout>
                @endif

                {{-- Error Messages --}}
                @if ($errors->any())
                    <flux:callout variant="danger" class="mb-6">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </flux:callout>
                @endif

                {{-- Filters --}}
                <div class="mb-6 rounded-lg border border-neutral-800 bg-neutral-900 p-4">
                    <form method="GET" action="{{ route('admin.coupons.index') }}" class="flex flex-wrap items-end gap-4">
                        {{-- Search --}}
                        <div class="flex-1 min-w-[200px]">
                            <flux:input
                                name="search"
                                :value="request('search')"
                                placeholder="Buscar por codigo ou nome..."
                            />
                        </div>

                        {{-- Status Filter --}}
                        <div class="w-40">
                            <flux:select name="status">
                                <option value="">{{ __('Todos os status') }}</option>
                                <option value="active" @selected(request('status') === 'active')>{{ __('Ativos') }}</option>
                                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Inativos') }}</option>
                                <option value="expired" @selected(request('status') === 'expired')>{{ __('Expirados') }}</option>
                            </flux:select>
                        </div>

                        {{-- Type Filter --}}
                        <div class="w-44">
                            <flux:select name="type">
                                <option value="">{{ __('Todos os tipos') }}</option>
                                @foreach ($types as $value => $label)
                                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                                @endforeach
                            </flux:select>
                        </div>

                        <flux:button type="submit" variant="filled">
                            <flux:icon name="magnifying-glass" class="size-4" />
                        </flux:button>

                        @if (request()->hasAny(['search', 'status', 'type']))
                            <a href="{{ route('admin.coupons.index') }}">
                                <flux:button variant="ghost">
                                    {{ __('Limpar') }}
                                </flux:button>
                            </a>
                        @endif
                    </form>
                </div>

                {{-- Coupons Table --}}
                <div class="overflow-hidden rounded-lg border border-neutral-800 bg-neutral-900">
                    <table class="w-full">
                        <thead class="border-b border-neutral-800 bg-neutral-900/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-medium text-neutral-400">{{ __('Codigo') }}</th>
                                <th class="px-6 py-4 text-left text-sm font-medium text-neutral-400">{{ __('Nome') }}</th>
                                <th class="px-6 py-4 text-left text-sm font-medium text-neutral-400">{{ __('Tipo/Valor') }}</th>
                                <th class="px-6 py-4 text-center text-sm font-medium text-neutral-400">{{ __('Usos') }}</th>
                                <th class="px-6 py-4 text-center text-sm font-medium text-neutral-400">{{ __('Status') }}</th>
                                <th class="px-6 py-4 text-right text-sm font-medium text-neutral-400">{{ __('Acoes') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-800">
                            @forelse ($coupons as $coupon)
                                <tr wire:key="coupon-{{ $coupon->id }}" class="hover:bg-neutral-800/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <code class="rounded bg-neutral-800 px-2 py-1 text-sm font-medium text-white">{{ $coupon->code }}</code>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-neutral-300">{{ $coupon->name }}</span>
                                        @if ($coupon->description)
                                            <p class="text-xs text-neutral-500 mt-1 truncate max-w-xs">{{ $coupon->description }}</p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($coupon->type === \App\Domain\Marketing\Enums\CouponType::Percentage)
                                            <flux:badge color="blue">{{ $coupon->value }}%</flux:badge>
                                            @if ($coupon->maximum_discount)
                                                <span class="text-xs text-neutral-500 ml-1">max R$ {{ number_format($coupon->maximum_discount / 100, 2, ',', '.') }}</span>
                                            @endif
                                        @elseif ($coupon->type === \App\Domain\Marketing\Enums\CouponType::Fixed)
                                            <flux:badge color="green">R$ {{ number_format($coupon->value / 100, 2, ',', '.') }}</flux:badge>
                                        @else
                                            <flux:badge color="purple">{{ __('Frete Gratis') }}</flux:badge>
                                        @endif
                                        @if ($coupon->minimum_order_value)
                                            <p class="text-xs text-neutral-500 mt-1">Min. R$ {{ number_format($coupon->minimum_order_value / 100, 2, ',', '.') }}</p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-sm text-neutral-300">{{ $coupon->times_used }}</span>
                                        @if ($coupon->usage_limit)
                                            <span class="text-neutral-500">/{{ $coupon->usage_limit }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if (!$coupon->is_active)
                                            <flux:badge color="zinc">{{ __('Inativo') }}</flux:badge>
                                        @elseif ($coupon->expires_at && $coupon->expires_at->isPast())
                                            <flux:badge color="red">{{ __('Expirado') }}</flux:badge>
                                        @elseif ($coupon->starts_at && $coupon->starts_at->isFuture())
                                            <flux:badge color="yellow">{{ __('Agendado') }}</flux:badge>
                                        @elseif ($coupon->usage_limit && $coupon->times_used >= $coupon->usage_limit)
                                            <flux:badge color="orange">{{ __('Esgotado') }}</flux:badge>
                                        @else
                                            <flux:badge color="green">{{ __('Ativo') }}</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            {{-- Toggle Active --}}
                                            <form method="POST" action="{{ route('admin.coupons.toggle-active', $coupon) }}">
                                                @csrf
                                                @method('PATCH')
                                                <flux:button variant="ghost" size="sm" type="submit" title="{{ $coupon->is_active ? 'Desativar' : 'Ativar' }}">
                                                    @if ($coupon->is_active)
                                                        <flux:icon name="pause" class="size-4 text-yellow-400" />
                                                    @else
                                                        <flux:icon name="play" class="size-4 text-green-400" />
                                                    @endif
                                                </flux:button>
                                            </form>

                                            {{-- Edit --}}
                                            <a href="{{ route('admin.coupons.edit', $coupon) }}">
                                                <flux:button variant="ghost" size="sm">
                                                    <flux:icon name="pencil" class="size-4" />
                                                </flux:button>
                                            </a>

                                            {{-- Delete --}}
                                            <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" onsubmit="return confirm('Tem certeza que deseja excluir este cupom?')">
                                                @csrf
                                                @method('DELETE')
                                                <flux:button variant="ghost" size="sm" type="submit">
                                                    <flux:icon name="trash" class="size-4 text-red-400" />
                                                </flux:button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-neutral-400">
                                        <flux:icon name="ticket" class="mx-auto size-12 mb-4 text-neutral-600" />
                                        <p class="text-lg font-medium">{{ __('Nenhum cupom encontrado') }}</p>
                                        <p class="mt-1 text-sm">{{ __('Comece criando seu primeiro cupom de desconto.') }}</p>
                                        <a href="{{ route('admin.coupons.create') }}" class="mt-4 inline-block">
                                            <flux:button variant="primary" size="sm">
                                                <flux:icon name="plus" class="size-4 mr-1" />
                                                {{ __('Criar Cupom') }}
                                            </flux:button>
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($coupons->hasPages())
                    <div class="mt-6">
                        {{ $coupons->links() }}
                    </div>
                @endif

                {{-- Back to Dashboard --}}
                <div class="mt-6">
                    <a href="{{ route('admin.dashboard') }}" class="text-sm text-neutral-400 hover:text-white transition-colors">
                        &larr; {{ __('Voltar ao Dashboard') }}
                    </a>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
