<div>
    {{-- Upload Section --}}
    <div class="mb-6">
        <flux:heading size="sm" class="mb-4">{{ __('Adicionar Imagens') }}</flux:heading>

        <div
            x-data="{
                isDragging: false,
                handleDrop(event) {
                    this.isDragging = false;
                    const files = event.dataTransfer.files;
                    if (files.length > 0) {
                        $wire.upload('newImages', files[0], () => {}, () => {}, (event) => {});
                    }
                }
            }"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop($event)"
            :class="isDragging ? 'border-blue-500 bg-blue-900/20' : 'border-neutral-700'"
            class="relative border-2 border-dashed rounded-lg p-8 transition-colors"
        >
            <div class="text-center">
                <flux:icon name="photo" class="mx-auto size-12 text-neutral-500 mb-4" />
                <p class="text-sm text-neutral-400 mb-2">{{ __('Arraste imagens aqui ou') }}</p>
                <label class="cursor-pointer">
                    <span class="text-sm text-blue-400 hover:text-blue-300">{{ __('selecione arquivos') }}</span>
                    <input
                        type="file"
                        wire:model="newImages"
                        accept="image/*"
                        multiple
                        class="hidden"
                    >
                </label>
                <p class="text-xs text-neutral-500 mt-2">{{ __('PNG, JPG, WebP até 5MB cada') }}</p>
            </div>
        </div>

        {{-- Preview of new images --}}
        @if (count($newImages) > 0)
            <div class="mt-4 space-y-4">
                <div class="flex items-center gap-4 p-4 bg-neutral-800/50 rounded-lg">
                    <div class="flex gap-2 overflow-x-auto">
                        @foreach ($newImages as $index => $image)
                            <div class="relative shrink-0">
                                <img
                                    src="{{ $image->temporaryUrl() }}"
                                    alt="Preview"
                                    class="size-16 object-cover rounded-lg"
                                >
                                <button
                                    type="button"
                                    wire:click="$set('newImages.{{ $index }}', null)"
                                    class="absolute -top-2 -right-2 p-1 bg-red-600 rounded-full text-white hover:bg-red-500"
                                >
                                    <flux:icon name="x-mark" class="size-3" />
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <flux:button variant="primary" size="sm" wire:click="uploadImages">
                        <flux:icon name="cloud-arrow-up" class="size-4 mr-1" />
                        {{ __('Enviar') }} ({{ count($newImages) }})
                    </flux:button>
                </div>
            </div>
        @endif

        {{-- Validation Errors --}}
        @error('newImages')
            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
        @enderror
        @error('newImages.*')
            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Images Grid --}}
    @if ($images->isNotEmpty())
        <div class="border-t border-neutral-800 pt-6">
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="sm">{{ __('Imagens do Produto') }} ({{ $images->count() }})</flux:heading>
                <span class="text-xs text-neutral-500">{{ __('Arraste para reordenar') }}</span>
            </div>

            <div
                x-data="imageGrid()"
                x-init="initSortable()"
                class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 image-grid"
            >
                @foreach ($images as $image)
                    <div
                        wire:key="image-{{ $image->id }}"
                        data-id="{{ $image->id }}"
                        class="relative group rounded-lg overflow-hidden bg-neutral-800 image-item"
                    >
                        {{-- Image --}}
                        <div class="aspect-square">
                            <img
                                src="{{ Storage::url($image->path) }}"
                                alt="{{ $image->alt_text ?? $product->name }}"
                                class="w-full h-full object-cover"
                            >
                        </div>

                        {{-- Primary Badge --}}
                        @if ($image->is_primary)
                            <div class="absolute top-2 left-2">
                                <flux:badge color="blue" size="sm">{{ __('Principal') }}</flux:badge>
                            </div>
                        @endif

                        {{-- Overlay Actions --}}
                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            @if (!$image->is_primary)
                                <button
                                    type="button"
                                    wire:click="setPrimary({{ $image->id }})"
                                    class="p-2 bg-blue-600 rounded-lg text-white hover:bg-blue-500 transition-colors"
                                    title="{{ __('Definir como principal') }}"
                                >
                                    <flux:icon name="star" class="size-4" />
                                </button>
                            @endif
                            <button
                                type="button"
                                wire:click="startEditing({{ $image->id }})"
                                class="p-2 bg-neutral-600 rounded-lg text-white hover:bg-neutral-500 transition-colors"
                                title="{{ __('Editar texto alternativo') }}"
                            >
                                <flux:icon name="pencil" class="size-4" />
                            </button>
                            <button
                                type="button"
                                wire:click="deleteImage({{ $image->id }})"
                                wire:confirm="{{ __('Tem certeza que deseja remover esta imagem?') }}"
                                class="p-2 bg-red-600 rounded-lg text-white hover:bg-red-500 transition-colors"
                                title="{{ __('Remover') }}"
                            >
                                <flux:icon name="trash" class="size-4" />
                            </button>
                        </div>

                        {{-- Drag Handle --}}
                        <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity cursor-move drag-handle">
                            <div class="p-1 bg-neutral-900/80 rounded">
                                <flux:icon name="arrows-pointing-out" class="size-4 text-white" />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Edit Alt Text Modal --}}
        @if ($editingImageId)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                <div class="bg-neutral-900 rounded-lg p-6 w-full max-w-md mx-4 border border-neutral-800">
                    <flux:heading size="lg" class="mb-4">{{ __('Editar Texto Alternativo') }}</flux:heading>

                    <flux:input
                        wire:model="editingAltText"
                        type="text"
                        placeholder="{{ __('Descrição da imagem para acessibilidade') }}"
                        autofocus
                    />
                    @error('editingAltText')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror

                    <div class="mt-6 flex justify-end gap-3">
                        <flux:button variant="ghost" wire:click="cancelEditing">
                            {{ __('Cancelar') }}
                        </flux:button>
                        <flux:button variant="primary" wire:click="saveAltText">
                            {{ __('Salvar') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        @endif
    @else
        <div class="text-center py-8 text-neutral-500">
            <flux:icon name="photo" class="mx-auto size-12 mb-4 text-neutral-600" />
            <p>{{ __('Nenhuma imagem adicionada ainda.') }}</p>
            <p class="text-sm">{{ __('Adicione imagens para exibir o produto.') }}</p>
        </div>
    @endif

    @script
    <script>
        Alpine.data('imageGrid', () => ({
            sortableInstance: null,

            initSortable() {
                if (typeof Sortable === 'undefined') {
                    console.warn('SortableJS not loaded');
                    return;
                }

                this.$nextTick(() => {
                    const grid = this.$el;
                    if (!grid) return;

                    this.sortableInstance = new Sortable(grid, {
                        animation: 150,
                        handle: '.drag-handle',
                        ghostClass: 'sortable-ghost',
                        chosenClass: 'sortable-chosen',
                        dragClass: 'sortable-drag',
                        onEnd: (evt) => {
                            const items = Array.from(grid.querySelectorAll('.image-item'));
                            const orderedIds = items.map(item => parseInt(item.dataset.id));
                            $wire.reorderImages(orderedIds);
                        }
                    });
                });
            }
        }));
    </script>
    @endscript

    <style>
        .sortable-ghost {
            opacity: 0.4;
        }

        .sortable-chosen {
            box-shadow: 0 0 0 2px rgb(59 130 246);
        }

        .sortable-drag {
            opacity: 1 !important;
        }
    </style>
</div>
