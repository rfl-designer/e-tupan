<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.products.index') }}" class="text-neutral-400 hover:text-white transition-colors">
                <flux:icon name="arrow-left" class="size-5" />
            </a>
            <div>
                <flux:heading size="xl">{{ __('Lixeira de Produtos') }}</flux:heading>
                <flux:subheading>{{ __('Produtos excluídos podem ser restaurados ou removidos permanentemente') }}</flux:subheading>
            </div>
        </div>
        @if ($products->total() > 0)
            <flux:button
                variant="ghost"
                wire:click="emptyTrash"
                wire:confirm="{{ __('Tem certeza que deseja esvaziar a lixeira? Esta ação não pode ser desfeita!') }}"
                class="text-red-400 hover:text-red-300"
            >
                <flux:icon name="trash" class="size-4 mr-1" />
                {{ __('Esvaziar Lixeira') }}
            </flux:button>
        @endif
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

    {{-- Info Message --}}
    <flux:callout variant="warning" class="mb-6">
        {{ __('Os produtos na lixeira serão excluídos permanentemente após 30 dias.') }}
    </flux:callout>

    {{-- Search --}}
    <div class="mb-6">
        <flux:input
            wire:model.live.debounce.300ms="search"
            type="search"
            placeholder="{{ __('Buscar por nome ou SKU...') }}"
            icon="magnifying-glass"
        />
    </div>

    {{-- Bulk Actions --}}
    @if (count($selectedProducts) > 0)
        <div class="mb-4 flex items-center gap-4 p-4 bg-blue-900/20 rounded-lg border border-blue-800">
            <span class="text-sm text-blue-300">
                {{ count($selectedProducts) }} {{ count($selectedProducts) === 1 ? __('produto selecionado') : __('produtos selecionados') }}
            </span>
            <div class="flex items-center gap-2">
                <flux:button variant="ghost" size="sm" wire:click="bulkAction('restore')">
                    <flux:icon name="arrow-path" class="size-4 mr-1" />
                    {{ __('Restaurar') }}
                </flux:button>
                <flux:button
                    variant="ghost"
                    size="sm"
                    wire:click="bulkAction('force_delete')"
                    wire:confirm="{{ __('Tem certeza que deseja excluir permanentemente os produtos selecionados?') }}"
                    class="text-red-400 hover:text-red-300"
                >
                    <flux:icon name="trash" class="size-4 mr-1" />
                    {{ __('Excluir Permanentemente') }}
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
                        <th class="px-4 py-4 text-left text-sm font-medium text-neutral-400">{{ __('Produto') }}</th>
                        <th class="px-4 py-4 text-left text-sm font-medium text-neutral-400">{{ __('SKU') }}</th>
                        <th class="px-4 py-4 text-left text-sm font-medium text-neutral-400">{{ __('Preço') }}</th>
                        <th class="px-4 py-4 text-left text-sm font-medium text-neutral-400">{{ __('Excluído em') }}</th>
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
                                            class="size-10 rounded-lg object-cover bg-neutral-800 opacity-50"
                                        >
                                    @else
                                        <div class="size-10 rounded-lg bg-neutral-800 flex items-center justify-center opacity-50">
                                            <flux:icon name="photo" class="size-5 text-neutral-600" />
                                        </div>
                                    @endif
                                    <div>
                                        <span class="font-medium text-neutral-400">{{ $product->name }}</span>
                                        @if ($product->type->value === 'variable')
                                            <div class="mt-0.5">
                                                <flux:badge size="sm" color="purple">{{ __('Variável') }}</flux:badge>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-neutral-500">
                                {{ $product->sku ?? '-' }}
                            </td>
                            <td class="px-4 py-4 text-sm text-neutral-400">
                                R$ {{ number_format($product->price_in_reais, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-4 text-sm text-neutral-500">
                                {{ $product->deleted_at->diffForHumans() }}
                                <span class="block text-xs">{{ $product->deleted_at->format('d/m/Y H:i') }}</span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button variant="ghost" size="sm" wire:click="restore({{ $product->id }})">
                                        <flux:icon name="arrow-path" class="size-4" />
                                    </flux:button>
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        wire:click="forceDelete({{ $product->id }})"
                                        wire:confirm="{{ __('Tem certeza que deseja excluir permanentemente este produto?') }}"
                                        class="text-red-400 hover:text-red-300"
                                    >
                                        <flux:icon name="trash" class="size-4" />
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <flux:icon name="trash" class="mx-auto size-12 mb-4 text-neutral-600" />
                                <p class="text-lg font-medium text-neutral-400">{{ __('A lixeira está vazia') }}</p>
                                <p class="mt-1 text-sm text-neutral-500">{{ __('Produtos excluídos aparecerão aqui.') }}</p>
                                <a href="{{ route('admin.products.index') }}" class="mt-4 inline-block">
                                    <flux:button variant="ghost" size="sm">
                                        {{ __('Voltar para Produtos') }}
                                    </flux:button>
                                </a>
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

    {{-- Back to Products --}}
    <div class="mt-6">
        <a href="{{ route('admin.products.index') }}" class="text-sm text-neutral-400 hover:text-white transition-colors">
            &larr; {{ __('Voltar para Produtos') }}
        </a>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</div>
