<div
    class="flex items-center"
    x-data="{
        selectedIndex: -1,
        get suggestions() { return $wire.suggestions },
        navigate(direction) {
            if (this.suggestions.length === 0) return;
            if (direction === 'down') {
                this.selectedIndex = this.selectedIndex < this.suggestions.length - 1 ? this.selectedIndex + 1 : 0;
            } else {
                this.selectedIndex = this.selectedIndex > 0 ? this.selectedIndex - 1 : this.suggestions.length - 1;
            }
        },
        selectCurrent() {
            if (this.selectedIndex >= 0 && this.suggestions[this.selectedIndex]) {
                $wire.selectSuggestion(this.suggestions[this.selectedIndex].slug);
            }
        },
        reset() {
            this.selectedIndex = -1;
        }
    }"
    x-on:keydown.arrow-down.prevent="navigate('down')"
    x-on:keydown.arrow-up.prevent="navigate('up')"
    x-on:keydown.enter.prevent="selectedIndex >= 0 ? selectCurrent() : $wire.search()"
    x-on:keydown.escape="$wire.suggestions = []; reset()"
>
    {{-- Desktop Search --}}
    <div class="relative hidden lg:block">
        <form wire:submit="search">
            <div class="relative">
                <flux:input
                    wire:model.live.debounce.300ms="query"
                    type="search"
                    icon="magnifying-glass"
                    placeholder="Buscar produtos..."
                    class="w-64"
                    autocomplete="off"
                    x-on:focus="reset()"
                />
                {{-- Loading indicator --}}
                <div wire:loading wire:target="query" class="pointer-events-none absolute inset-y-0 right-8 flex items-center">
                    <flux:icon name="arrow-path" class="size-4 animate-spin text-zinc-400" />
                </div>
            </div>
        </form>

        {{-- Suggestions Dropdown --}}
        @if($this->showSuggestions)
            <div class="absolute left-0 top-full z-50 mt-1 w-80 rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @foreach($suggestions as $index => $suggestion)
                        <li wire:key="suggestion-{{ $suggestion['id'] }}">
                            <a
                                href="{{ route('products.show', $suggestion['slug']) }}"
                                wire:navigate
                                class="flex items-center gap-3 px-3 py-2 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-700"
                                :class="{ 'bg-zinc-100 dark:bg-zinc-700': selectedIndex === {{ $index }} }"
                                x-on:mouseenter="selectedIndex = {{ $index }}"
                            >
                                @if($suggestion['image'])
                                    <img
                                        src="{{ $suggestion['image'] }}"
                                        alt="{{ $suggestion['name'] }}"
                                        class="size-10 rounded object-cover"
                                    />
                                @else
                                    <div class="flex size-10 items-center justify-center rounded bg-zinc-100 dark:bg-zinc-700">
                                        <flux:icon name="photo" class="size-5 text-zinc-400" />
                                    </div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">
                                        {{ $suggestion['name'] }}
                                    </p>
                                    <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                                        {{ $suggestion['formatted_price'] }}
                                    </p>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="border-t border-zinc-100 p-2 dark:border-zinc-700">
                    <a
                        href="{{ route('search', ['q' => $query]) }}"
                        wire:navigate
                        class="flex items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-zinc-600 transition-colors hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-700 dark:hover:text-white"
                    >
                        <flux:icon name="magnifying-glass" class="size-4" />
                        Ver todos os resultados
                    </a>
                </div>
            </div>
        @endif
    </div>

    {{-- Mobile Search Toggle --}}
    <flux:button
        wire:click="toggleMobileSearch"
        variant="ghost"
        size="sm"
        icon="magnifying-glass"
        class="lg:hidden"
        aria-label="Buscar"
    />

    {{-- Mobile Search Overlay --}}
    @if($showMobileSearch)
        <div
            class="fixed inset-0 z-50 bg-black/50 lg:hidden"
            wire:click="toggleMobileSearch"
        ></div>
        <div class="fixed inset-x-0 top-0 z-50 bg-white p-4 shadow-lg lg:hidden dark:bg-zinc-900">
            <form wire:submit="search" class="flex items-center gap-2">
                <div class="relative flex-1">
                    <div class="relative">
                        <flux:input
                            wire:model.live.debounce.300ms="query"
                            type="search"
                            icon="magnifying-glass"
                            placeholder="Buscar produtos..."
                            autofocus
                            autocomplete="off"
                        />
                        {{-- Loading indicator --}}
                        <div wire:loading wire:target="query" class="pointer-events-none absolute inset-y-0 right-8 flex items-center">
                            <flux:icon name="arrow-path" class="size-4 animate-spin text-zinc-400" />
                        </div>
                    </div>

                    {{-- Mobile Suggestions Dropdown --}}
                    @if($this->showSuggestions)
                        <div class="absolute left-0 top-full z-50 mt-1 w-full rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
                            <ul class="max-h-64 divide-y divide-zinc-100 overflow-y-auto dark:divide-zinc-700">
                                @foreach($suggestions as $suggestion)
                                    <li wire:key="mobile-suggestion-{{ $suggestion['id'] }}">
                                        <a
                                            href="{{ route('products.show', $suggestion['slug']) }}"
                                            wire:navigate
                                            class="flex items-center gap-3 px-3 py-2 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-700"
                                        >
                                            @if($suggestion['image'])
                                                <img
                                                    src="{{ $suggestion['image'] }}"
                                                    alt="{{ $suggestion['name'] }}"
                                                    class="size-10 rounded object-cover"
                                                />
                                            @else
                                                <div class="flex size-10 items-center justify-center rounded bg-zinc-100 dark:bg-zinc-700">
                                                    <flux:icon name="photo" class="size-5 text-zinc-400" />
                                                </div>
                                            @endif
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">
                                                    {{ $suggestion['name'] }}
                                                </p>
                                                <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                                                    {{ $suggestion['formatted_price'] }}
                                                </p>
                                            </div>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="border-t border-zinc-100 p-2 dark:border-zinc-700">
                                <a
                                    href="{{ route('search', ['q' => $query]) }}"
                                    wire:navigate
                                    class="flex items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-zinc-600 transition-colors hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-700 dark:hover:text-white"
                                >
                                    <flux:icon name="magnifying-glass" class="size-4" />
                                    Ver todos os resultados
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
                <flux:button type="submit" variant="primary" icon="magnifying-glass" aria-label="Buscar" />
                <flux:button
                    type="button"
                    wire:click="toggleMobileSearch"
                    variant="ghost"
                    icon="x-mark"
                    aria-label="Fechar"
                />
            </form>
        </div>
    @endif
</div>
