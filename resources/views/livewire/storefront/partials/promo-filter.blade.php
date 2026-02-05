<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Ofertas</h3>
        @if($promocao)
            <button wire:click="clearPromoFilter" class="text-xs text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                Limpar
            </button>
        @endif
    </div>

    <div>
        <button
            type="button"
            wire:click="$toggle('promocao')"
            class="flex w-full cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-left transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50"
        >
            <span class="flex size-5 items-center justify-center rounded border {{ $promocao ? 'border-red-500 bg-red-500' : 'border-zinc-300 dark:border-zinc-600' }}">
                @if($promocao)
                    <flux:icon name="check" class="size-3.5 text-white" />
                @endif
            </span>
            <span class="flex items-center gap-2 text-sm {{ $promocao ? 'font-medium text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-400' }}">
                <flux:icon name="tag" class="size-4 text-red-500" />
                Apenas ofertas
            </span>
        </button>
    </div>
</div>
