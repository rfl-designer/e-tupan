<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Preco</h3>
        @if($this->hasPriceFilter())
            <button wire:click="clearPriceFilter" class="text-xs text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                Limpar
            </button>
        @endif
    </div>

    <div class="space-y-4">
        {{-- Price Range Slider --}}
        <div>
            <div class="mb-2 flex items-center justify-between text-sm">
                <span class="text-zinc-600 dark:text-zinc-400">
                    R$ <span class="tabular-nums">{{ number_format($precoRange[0], 0, ',', '.') }}</span>
                </span>
                <span class="text-zinc-600 dark:text-zinc-400">
                    R$ <span class="tabular-nums">{{ number_format($precoRange[1], 0, ',', '.') }}</span>
                </span>
            </div>

            <flux:slider
                wire:model.live="precoRange"
                range
                min="0"
                max="1000"
                step="10"
                min-steps-between="1"
            />
        </div>

        {{-- Quick Price Ranges --}}
        <div class="flex flex-wrap gap-2">
            <button
                wire:click="$set('precoRange', [0, 100])"
                class="rounded-full border border-zinc-200 px-3 py-1 text-xs text-zinc-600 transition-colors hover:border-zinc-400 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-500 dark:hover:text-white {{ $precoRange[0] === 0 && $precoRange[1] === 100 ? 'bg-zinc-100 dark:bg-zinc-800' : '' }}"
            >
                Ate R$ 100
            </button>
            <button
                wire:click="$set('precoRange', [100, 300])"
                class="rounded-full border border-zinc-200 px-3 py-1 text-xs text-zinc-600 transition-colors hover:border-zinc-400 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-500 dark:hover:text-white {{ $precoRange[0] === 100 && $precoRange[1] === 300 ? 'bg-zinc-100 dark:bg-zinc-800' : '' }}"
            >
                R$ 100 - R$ 300
            </button>
            <button
                wire:click="$set('precoRange', [300, 1000])"
                class="rounded-full border border-zinc-200 px-3 py-1 text-xs text-zinc-600 transition-colors hover:border-zinc-400 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-500 dark:hover:text-white {{ $precoRange[0] === 300 && $precoRange[1] === 1000 ? 'bg-zinc-100 dark:bg-zinc-800' : '' }}"
            >
                Acima de R$ 300
            </button>
        </div>
    </div>
</div>
