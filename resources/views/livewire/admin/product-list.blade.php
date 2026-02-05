<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.dashboard') }}" class="text-neutral-400 hover:text-white transition-colors">
                <flux:icon name="arrow-left" class="size-5" />
            </a>
            <div>
                <flux:heading size="xl">{{ __('Produtos') }}</flux:heading>
                <flux:subheading>{{ __('Gerencie os produtos do catálogo') }}</flux:subheading>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.products.trash') }}">
                <flux:button variant="ghost" size="sm">
                    <flux:icon name="trash" class="size-4 mr-1" />
                    {{ __('Lixeira') }}
                </flux:button>
            </a>
            <a href="{{ route('admin.products.create') }}">
                <flux:button variant="primary">
                    <flux:icon name="plus" class="size-4 mr-1" />
                    {{ __('Novo Produto') }}
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
                @if ($status || $type || $category)
                    <flux:badge size="sm" color="blue" class="ml-1">{{ collect([$status, $type, $category])->filter()->count() }}</flux:badge>
                @endif
            </flux:button>
        </div>

        {{-- Filters Panel --}}
        @if ($showFilters)
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 p-4 bg-neutral-900/50 rounded-lg border border-neutral-800">
                {{-- Status Filter --}}
                <flux:select wire:model.live="status" label="{{ __('Status') }}">
                    <option value="">{{ __('Todos os status') }}</option>
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                {{-- Type Filter --}}
                <flux:select wire:model.live="type" label="{{ __('Tipo') }}">
                    <option value="">{{ __('Todos os tipos') }}</option>
                    @foreach ($typeOptions as $value => $label)
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
                <div class="sm:col-span-3 flex justify-end">
                    <flux:button variant="ghost" size="sm" wire:click="clearFilters">
                        <flux:icon name="x-mark" class="size-4 mr-1" />
                        {{ __('Limpar Filtros') }}
                    </flux:button>
                </div>
            </div>
        @endif
    </div>

    {{-- Bulk Actions --}}
    @if (count($selectedProducts) > 0)
        <div class="mb-4 flex items-center gap-4 p-4 bg-blue-900/20 rounded-lg border border-blue-800">
            <span class="text-sm text-blue-300">
                {{ count($selectedProducts) }} {{ count($selectedProducts) === 1 ? __('produto selecionado') : __('produtos selecionados') }}
            </span>
            <div class="flex items-center gap-2">
                <flux:button variant="ghost" size="sm" wire:click="bulkAction('activate')">
                    <flux:icon name="check" class="size-4 mr-1" />
                    {{ __('Ativar') }}
                </flux:button>
                <flux:button variant="ghost" size="sm" wire:click="bulkAction('deactivate')">
                    <flux:icon name="x-mark" class="size-4 mr-1" />
                    {{ __('Desativar') }}
                </flux:button>
                <flux:button variant="ghost" size="sm" wire:click="bulkAction('delete')" class="text-red-400 hover:text-red-300">
                    <flux:icon name="trash" class="size-4 mr-1" />
                    {{ __('Excluir') }}
                </flux:button>
            </div>
        </div>
    @endif

    {{-- Products Table --}}
    <div class="overflow-hidden rounded-lg border border-neutral-800 bg-neutral-900">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-neutral-800 bg-neutral-900/50">
                    <tr>
                        <th class="w-12 px-4 py-4">
                            <flux:checkbox wire:model.live="selectAll" />
                        </th>
                        <th class="px-4 py-4 text-left text-sm font-medium text-neutral-400">
                            <button wire:click="sortBy('name')" class="flex items-center gap-1 hover:text-white">
                                {{ __('Produto') }}
                                @if ($sortBy === 'name')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-4 text-left text-sm font-medium text-neutral-400">
                            <button wire:click="sortBy('sku')" class="flex items-center gap-1 hover:text-white">
                                {{ __('SKU') }}
                                @if ($sortBy === 'sku')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-4 text-left text-sm font-medium text-neutral-400">
                            <button wire:click="sortBy('price')" class="flex items-center gap-1 hover:text-white">
                                {{ __('Preço') }}
                                @if ($sortBy === 'price')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-4 text-left text-sm font-medium text-neutral-400">
                            <button wire:click="sortBy('stock_quantity')" class="flex items-center gap-1 hover:text-white">
                                {{ __('Estoque') }}
                                @if ($sortBy === 'stock_quantity')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-4 text-left text-sm font-medium text-neutral-400">{{ __('Status') }}</th>
                        <th class="px-4 py-4 text-right text-sm font-medium text-neutral-400">{{ __('Ações') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-800">
                    @forelse ($products as $product)
                        <tr wire:key="product-{{ $product->id }}" class="hover:bg-neutral-800/50 transition-colors">
                            <td class="px-4 py-4">
                                <flux:checkbox wire:model.live="selectedProducts" value="{{ $product->id }}" />
                            </td>
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
                                            <flux:icon name="photo" class="size-5 text-neutral-600" />
                                        </div>
                                    @endif
                                    <div>
                                        <a href="{{ route('admin.products.edit', $product) }}" class="font-medium text-white hover:text-blue-400 transition-colors">
                                            {{ $product->name }}
                                        </a>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            @if ($product->type->value === 'variable')
                                                <flux:badge size="sm" color="purple">{{ __('Variável') }}</flux:badge>
                                            @endif
                                            @if ($product->categories->isNotEmpty())
                                                <span class="text-xs text-neutral-500">{{ $product->categories->first()->name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-neutral-300">
                                {{ $product->sku ?? '-' }}
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-sm">
                                    @if ($product->isOnSale())
                                        <span class="text-neutral-500 line-through">R$ {{ number_format($product->price_in_reais, 2, ',', '.') }}</span>
                                        <span class="text-green-400 font-medium ml-1">R$ {{ number_format($product->sale_price_in_reais, 2, ',', '.') }}</span>
                                    @else
                                        <span class="text-white">R$ {{ number_format($product->price_in_reais, 2, ',', '.') }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                @if (!$product->manage_stock)
                                    <span class="text-sm text-neutral-400">{{ __('Ilimitado') }}</span>
                                @elseif ($product->stock_quantity === 0)
                                    <flux:badge size="sm" color="red">{{ __('Esgotado') }}</flux:badge>
                                @elseif ($product->stock_quantity <= 5)
                                    <flux:badge size="sm" color="amber">{{ $product->stock_quantity }}</flux:badge>
                                @else
                                    <span class="text-sm text-neutral-300">{{ $product->stock_quantity }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                @switch($product->status->value)
                                    @case('active')
                                        <flux:badge color="green">{{ __('Ativo') }}</flux:badge>
                                        @break
                                    @case('inactive')
                                        <flux:badge color="red">{{ __('Inativo') }}</flux:badge>
                                        @break
                                    @case('draft')
                                        <flux:badge color="zinc">{{ __('Rascunho') }}</flux:badge>
                                        @break
                                @endswitch
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.products.edit', $product) }}">
                                        <flux:button variant="ghost" size="sm">
                                            <flux:icon name="pencil" class="size-4" />
                                        </flux:button>
                                    </a>
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm">
                                            <flux:icon name="ellipsis-vertical" class="size-4" />
                                        </flux:button>
                                        <flux:menu>
                                            <flux:menu.item wire:click="duplicate({{ $product->id }})">
                                                <flux:icon name="document-duplicate" class="size-4 mr-2" />
                                                {{ __('Duplicar') }}
                                            </flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item
                                                wire:click="delete({{ $product->id }})"
                                                wire:confirm="{{ __('Tem certeza que deseja mover este produto para a lixeira?') }}"
                                                class="text-red-400"
                                            >
                                                <flux:icon name="trash" class="size-4 mr-2" />
                                                {{ __('Excluir') }}
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <flux:icon name="cube" class="mx-auto size-12 mb-4 text-neutral-600" />
                                <p class="text-lg font-medium text-neutral-400">{{ __('Nenhum produto encontrado') }}</p>
                                @if ($search || $status || $type || $category)
                                    <p class="mt-1 text-sm text-neutral-500">{{ __('Tente ajustar os filtros de busca.') }}</p>
                                    <flux:button variant="ghost" size="sm" wire:click="clearFilters" class="mt-4">
                                        {{ __('Limpar Filtros') }}
                                    </flux:button>
                                @else
                                    <p class="mt-1 text-sm text-neutral-500">{{ __('Comece criando seu primeiro produto.') }}</p>
                                    <a href="{{ route('admin.products.create') }}" class="mt-4 inline-block">
                                        <flux:button variant="primary" size="sm">
                                            <flux:icon name="plus" class="size-4 mr-1" />
                                            {{ __('Criar Produto') }}
                                        </flux:button>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($products->hasPages())
            <div class="border-t border-neutral-800 px-6 py-4">
                {{ $products->links() }}
            </div>
        @endif
    </div>

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
