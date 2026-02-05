<div class="rounded-lg border border-neutral-800 bg-neutral-900">
    {{-- Header --}}
    <div class="flex items-center justify-between border-b border-neutral-800 p-4">
        <div class="flex items-center gap-2">
            <flux:icon name="exclamation-triangle" class="size-5 text-amber-400" />
            <flux:heading size="sm">{{ __('Produtos com Estoque Baixo') }}</flux:heading>
        </div>
        <a href="{{ route('admin.inventory.index', ['stockStatus' => 'low_stock']) }}" class="text-sm text-blue-400 hover:text-blue-300 transition-colors">
            {{ __('Ver todos') }}
        </a>
    </div>

    {{-- Content --}}
    <div class="p-4">
        @if ($lowStockItems->isEmpty())
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <flux:icon name="check-circle" class="size-10 text-green-500 mb-2" />
                <p class="text-sm text-neutral-400">{{ __('Nenhum produto com estoque baixo') }}</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($lowStockItems as $product)
                    <div wire:key="low-stock-{{ $product->id }}" class="flex items-center gap-3 rounded-lg bg-neutral-800/50 p-3">
                        {{-- Product Image --}}
                        @php
                            $image = $product->primaryImage();
                        @endphp
                        @if ($image)
                            <img
                                src="{{ Storage::url($image->path) }}"
                                alt="{{ $product->name }}"
                                class="size-10 rounded-lg object-cover bg-neutral-700"
                            >
                        @else
                            <div class="size-10 rounded-lg bg-neutral-700 flex items-center justify-center">
                                <flux:icon name="cube" class="size-5 text-neutral-500" />
                            </div>
                        @endif

                        {{-- Product Info --}}
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.products.edit', $product) }}" class="block truncate text-sm font-medium text-white hover:text-blue-400 transition-colors">
                                {{ $product->name }}
                            </a>
                            <p class="text-xs text-neutral-500">{{ $product->sku ?? '-' }}</p>
                        </div>

                        {{-- Stock Quantity --}}
                        <div class="text-right">
                            @if ($product->stock_quantity === 0)
                                <flux:badge size="sm" color="red">{{ __('Sem Estoque') }}</flux:badge>
                            @else
                                <span class="text-sm font-medium text-amber-400">{{ $product->stock_quantity }}</span>
                                <p class="text-xs text-neutral-500">{{ __('de') }} {{ $product->getLowStockThreshold() }}</p>
                            @endif
                        </div>

                        {{-- Quick Action --}}
                        <a href="{{ route('admin.inventory.index') }}#product-{{ $product->id }}" class="ml-2">
                            <flux:button variant="ghost" size="sm">
                                <flux:icon name="adjustments-horizontal" class="size-4" />
                            </flux:button>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
