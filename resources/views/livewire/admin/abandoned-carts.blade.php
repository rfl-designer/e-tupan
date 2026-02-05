<div>
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-4">
            <div class="text-sm text-neutral-400">{{ __('Total de Carrinhos') }}</div>
            <div class="mt-1 text-2xl font-semibold text-white">{{ $summary['total_carts'] }}</div>
        </div>
        <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-4">
            <div class="text-sm text-neutral-400">{{ __('Valor Total') }}</div>
            <div class="mt-1 text-2xl font-semibold text-white">R$ {{ number_format($summary['total_value'] / 100, 2, ',', '.') }}</div>
        </div>
        <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-4">
            <div class="text-sm text-neutral-400">{{ __('Valor Medio') }}</div>
            <div class="mt-1 text-2xl font-semibold text-white">R$ {{ number_format($summary['avg_value'] / 100, 2, ',', '.') }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-6 rounded-lg border border-neutral-800 bg-neutral-900 p-4">
        <div class="flex flex-wrap items-end gap-4">
            {{-- Search --}}
            <div class="flex-1 min-w-[200px]">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar por nome ou email..."
                />
            </div>

            {{-- Date From --}}
            <div class="w-40">
                <flux:input
                    type="date"
                    wire:model.live="dateFrom"
                    label="Data inicial"
                />
            </div>

            {{-- Date To --}}
            <div class="w-40">
                <flux:input
                    type="date"
                    wire:model.live="dateTo"
                    label="Data final"
                />
            </div>

            @if ($search || $dateFrom || $dateTo)
                <flux:button wire:click="resetFilters" variant="ghost">
                    {{ __('Limpar') }}
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-lg border border-neutral-800 bg-neutral-900">
        <table class="w-full">
            <thead class="border-b border-neutral-800 bg-neutral-900/50">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-medium text-neutral-400">{{ __('Cliente') }}</th>
                    <th class="px-6 py-4 text-center text-sm font-medium text-neutral-400">{{ __('Itens') }}</th>
                    <th class="px-6 py-4 text-right text-sm font-medium text-neutral-400">{{ __('Valor') }}</th>
                    <th class="px-6 py-4 text-center text-sm font-medium text-neutral-400">{{ __('Abandonado em') }}</th>
                    <th class="px-6 py-4 text-right text-sm font-medium text-neutral-400">{{ __('Acoes') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-800">
                @forelse ($carts as $cart)
                    <tr wire:key="cart-{{ $cart->id }}" class="hover:bg-neutral-800/50 transition-colors">
                        <td class="px-6 py-4">
                            @if ($cart->user)
                                <div>
                                    <div class="font-medium text-white">{{ $cart->user->name }}</div>
                                    <div class="text-sm text-neutral-400">{{ $cart->user->email }}</div>
                                </div>
                            @else
                                <div class="text-neutral-400">{{ __('Visitante') }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm text-neutral-300">
                                {{ $cart->items->sum('quantity') }} {{ $cart->items->sum('quantity') === 1 ? 'item' : 'itens' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="font-medium text-white">R$ {{ number_format($cart->total / 100, 2, ',', '.') }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="text-sm text-neutral-300">{{ $cart->abandoned_at->format('d/m/Y') }}</div>
                            <div class="text-xs text-neutral-500">{{ $cart->abandoned_at->format('H:i') }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <flux:button wire:click="showDetails('{{ $cart->id }}')" variant="ghost" size="sm">
                                    <flux:icon name="eye" class="size-4" />
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-neutral-400">
                            <flux:icon name="shopping-cart" class="mx-auto size-12 mb-4 text-neutral-600" />
                            <p class="text-lg font-medium">{{ __('Nenhum carrinho abandonado') }}</p>
                            <p class="mt-1 text-sm">{{ __('Os carrinhos abandonados aparecerao aqui.') }}</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($carts->hasPages())
        <div class="mt-6">
            {{ $carts->links() }}
        </div>
        <div class="mt-2 text-sm text-neutral-400">
            {{ __('Mostrando') }} {{ $carts->firstItem() }} - {{ $carts->lastItem() }} {{ __('de') }} {{ $carts->total() }}
        </div>
    @elseif ($carts->count() > 0)
        <div class="mt-4 text-sm text-neutral-400">
            {{ __('Mostrando') }} {{ $carts->count() }} {{ $carts->count() === 1 ? 'carrinho' : 'carrinhos' }}
        </div>
    @endif

    {{-- Details Modal --}}
    <flux:modal wire:model="showModal" name="cart-details" class="max-w-2xl">
        @if ($selectedCart)
            <div class="p-6">
                <flux:heading size="lg" class="mb-4">{{ __('Detalhes do Carrinho') }}</flux:heading>

                {{-- Customer Info --}}
                <div class="mb-6 p-4 rounded-lg bg-neutral-800">
                    <div class="text-sm text-neutral-400 mb-1">{{ __('Cliente') }}</div>
                    @if ($selectedCart->user)
                        <div class="font-medium text-white">{{ $selectedCart->user->name }}</div>
                        <div class="text-sm text-neutral-400">{{ $selectedCart->user->email }}</div>
                    @else
                        <div class="text-neutral-400">{{ __('Visitante') }}</div>
                    @endif
                    <div class="mt-2 text-sm text-neutral-500">
                        {{ __('Abandonado em') }}: {{ $selectedCart->abandoned_at->format('d/m/Y H:i') }}
                    </div>
                </div>

                {{-- Cart Items --}}
                <div class="space-y-3">
                    @foreach ($selectedCart->items as $item)
                        <div wire:key="modal-item-{{ $item->id }}" class="flex items-center gap-4 p-3 rounded-lg border border-neutral-700">
                            {{-- Product Image --}}
                            <div class="flex-shrink-0 w-16 h-16 rounded bg-neutral-800 overflow-hidden">
                                @if ($item->product->images->isNotEmpty())
                                    <img
                                        src="{{ Storage::url($item->product->images->first()->path_thumb) }}"
                                        alt="{{ $item->product->name }}"
                                        class="w-full h-full object-cover"
                                    >
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <flux:icon name="photo" class="size-6 text-neutral-600" />
                                    </div>
                                @endif
                            </div>

                            {{-- Product Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-white truncate">{{ $item->product->name }}</div>
                                @if ($item->variant)
                                    <div class="text-sm text-neutral-400">{{ $item->variant->getName() }}</div>
                                @endif
                                <div class="text-sm text-neutral-500">
                                    {{ $item->quantity }} x R$ {{ number_format($item->getEffectivePrice() / 100, 2, ',', '.') }}
                                </div>
                            </div>

                            {{-- Item Total --}}
                            <div class="text-right">
                                <div class="font-medium text-white">
                                    R$ {{ number_format($item->getSubtotal() / 100, 2, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Cart Summary --}}
                <div class="mt-6 pt-4 border-t border-neutral-700">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-neutral-400">{{ __('Subtotal') }}</span>
                        <span class="text-white">R$ {{ number_format($selectedCart->subtotal / 100, 2, ',', '.') }}</span>
                    </div>
                    @if ($selectedCart->discount > 0)
                        <div class="flex justify-between text-sm mb-2 text-green-400">
                            <span>{{ __('Desconto') }}</span>
                            <span>- R$ {{ number_format($selectedCart->discount / 100, 2, ',', '.') }}</span>
                        </div>
                    @endif
                    @if ($selectedCart->shipping_cost !== null)
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-neutral-400">{{ __('Frete') }}</span>
                            <span class="text-white">R$ {{ number_format($selectedCart->shipping_cost / 100, 2, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-lg font-semibold mt-2 pt-2 border-t border-neutral-700">
                        <span class="text-white">{{ __('Total') }}</span>
                        <span class="text-white">R$ {{ number_format($selectedCart->total / 100, 2, ',', '.') }}</span>
                    </div>
                </div>

                {{-- Close Button --}}
                <div class="mt-6">
                    <flux:button wire:click="closeModal" variant="filled" class="w-full">
                        {{ __('Fechar') }}
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
