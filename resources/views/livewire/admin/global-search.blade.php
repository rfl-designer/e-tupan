<div
    x-data="{
        open: @entangle('isOpen'),
        init() {
            document.addEventListener('keydown', (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                    e.preventDefault();
                    this.$refs.searchInput.focus();
                }
                if (e.key === 'Escape') {
                    this.open = false;
                    @this.close();
                }
            });
        }
    }"
    class="relative"
>
    {{-- Search Input --}}
    <div class="relative">
        <flux:input
            x-ref="searchInput"
            wire:model.live.debounce.300ms="query"
            wire:focus="open"
            icon="magnifying-glass"
            placeholder="Buscar... (Ctrl+K)"
            class="w-64"
        />
    </div>

    {{-- Results Dropdown --}}
    <div
        x-show="open && $wire.query.length >= 2"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.away="open = false; @this.close()"
        class="absolute right-0 top-full z-50 mt-2 w-96 rounded-xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
    >
        @if($this->hasResults)
            <div class="max-h-96 overflow-y-auto p-2">
                {{-- Orders --}}
                @if($this->results['orders']->isNotEmpty())
                    <div class="px-3 py-2 text-xs font-medium uppercase tracking-wider text-zinc-500">
                        Pedidos
                    </div>
                    @foreach($this->results['orders'] as $result)
                        <a
                            href="{{ $result['url'] }}"
                            class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                            @click="open = false; @this.close()"
                        >
                            <div class="flex h-8 w-8 items-center justify-center rounded bg-blue-100 dark:bg-blue-900/30">
                                <flux:icon name="{{ $result['icon'] }}" class="size-4 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-zinc-900 dark:text-white">{{ $result['title'] }}</p>
                                <p class="truncate text-sm text-zinc-500">{{ $result['subtitle'] }}</p>
                            </div>
                        </a>
                    @endforeach
                @endif

                {{-- Products --}}
                @if($this->results['products']->isNotEmpty())
                    <div class="px-3 py-2 text-xs font-medium uppercase tracking-wider text-zinc-500">
                        Produtos
                    </div>
                    @foreach($this->results['products'] as $result)
                        <a
                            href="{{ $result['url'] }}"
                            class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                            @click="open = false; @this.close()"
                        >
                            <div class="flex h-8 w-8 items-center justify-center rounded bg-green-100 dark:bg-green-900/30">
                                <flux:icon name="{{ $result['icon'] }}" class="size-4 text-green-600 dark:text-green-400" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-zinc-900 dark:text-white">{{ $result['title'] }}</p>
                                <p class="truncate text-sm text-zinc-500">{{ $result['subtitle'] }}</p>
                            </div>
                        </a>
                    @endforeach
                @endif

                {{-- Customers --}}
                @if($this->results['customers']->isNotEmpty())
                    <div class="px-3 py-2 text-xs font-medium uppercase tracking-wider text-zinc-500">
                        Clientes
                    </div>
                    @foreach($this->results['customers'] as $result)
                        <a
                            href="{{ $result['url'] }}"
                            class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                            @click="open = false; @this.close()"
                        >
                            <div class="flex h-8 w-8 items-center justify-center rounded bg-purple-100 dark:bg-purple-900/30">
                                <flux:icon name="{{ $result['icon'] }}" class="size-4 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-zinc-900 dark:text-white">{{ $result['title'] }}</p>
                                <p class="truncate text-sm text-zinc-500">{{ $result['subtitle'] }}</p>
                            </div>
                        </a>
                    @endforeach
                @endif
            </div>
        @else
            <div class="p-8 text-center">
                <flux:icon name="magnifying-glass" class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
                <p class="mt-2 text-sm text-zinc-500">Nenhum resultado encontrado</p>
            </div>
        @endif

        <div class="border-t border-zinc-200 px-4 py-2 text-xs text-zinc-500 dark:border-zinc-700">
            <span class="font-medium">Dica:</span> Use <kbd class="rounded bg-zinc-100 px-1 py-0.5 dark:bg-zinc-800">Ctrl+K</kbd> para abrir a busca
        </div>
    </div>
</div>
