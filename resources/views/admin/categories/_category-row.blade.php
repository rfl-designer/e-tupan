<tr wire:key="category-{{ $category->id }}" class="hover:bg-neutral-800/50 transition-colors">
    <td class="px-6 py-4">
        <div class="flex items-center gap-3">
            @if ($depth > 0)
                <span class="text-neutral-600">{{ str_repeat('—', $depth) }}</span>
            @endif
            @if ($category->image)
                <img src="{{ Storage::url($category->image) }}" alt="{{ $category->name }}" class="size-8 rounded object-cover" />
            @else
                <div class="size-8 rounded bg-neutral-800 flex items-center justify-center">
                    <flux:icon name="folder" class="size-4 text-neutral-500" />
                </div>
            @endif
            <span class="text-sm font-medium text-white">{{ $category->name }}</span>
        </div>
    </td>
    <td class="px-6 py-4 text-sm text-neutral-300">{{ $category->slug }}</td>
    <td class="px-6 py-4">
        @if ($category->is_active)
            <flux:badge color="green">{{ __('Ativa') }}</flux:badge>
        @else
            <flux:badge color="red">{{ __('Inativa') }}</flux:badge>
        @endif
    </td>
    <td class="px-6 py-4 text-sm text-neutral-400">
        {{ $category->products_count ?? $category->products()->count() }}
    </td>
    <td class="px-6 py-4">
        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('admin.categories.edit', $category) }}">
                <flux:button variant="ghost" size="sm">
                    <flux:icon name="pencil" class="size-4" />
                </flux:button>
            </a>
            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Tem certeza que deseja excluir esta categoria?')">
                @csrf
                @method('DELETE')
                <flux:button variant="ghost" size="sm" type="submit">
                    <flux:icon name="trash" class="size-4 text-red-400" />
                </flux:button>
            </form>
        </div>
    </td>
</tr>
@if ($category->children->isNotEmpty())
    @foreach ($category->children as $child)
        @include('admin.categories._category-row', ['category' => $child, 'depth' => $depth + 1])
    @endforeach
@endif
