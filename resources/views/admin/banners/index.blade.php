<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        {{-- SortableJS --}}
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="min-h-svh p-6 md:p-10">
            <div class="mx-auto max-w-6xl">
                {{-- Header --}}
                <div class="mb-8 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('admin.dashboard') }}" class="text-neutral-400 hover:text-white transition-colors">
                            <flux:icon name="arrow-left" class="size-5" />
                        </a>
                        <div>
                            <flux:heading size="xl">{{ __('Banners Promocionais') }}</flux:heading>
                            <flux:subheading>{{ __('Gerencie os banners da homepage') }}</flux:subheading>
                        </div>
                    </div>
                    <a href="{{ route('admin.banners.create') }}">
                        <flux:button variant="primary">
                            <flux:icon name="plus" class="size-4 mr-1" />
                            {{ __('Novo Banner') }}
                        </flux:button>
                    </a>
                </div>

                {{-- Success Message --}}
                @if (session('success'))
                    <flux:callout variant="success" class="mb-6">
                        {{ session('success') }}
                    </flux:callout>
                @endif

                {{-- Error Messages --}}
                @if ($errors->any())
                    <flux:callout variant="danger" class="mb-6">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </flux:callout>
                @endif

                {{-- Filters --}}
                <div class="mb-6 rounded-lg border border-neutral-800 bg-neutral-900 p-4">
                    <form method="GET" action="{{ route('admin.banners.index') }}" class="flex flex-wrap items-end gap-4">
                        {{-- Search --}}
                        <div class="flex-1 min-w-[200px]">
                            <flux:input
                                name="search"
                                :value="request('search')"
                                placeholder="Buscar por titulo..."
                            />
                        </div>

                        {{-- Status Filter --}}
                        <div class="w-48">
                            <flux:select name="status">
                                <option value="">{{ __('Todos os status') }}</option>
                                <option value="active" @selected(request('status') === 'active')>{{ __('Ativos') }}</option>
                                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Inativos') }}</option>
                                <option value="scheduled" @selected(request('status') === 'scheduled')>{{ __('Agendados') }}</option>
                                <option value="expired" @selected(request('status') === 'expired')>{{ __('Expirados') }}</option>
                            </flux:select>
                        </div>

                        <flux:button type="submit" variant="filled">
                            <flux:icon name="magnifying-glass" class="size-4" />
                        </flux:button>

                        @if (request()->hasAny(['search', 'status']))
                            <a href="{{ route('admin.banners.index') }}">
                                <flux:button variant="ghost">
                                    {{ __('Limpar') }}
                                </flux:button>
                            </a>
                        @endif
                    </form>
                </div>

                {{-- Reorder Form --}}
                <form id="banner-order-form" method="POST" action="{{ route('admin.banners.reorder') }}" class="hidden">
                    @csrf
                    @method('PATCH')
                    <div id="banner-order-fields"></div>
                </form>

                {{-- Banners Table --}}
                <div class="overflow-hidden rounded-lg border border-neutral-800 bg-neutral-900">
                    <table class="w-full">
                        <thead class="border-b border-neutral-800 bg-neutral-900/50">
                            <tr>
                                <th class="px-4 py-4 text-left text-sm font-medium text-neutral-400">{{ __('Ordem') }}</th>
                                <th class="px-6 py-4 text-left text-sm font-medium text-neutral-400">{{ __('Imagem') }}</th>
                                <th class="px-6 py-4 text-left text-sm font-medium text-neutral-400">{{ __('Titulo') }}</th>
                                <th class="px-6 py-4 text-left text-sm font-medium text-neutral-400">{{ __('Periodo') }}</th>
                                <th class="px-6 py-4 text-center text-sm font-medium text-neutral-400">{{ __('Posicao') }}</th>
                                <th class="px-6 py-4 text-center text-sm font-medium text-neutral-400">{{ __('Status') }}</th>
                                <th class="px-6 py-4 text-right text-sm font-medium text-neutral-400">{{ __('Acoes') }}</th>
                            </tr>
                        </thead>
                        <tbody id="banner-sortable" class="divide-y divide-neutral-800">
                            @forelse ($banners as $banner)
                                <tr data-id="{{ $banner->id }}" class="banner-row hover:bg-neutral-800/50 transition-colors">
                                    <td class="px-4 py-4">
                                        <div class="drag-handle cursor-grab text-neutral-500 hover:text-neutral-300 active:cursor-grabbing">
                                            <flux:icon name="bars-3" class="size-4" />
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <img
                                            src="{{ Storage::disk('banners')->url(str_replace('banners/', '', str_replace('/large/', '/medium/', $banner->image_desktop))) }}"
                                            alt="{{ $banner->alt_text ?? $banner->title }}"
                                            class="h-16 w-32 object-cover rounded border border-neutral-700"
                                        >
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-neutral-300">{{ $banner->title }}</span>
                                        @if ($banner->link)
                                            <p class="text-xs text-neutral-500 mt-1 truncate max-w-xs">{{ $banner->link }}</p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm">
                                            @if ($banner->starts_at || $banner->ends_at)
                                                <div class="text-neutral-300">
                                                    @if ($banner->starts_at)
                                                        <span class="text-neutral-500">{{ __('De:') }}</span> {{ $banner->starts_at->format('d/m/Y H:i') }}
                                                    @else
                                                        <span class="text-neutral-500">{{ __('De:') }}</span> {{ __('Imediato') }}
                                                    @endif
                                                </div>
                                                <div class="text-neutral-300 mt-0.5">
                                                    @if ($banner->ends_at)
                                                        <span class="text-neutral-500">{{ __('Ate:') }}</span> {{ $banner->ends_at->format('d/m/Y H:i') }}
                                                    @else
                                                        <span class="text-neutral-500">{{ __('Ate:') }}</span> {{ __('Indefinido') }}
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-neutral-500">{{ __('Sem restricao') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-sm text-neutral-300">{{ $banner->position }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if (!$banner->is_active)
                                            <flux:badge color="zinc">{{ __('Inativo') }}</flux:badge>
                                        @else
                                            <flux:badge :color="$banner->getPeriodStatusColor()">{{ __($banner->getPeriodStatusLabel()) }}</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            {{-- Toggle Active --}}
                                            <form method="POST" action="{{ route('admin.banners.toggle-active', $banner) }}">
                                                @csrf
                                                @method('PATCH')
                                                <flux:button variant="ghost" size="sm" type="submit" title="{{ $banner->is_active ? 'Desativar' : 'Ativar' }}">
                                                    @if ($banner->is_active)
                                                        <flux:icon name="pause" class="size-4 text-yellow-400" />
                                                    @else
                                                        <flux:icon name="play" class="size-4 text-green-400" />
                                                    @endif
                                                </flux:button>
                                            </form>

                                            {{-- Edit --}}
                                            <a href="{{ route('admin.banners.edit', $banner) }}">
                                                <flux:button variant="ghost" size="sm">
                                                    <flux:icon name="pencil" class="size-4" />
                                                </flux:button>
                                            </a>

                                            {{-- Duplicate --}}
                                            <form method="POST" action="{{ route('admin.banners.duplicate', $banner) }}">
                                                @csrf
                                                <flux:button variant="ghost" size="sm" type="submit" title="{{ __('Duplicar') }}">
                                                    <flux:icon name="document-duplicate" class="size-4" />
                                                </flux:button>
                                            </form>

                                            {{-- Delete --}}
                                            <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" onsubmit="return confirm('Tem certeza que deseja excluir este banner? Esta acao nao pode ser desfeita.')">
                                                @csrf
                                                @method('DELETE')
                                                <flux:button variant="ghost" size="sm" type="submit">
                                                    <flux:icon name="trash" class="size-4 text-red-400" />
                                                </flux:button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-neutral-400">
                                        <flux:icon name="photo" class="mx-auto size-12 mb-4 text-neutral-600" />
                                        <p class="text-lg font-medium">{{ __('Nenhum banner encontrado') }}</p>
                                        <p class="mt-1 text-sm">{{ __('Comece criando seu primeiro banner promocional.') }}</p>
                                        <a href="{{ route('admin.banners.create') }}" class="mt-4 inline-block">
                                            <flux:button variant="primary" size="sm">
                                                <flux:icon name="plus" class="size-4 mr-1" />
                                                {{ __('Criar Banner') }}
                                            </flux:button>
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($banners->hasPages())
                    <div class="mt-6">
                        {{ $banners->links() }}
                    </div>
                @endif

                {{-- Back to Dashboard --}}
                <div class="mt-6">
                    <a href="{{ route('admin.dashboard') }}" class="text-sm text-neutral-400 hover:text-white transition-colors">
                        &larr; {{ __('Voltar ao Dashboard') }}
                    </a>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const tableBody = document.getElementById('banner-sortable');
                const form = document.getElementById('banner-order-form');
                const fields = document.getElementById('banner-order-fields');

                if (!tableBody || !form || !fields || tableBody.children.length === 0) {
                    return;
                }

                const updateFields = () => {
                    fields.innerHTML = '';
                    const ids = Array.from(tableBody.querySelectorAll('.banner-row')).map((row) => row.dataset.id);

                    ids.forEach((id) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'order[]';
                        input.value = id;
                        fields.appendChild(input);
                    });
                };

                updateFields();

                new Sortable(tableBody, {
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    onEnd: () => {
                        updateFields();
                        form.submit();
                    },
                });
            });
        </script>

        <style>
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
        @fluxScripts
    </body>
</html>
