<div class="space-y-6">
    {{-- Filters --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Buscar por descricao, entidade ou admin..."
            />
        </div>

        <div class="flex flex-wrap gap-4">
            <flux:select wire:model.live="filterAdmin" placeholder="Todos os admins">
                <flux:select.option value="">Todos os admins</flux:select.option>
                @foreach($this->admins as $id => $name)
                    <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="filterAction" placeholder="Todas as acoes">
                <flux:select.option value="">Todas as acoes</flux:select.option>
                @foreach($this->actions as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input
                wire:model.live="filterDateFrom"
                type="date"
                label=""
                placeholder="Data inicial"
            />

            <flux:input
                wire:model.live="filterDateTo"
                type="date"
                label=""
                placeholder="Data final"
            />

            @if($search || $filterAdmin || $filterAction || $filterDateFrom || $filterDateTo)
                <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark">
                    Limpar
                </flux:button>
            @endif
        </div>

        <flux:button wire:click="export" icon="arrow-down-tray" variant="ghost">
            Exportar CSV
        </flux:button>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Data/Hora</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Admin</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Acao</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Descricao</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse($this->logs as $log)
                    <tr wire:key="log-{{ $log->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <td class="whitespace-nowrap px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            <div>{{ $log->created_at->format('d/m/Y') }}</div>
                            <div class="text-xs text-zinc-400">{{ $log->created_at->format('H:i:s') }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium dark:bg-zinc-700">
                                    {{ $log->admin ? substr($log->admin->name, 0, 2) : 'SY' }}
                                </div>
                                <span class="text-zinc-900 dark:text-white">
                                    {{ $log->admin_name ?? 'Sistema' }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge size="sm" color="{{ $log->action->color() }}">
                                {{ $log->action->label() }}
                            </flux:badge>
                        </td>
                        <td class="max-w-md px-4 py-3">
                            <div class="truncate text-zinc-900 dark:text-white" title="{{ $log->description }}">
                                {{ $log->description }}
                            </div>
                            <div class="text-xs text-zinc-500">
                                {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-zinc-500">
                            {{ $log->ip_address ?? '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center">
                            <flux:icon name="clipboard-document-list" class="mx-auto size-12 text-zinc-300 dark:text-zinc-600" />
                            <p class="mt-4 text-zinc-500">Nenhum log encontrado</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($this->logs->hasPages())
        <div class="flex justify-center">
            {{ $this->logs->links() }}
        </div>
    @endif
</div>
