<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Categorias</h3>
        @if($categoria)
            <button wire:click="clearCategory" class="text-xs text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                Limpar
            </button>
        @endif
    </div>

    <div class="space-y-1">
        @forelse($this->categories as $category)
            <div wire:key="category-{{ $category->id }}">
                <button
                    wire:click="$set('categoria', '{{ $category->slug }}')"
                    class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm transition-colors {{ $categoria === $category->slug ? 'bg-zinc-100 font-medium text-zinc-900 dark:bg-zinc-800 dark:text-white' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/50 dark:hover:text-white' }}"
                >
                    <span>{{ $category->name }}</span>
                    @if($categoria === $category->slug)
                        <flux:icon name="check" class="size-4" />
                    @endif
                </button>

                {{-- Subcategorias --}}
                @if($category->children->isNotEmpty())
                    <div class="ml-4 mt-1 space-y-1">
                        @foreach($category->children as $child)
                            <button
                                wire:key="category-child-{{ $child->id }}"
                                wire:click="$set('categoria', '{{ $child->slug }}')"
                                class="flex w-full items-center justify-between rounded-lg px-3 py-1.5 text-left text-sm transition-colors {{ $categoria === $child->slug ? 'bg-zinc-100 font-medium text-zinc-900 dark:bg-zinc-800 dark:text-white' : 'text-zinc-500 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-500 dark:hover:bg-zinc-800/50 dark:hover:text-white' }}"
                            >
                                <span>{{ $child->name }}</span>
                                @if($categoria === $child->slug)
                                    <flux:icon name="check" class="size-4" />
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <p class="px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400">
                Nenhuma categoria disponivel
            </p>
        @endforelse
    </div>
</div>
