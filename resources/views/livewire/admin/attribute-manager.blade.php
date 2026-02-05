<div>
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

    {{-- Add New Value Form --}}
    <form wire:submit="addValue" class="mb-6">
        <div class="flex gap-2">
            <div class="flex-1">
                <flux:input
                    wire:model="newValue"
                    type="text"
                    placeholder="{{ __('Novo valor...') }}"
                    class="w-full"
                />
            </div>
            @if ($attribute->isColor())
                <div class="w-24">
                    <flux:input
                        wire:model="newColorHex"
                        type="color"
                        class="h-10 w-full cursor-pointer"
                    />
                </div>
            @endif
            <flux:button type="submit" variant="primary" size="sm">
                <flux:icon name="plus" class="size-4" />
            </flux:button>
        </div>
        @error('newValue')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
        @error('newColorHex')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </form>

    {{-- Values List --}}
    <div
        x-data="attributeValues()"
        x-init="initSortable()"
        class="space-y-2"
    >
        @forelse ($values as $value)
            <div
                wire:key="value-{{ $value['id'] }}"
                data-id="{{ $value['id'] }}"
                class="value-item flex items-center gap-2 rounded-lg border border-neutral-700 bg-neutral-800 p-3"
            >
                {{-- Drag Handle --}}
                <div class="drag-handle cursor-grab text-neutral-500 hover:text-neutral-300">
                    <flux:icon name="bars-3" class="size-4" />
                </div>

                @if ($editingValueId === $value['id'])
                    {{-- Edit Mode --}}
                    <div class="flex flex-1 items-center gap-2">
                        <input
                            type="text"
                            wire:model="editingValue"
                            class="flex-1 rounded border border-neutral-600 bg-neutral-700 px-2 py-1 text-sm text-white focus:border-blue-500 focus:outline-none"
                        />
                        @if ($attribute->isColor())
                            <input
                                type="color"
                                wire:model="editingColorHex"
                                class="h-8 w-12 cursor-pointer rounded border border-neutral-600"
                            />
                        @endif
                        <flux:button wire:click="saveEditing" variant="primary" size="sm">
                            <flux:icon name="check" class="size-4" />
                        </flux:button>
                        <flux:button wire:click="cancelEditing" variant="ghost" size="sm">
                            <flux:icon name="x-mark" class="size-4" />
                        </flux:button>
                    </div>
                @else
                    {{-- Display Mode --}}
                    @if ($attribute->isColor() && $value['color_hex'])
                        <div
                            class="size-6 rounded-full border border-neutral-600"
                            style="background-color: {{ $value['color_hex'] }}"
                            title="{{ $value['color_hex'] }}"
                        ></div>
                    @endif

                    <span class="flex-1 text-sm text-white">{{ $value['value'] }}</span>

                    @if ($attribute->isColor() && $value['color_hex'])
                        <code class="rounded bg-neutral-700 px-2 py-0.5 text-xs text-neutral-300">
                            {{ $value['color_hex'] }}
                        </code>
                    @endif

                    <div class="flex items-center gap-1">
                        <flux:button wire:click="startEditing({{ $value['id'] }})" variant="ghost" size="sm">
                            <flux:icon name="pencil" class="size-4" />
                        </flux:button>
                        <flux:button
                            wire:click="deleteValue({{ $value['id'] }})"
                            wire:confirm="{{ __('Tem certeza que deseja excluir este valor?') }}"
                            variant="ghost"
                            size="sm"
                        >
                            <flux:icon name="trash" class="size-4 text-red-400" />
                        </flux:button>
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-lg border border-dashed border-neutral-700 p-6 text-center">
                <flux:icon name="tag" class="mx-auto size-8 text-neutral-600" />
                <p class="mt-2 text-sm text-neutral-400">{{ __('Nenhum valor cadastrado') }}</p>
                <p class="text-xs text-neutral-500">{{ __('Adicione valores usando o campo acima') }}</p>
            </div>
        @endforelse
    </div>

    @script
    <script>
        Alpine.data('attributeValues', () => ({
            sortableInstance: null,

            initSortable() {
                this.$nextTick(() => {
                    const container = this.$el;
                    if (!container) return;

                    this.sortableInstance = new Sortable(container, {
                        animation: 150,
                        handle: '.drag-handle',
                        ghostClass: 'sortable-ghost',
                        chosenClass: 'sortable-chosen',
                        dragClass: 'sortable-drag',
                        onEnd: (evt) => {
                            const items = Array.from(container.querySelectorAll('.value-item'))
                                .map(el => parseInt(el.dataset.id));
                            $wire.reorderValues(items);
                        }
                    });
                });
            }
        }));
    </script>
    @endscript

    <style>
        [x-cloak] { display: none !important; }

        .sortable-ghost {
            opacity: 0.4;
            background-color: rgb(38 38 38);
        }

        .sortable-chosen {
            background-color: rgb(38 38 38);
        }

        .sortable-drag {
            background-color: rgb(23 23 23);
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }
    </style>
</div>
