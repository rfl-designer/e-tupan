<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.dashboard') }}" class="text-neutral-400 hover:text-white transition-colors">
                <flux:icon name="arrow-left" class="size-5" />
            </a>
            <div>
                <flux:heading size="xl">{{ __('Categorias') }}</flux:heading>
                <flux:subheading>{{ __('Gerencie as categorias de produtos') }}</flux:subheading>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" wire:click="expandAll">
                <flux:icon name="chevron-double-down" class="size-4 mr-1" />
                {{ __('Expandir') }}
            </flux:button>
            <flux:button variant="ghost" size="sm" wire:click="collapseAll">
                <flux:icon name="chevron-double-up" class="size-4 mr-1" />
                {{ __('Recolher') }}
            </flux:button>
            <a href="{{ route('admin.categories.create') }}">
                <flux:button variant="primary">
                    <flux:icon name="plus" class="size-4 mr-1" />
                    {{ __('Nova Categoria') }}
                </flux:button>
            </a>
        </div>
    </div>

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

    {{-- Categories Tree --}}
    <div
        x-data="categoryTree()"
        x-init="initSortable()"
        class="overflow-hidden rounded-lg border border-neutral-800 bg-neutral-900"
    >
        {{-- Table Header --}}
        <div class="border-b border-neutral-800 bg-neutral-900/50 px-6 py-4">
            <div class="grid grid-cols-12 gap-4 text-sm font-medium text-neutral-400">
                <div class="col-span-5">{{ __('Nome') }}</div>
                <div class="col-span-2">{{ __('Slug') }}</div>
                <div class="col-span-2">{{ __('Status') }}</div>
                <div class="col-span-1 text-center">{{ __('Produtos') }}</div>
                <div class="col-span-2 text-right">{{ __('Ações') }}</div>
            </div>
        </div>

        {{-- Categories List --}}
        <div class="category-list divide-y divide-neutral-800" data-parent-id="">
            @forelse ($categories as $category)
                @include('livewire.admin._category-item', ['category' => $category, 'level' => 0])
            @empty
                <div class="px-6 py-12 text-center text-neutral-400">
                    <flux:icon name="folder-open" class="mx-auto size-12 mb-4 text-neutral-600" />
                    <p class="text-lg font-medium">{{ __('Nenhuma categoria encontrada') }}</p>
                    <p class="mt-1 text-sm">{{ __('Comece criando sua primeira categoria.') }}</p>
                    <a href="{{ route('admin.categories.create') }}" class="mt-4 inline-block">
                        <flux:button variant="primary" size="sm">
                            <flux:icon name="plus" class="size-4 mr-1" />
                            {{ __('Criar Categoria') }}
                        </flux:button>
                    </a>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Back to Dashboard --}}
    <div class="mt-6">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-neutral-400 hover:text-white transition-colors">
            &larr; {{ __('Voltar ao Dashboard') }}
        </a>
    </div>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="p-6">
            <div class="flex items-center gap-4">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-red-900/50">
                    <flux:icon name="exclamation-triangle" class="size-6 text-red-400" />
                </div>
                <div>
                    <flux:heading size="lg">{{ __('Excluir Categoria') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('Tem certeza que deseja excluir esta categoria? Esta ação não pode ser desfeita.') }}</flux:text>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">
                    {{ __('Cancelar') }}
                </flux:button>
                <flux:button variant="danger" wire:click="confirmDelete">
                    {{ __('Excluir') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    @script
    <script>
        Alpine.data('categoryTree', () => ({
            sortableInstances: [],

            initSortable() {
                this.destroySortables();

                // Wait for DOM to be ready
                this.$nextTick(() => {
                    document.querySelectorAll('.category-list').forEach(el => {
                        const instance = new Sortable(el, {
                            group: 'categories',
                            animation: 150,
                            fallbackOnBody: true,
                            swapThreshold: 0.65,
                            handle: '.drag-handle',
                            ghostClass: 'sortable-ghost',
                            chosenClass: 'sortable-chosen',
                            dragClass: 'sortable-drag',
                            onEnd: (evt) => {
                                // Collect new order from root level
                                const rootList = document.querySelector('.category-list[data-parent-id=""]');
                                if (rootList) {
                                    const items = this.collectItems(rootList);
                                    $wire.reorder(items, null);
                                }
                            }
                        });
                        this.sortableInstances.push(instance);
                    });
                });
            },

            destroySortables() {
                this.sortableInstances.forEach(instance => {
                    if (instance && typeof instance.destroy === 'function') {
                        instance.destroy();
                    }
                });
                this.sortableInstances = [];
            },

            collectItems(container) {
                if (!container) return [];

                return Array.from(container.querySelectorAll(':scope > .category-item')).map(el => {
                    const childList = el.querySelector('.category-list');
                    return {
                        id: parseInt(el.dataset.id),
                        children: childList ? this.collectItems(childList) : []
                    };
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
