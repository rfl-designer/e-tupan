@php
    $hasChildren = $category->children->isNotEmpty();
    $isExpanded = in_array($category->id, $expandedCategories);
    $productsCount = $category->products_count ?? $category->products()->count();
@endphp

<div
    wire:key="category-{{ $category->id }}"
    class="category-item"
    data-id="{{ $category->id }}"
>
    {{-- Category Row --}}
    <div class="group flex items-center px-6 py-4 hover:bg-neutral-800/50 transition-colors">
        <div class="grid w-full grid-cols-12 gap-4 items-center">
            {{-- Name Column --}}
            <div class="col-span-5 flex items-center gap-3">
                {{-- Drag Handle --}}
                <div class="drag-handle cursor-grab text-neutral-600 hover:text-neutral-400 active:cursor-grabbing">
                    <flux:icon name="bars-3" class="size-4" />
                </div>

                {{-- Indentation --}}
                @if ($level > 0)
                    <div class="flex items-center">
                        @for ($i = 0; $i < $level; $i++)
                            <span class="w-6 border-l border-neutral-700"></span>
                        @endfor
                    </div>
                @endif

                {{-- Expand/Collapse Toggle --}}
                @if ($hasChildren)
                    <button
                        type="button"
                        wire:click="toggleExpand({{ $category->id }})"
                        class="flex size-6 items-center justify-center rounded hover:bg-neutral-700 transition-colors"
                    >
                        <flux:icon
                            name="chevron-right"
                            class="size-4 text-neutral-400 transition-transform duration-200 {{ $isExpanded ? 'rotate-90' : '' }}"
                        />
                    </button>
                @else
                    <span class="size-6"></span>
                @endif

                {{-- Category Image --}}
                @if ($category->image)
                    <img
                        src="{{ Storage::url($category->image) }}"
                        alt="{{ $category->name }}"
                        class="size-8 rounded object-cover"
                    />
                @else
                    <div class="flex size-8 items-center justify-center rounded bg-neutral-800">
                        <flux:icon name="folder" class="size-4 text-neutral-500" />
                    </div>
                @endif

                {{-- Category Name --}}
                <span class="text-sm font-medium text-white truncate">{{ $category->name }}</span>
            </div>

            {{-- Slug Column --}}
            <div class="col-span-2">
                <span class="text-sm text-neutral-400 truncate">{{ $category->slug }}</span>
            </div>

            {{-- Status Column --}}
            <div class="col-span-2">
                @if ($category->is_active)
                    <flux:badge color="green">{{ __('Ativa') }}</flux:badge>
                @else
                    <flux:badge color="red">{{ __('Inativa') }}</flux:badge>
                @endif
            </div>

            {{-- Products Count Column --}}
            <div class="col-span-1 text-center">
                <span class="text-sm text-neutral-400">{{ $productsCount }}</span>
            </div>

            {{-- Actions Column --}}
            <div class="col-span-2 flex items-center justify-end gap-1">
                <a href="{{ route('admin.categories.edit', $category) }}">
                    <flux:button variant="ghost" size="sm">
                        <flux:icon name="pencil" class="size-4" />
                    </flux:button>
                </a>
                <flux:button
                    variant="ghost"
                    size="sm"
                    wire:click="delete({{ $category->id }})"
                    wire:confirm="{{ __('Tem certeza que deseja excluir esta categoria?') }}"
                >
                    <flux:icon name="trash" class="size-4 text-red-400" />
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Children Categories --}}
    @if ($hasChildren)
        <div
            x-show="@js($isExpanded)"
            x-collapse
            class="category-list border-l border-neutral-800 ml-6"
            data-parent-id="{{ $category->id }}"
        >
            @foreach ($category->children->sortBy('position') as $child)
                @include('livewire.admin._category-item', ['category' => $child, 'level' => $level + 1])
            @endforeach
        </div>
    @endif
</div>
