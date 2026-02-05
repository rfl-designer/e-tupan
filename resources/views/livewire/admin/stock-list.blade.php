<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.dashboard') }}" class="text-neutral-400 hover:text-white transition-colors">
                <flux:icon name="arrow-left" class="size-5" />
            </a>
            <div>
                <flux:heading size="xl">{{ __('Estoque') }}</flux:heading>
                <flux:subheading>{{ __('Gerencie o estoque dos produtos') }}</flux:subheading>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.inventory.movements') }}">
                <flux:button variant="ghost">
                    <flux:icon name="clipboard-document-list" class="size-4 mr-1" />
                    {{ __('Historico') }}
                </flux:button>
            </a>
        </div>
    </div>

    {{-- Notification Listener --}}
    <div
        x-data="{
            show: false,
            type: 'success',
            message: ''
        }"
        x-on:notify.window="
            show = true;
            type = $event.detail.type;
            message = $event.detail.message;
            setTimeout(() => show = false, 4000);
        "
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform translate-y-2"
        x-cloak
        class="fixed bottom-4 right-4 z-50"
    >
        <div
            :class="{
                'bg-green-900/90 border-green-700': type === 'success',
                'bg-red-900/90 border-red-700': type === 'error'
            }"
            class="rounded-lg border px-4 py-3 shadow-lg"
        >
            <div class="flex items-center gap-2">
                <template x-if="type === 'success'">
                    <flux:icon name="check-circle" class="size-5 text-green-400" />
                </template>
                <template x-if="type === 'error'">
                    <flux:icon name="x-circle" class="size-5 text-red-400" />
                </template>
                <span class="text-sm text-white" x-text="message"></span>
            </div>
        </div>
    </div>

    {{-- Search and Filters --}}
    <div class="mb-6 space-y-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            {{-- Search --}}
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="{{ __('Buscar por nome ou SKU...') }}"
                    icon="magnifying-glass"
                />
            </div>

            {{-- Toggle Filters --}}
            <flux:button
                variant="ghost"
                wire:click="$toggle('showFilters')"
                class="{{ $showFilters ? 'bg-neutral-800' : '' }}"
            >
                <flux:icon name="funnel" class="size-4 mr-1" />
                {{ __('Filtros') }}
                @if ($category || $stockStatus)
                    <flux:badge size="sm" color="blue" class="ml-1">{{ collect([$category, $stockStatus])->filter()->count() }}</flux:badge>
                @endif
            </flux:button>
        </div>

        {{-- Filters Panel --}}
        @if ($showFilters)
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 p-4 bg-neutral-900/50 rounded-lg border border-neutral-800">
                {{-- Stock Status Filter --}}
                <flux:select wire:model.live="stockStatus" label="{{ __('Status de Estoque') }}">
                    @foreach ($stockStatusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                {{-- Category Filter --}}
                <flux:select wire:model.live="category" label="{{ __('Categoria') }}">
                    <option value="">{{ __('Todas as categorias') }}</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @foreach ($cat->children as $child)
                            <option value="{{ $child->id }}">&nbsp;&nbsp;{{ $child->name }}</option>
                            @foreach ($child->children as $grandchild)
                                <option value="{{ $grandchild->id }}">&nbsp;&nbsp;&nbsp;&nbsp;{{ $grandchild->name }}</option>
                            @endforeach
                        @endforeach
                    @endforeach
                </flux:select>

                {{-- Clear Filters --}}
                <div class="sm:col-span-2 flex justify-end">
                    <flux:button variant="ghost" size="sm" wire:click="clearFilters">
                        <flux:icon name="x-mark" class="size-4 mr-1" />
                        {{ __('Limpar Filtros') }}
                    </flux:button>
                </div>
            </div>
        @endif
    </div>

    {{-- Stock Table --}}
    <div class="overflow-hidden rounded-lg border border-neutral-800 bg-neutral-900">
        <div class="table-responsive overflow-x-auto scroll-touch">
            <table class="w-full min-w-[900px]">
                <thead class="border-b border-neutral-800 bg-neutral-900/50">
                    <tr>
                        <th class="px-4 py-4 text-left text-sm font-medium text-neutral-400">
                            <button wire:click="sortBy('name')" class="flex items-center gap-1 hover:text-white">
                                {{ __('Produto') }}
                                @if ($sortBy === 'name')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-4 text-left text-sm font-medium text-neutral-400">{{ __('SKU') }}</th>
                        <th class="px-4 py-4 text-center text-sm font-medium text-neutral-400">
                            <button wire:click="sortBy('stock_quantity')" class="flex items-center gap-1 hover:text-white mx-auto">
                                {{ __('Estoque') }}
                                @if ($sortBy === 'stock_quantity')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-4 text-center text-sm font-medium text-neutral-400">{{ __('Reservado') }}</th>
                        <th class="px-4 py-4 text-center text-sm font-medium text-neutral-400">{{ __('Disponivel') }}</th>
                        <th class="px-4 py-4 text-center text-sm font-medium text-neutral-400">{{ __('Status') }}</th>
                        <th class="px-4 py-4 text-right text-sm font-medium text-neutral-400">{{ __('Acoes') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-800">
                    @forelse ($stockItems as $product)
                        @php
                            $reserved = $product->getReservedQuantity();
                            $available = $product->getAvailableQuantity();
                            $isLowStock = $product->isLowStock();
                            $isOutOfStock = $product->stock_quantity === 0;
                        @endphp
                        <tr wire:key="product-{{ $product->id }}" class="hover:bg-neutral-800/50 transition-colors">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    @php
                                        $image = $product->primaryImage();
                                    @endphp
                                    @if ($image)
                                        <img
                                            src="{{ Storage::url($image->path) }}"
                                            alt="{{ $product->name }}"
                                            class="size-10 rounded-lg object-cover bg-neutral-800"
                                        >
                                    @else
                                        <div class="size-10 rounded-lg bg-neutral-800 flex items-center justify-center">
                                            <flux:icon name="cube" class="size-5 text-neutral-600" />
                                        </div>
                                    @endif
                                    <div>
                                        <a href="{{ route('admin.products.edit', $product) }}" class="font-medium text-white hover:text-blue-400 transition-colors">
                                            {{ $product->name }}
                                        </a>
                                        @if ($product->categories->isNotEmpty())
                                            <div class="text-xs text-neutral-500">{{ $product->categories->first()->name }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-neutral-300">
                                {{ $product->sku ?? '-' }}
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="text-sm font-medium {{ $isOutOfStock ? 'text-red-400' : ($isLowStock ? 'text-amber-400' : 'text-white') }}">
                                    {{ $product->stock_quantity }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="text-sm {{ $reserved > 0 ? 'text-blue-400' : 'text-neutral-500' }}">
                                    {{ $reserved }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="text-sm font-medium {{ $available === 0 ? 'text-red-400' : 'text-green-400' }}">
                                    {{ $available }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                @if ($isOutOfStock)
                                    <flux:badge size="sm" color="red">{{ __('Sem Estoque') }}</flux:badge>
                                @elseif ($isLowStock)
                                    <flux:badge size="sm" color="amber">{{ __('Estoque Baixo') }}</flux:badge>
                                @else
                                    <flux:badge size="sm" color="green">{{ __('Em Estoque') }}</flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        class="touch-target"
                                        wire:click="openAdjustModal('product', {{ $product->id }})"
                                        title="{{ __('Ajustar Estoque') }}"
                                    >
                                        <flux:icon name="adjustments-horizontal" class="size-4" />
                                    </flux:button>
                                    <a href="{{ route('admin.products.edit', $product) }}">
                                        <flux:button variant="ghost" size="sm" class="touch-target" title="{{ __('Editar Produto') }}">
                                            <flux:icon name="pencil" class="size-4" />
                                        </flux:button>
                                    </a>
                                </div>
                            </td>
                        </tr>

                        {{-- Display variants if any --}}
                        @foreach ($product->variants as $variant)
                            @php
                                $variantReserved = $variant->getReservedQuantity();
                                $variantAvailable = $variant->getAvailableQuantity();
                                $variantIsLowStock = $variant->isLowStock();
                                $variantIsOutOfStock = $variant->stock_quantity === 0;
                            @endphp
                            <tr wire:key="variant-{{ $variant->id }}" class="hover:bg-neutral-800/50 transition-colors bg-neutral-900/30">
                                <td class="px-4 py-3 pl-12">
                                    <div class="flex items-center gap-3">
                                        <div class="size-8 rounded bg-neutral-800 flex items-center justify-center">
                                            <flux:icon name="square-3-stack-3d" class="size-4 text-neutral-500" />
                                        </div>
                                        <div>
                                            <span class="text-sm text-neutral-300">{{ $variant->getAttributeDescription() }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-neutral-400">
                                    {{ $variant->sku ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-sm {{ $variantIsOutOfStock ? 'text-red-400' : ($variantIsLowStock ? 'text-amber-400' : 'text-neutral-300') }}">
                                        {{ $variant->stock_quantity }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-sm {{ $variantReserved > 0 ? 'text-blue-400' : 'text-neutral-500' }}">
                                        {{ $variantReserved }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-sm {{ $variantAvailable === 0 ? 'text-red-400' : 'text-green-400' }}">
                                        {{ $variantAvailable }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($variantIsOutOfStock)
                                        <flux:badge size="sm" color="red">{{ __('Sem Estoque') }}</flux:badge>
                                    @elseif ($variantIsLowStock)
                                        <flux:badge size="sm" color="amber">{{ __('Estoque Baixo') }}</flux:badge>
                                    @else
                                        <flux:badge size="sm" color="green">{{ __('Em Estoque') }}</flux:badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end">
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            class="touch-target"
                                            wire:click="openAdjustModal('variant', {{ $variant->id }})"
                                            title="{{ __('Ajustar Estoque') }}"
                                        >
                                            <flux:icon name="adjustments-horizontal" class="size-4" />
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <flux:icon name="cube" class="mx-auto size-12 mb-4 text-neutral-600" />
                                <p class="text-lg font-medium text-neutral-400">{{ __('Nenhum produto encontrado') }}</p>
                                @if ($search || $category || $stockStatus)
                                    <p class="mt-1 text-sm text-neutral-500">{{ __('Tente ajustar os filtros de busca.') }}</p>
                                    <flux:button variant="ghost" size="sm" wire:click="clearFilters" class="mt-4">
                                        {{ __('Limpar Filtros') }}
                                    </flux:button>
                                @else
                                    <p class="mt-1 text-sm text-neutral-500">{{ __('Nenhum produto com gerenciamento de estoque ativo.') }}</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($stockItems->hasPages())
            <div class="border-t border-neutral-800 px-6 py-4">
                {{ $stockItems->links() }}
            </div>
        @endif
    </div>

    {{-- Adjust Stock Modal --}}
    <flux:modal wire:model="adjustModal" class="w-full max-w-lg md:max-w-full md:h-full md:max-h-full md:rounded-none lg:max-w-lg lg:h-auto lg:max-h-[90vh] lg:rounded-lg">
        <div class="p-6">
            <flux:heading size="lg" class="mb-4">{{ __('Ajustar Estoque') }}</flux:heading>

            @if ($adjustItemName)
                <div class="mb-4 p-3 bg-neutral-800/50 rounded-lg">
                    <p class="text-sm text-neutral-400">{{ __('Produto') }}</p>
                    <p class="font-medium text-white">{{ $adjustItemName }}</p>
                    <p class="text-sm text-neutral-400 mt-1">
                        {{ __('Estoque atual:') }}
                        <span class="text-white font-medium">{{ $adjustCurrentStock }}</span>
                        {{ __('unidades') }}
                    </p>
                </div>
            @endif

            <form wire:submit="submitAdjustment" class="space-y-4">
                <flux:select
                    wire:model="adjustMovementType"
                    label="{{ __('Tipo de Movimentacao') }}"
                >
                    @foreach ($movementTypeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                <flux:input
                    wire:model="adjustQuantity"
                    type="number"
                    label="{{ __('Quantidade') }}"
                    placeholder="{{ __('Ex: 10 para entrada, -5 para saida') }}"
                />
                @error('adjustQuantity')
                    <p class="text-sm text-red-400">{{ $message }}</p>
                @enderror

                <flux:textarea
                    wire:model="adjustNotes"
                    label="{{ __('Motivo/Observacao') }}"
                    placeholder="{{ __('Descreva o motivo do ajuste...') }}"
                    rows="3"
                />
                @error('adjustNotes')
                    <p class="text-sm text-red-400">{{ $message }}</p>
                @enderror

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" type="button" wire:click="closeAdjustModal">
                        {{ __('Cancelar') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Ajustar Estoque') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Back to Dashboard --}}
    <div class="mt-6">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-neutral-400 hover:text-white transition-colors">
            &larr; {{ __('Voltar ao Dashboard') }}
        </a>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</div>
