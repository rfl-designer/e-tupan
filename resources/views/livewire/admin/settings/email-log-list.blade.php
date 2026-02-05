<div class="space-y-6">
    {{-- Filters --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Buscar por destinatario ou assunto..."
            />
        </div>

        <div class="flex flex-wrap gap-4">
            <flux:select wire:model.live="filterStatus" placeholder="Todos os status">
                <flux:select.option value="">Todos os status</flux:select.option>
                @foreach($statuses as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="filterMailableClass" placeholder="Todos os tipos">
                <flux:select.option value="">Todos os tipos</flux:select.option>
                @foreach($mailableClasses as $class)
                    <flux:select.option value="{{ $class }}">{{ class_basename($class) }}</flux:select.option>
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

            @if($search || $filterStatus || $filterMailableClass || $filterDateFrom || $filterDateTo)
                <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark">
                    Limpar
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Data/Hora</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Destinatario</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Assunto</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Tipo</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Driver</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Status</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Acoes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse($logs as $log)
                    <tr wire:key="log-{{ $log->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <td class="whitespace-nowrap px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            <div>{{ $log->created_at->format('d/m/Y') }}</div>
                            <div class="text-xs text-zinc-400">{{ $log->created_at->format('H:i:s') }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-zinc-900 dark:text-white">{{ $log->recipient }}</span>
                        </td>
                        <td class="max-w-xs px-4 py-3">
                            <div class="truncate text-zinc-900 dark:text-white" title="{{ $log->subject }}">
                                {{ $log->subject }}
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-zinc-500">
                            {{ class_basename($log->mailable_class) }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-zinc-500">
                            {{ $log->driver ?? '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge size="sm" color="{{ $log->status->color() }}" icon="{{ $log->status->icon() }}">
                                {{ $log->status->label() }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1">
                                <flux:button
                                    wire:click="showDetails({{ $log->id }})"
                                    variant="ghost"
                                    size="sm"
                                    icon="eye"
                                >
                                    Ver
                                </flux:button>
                                @if($log->canBeResent())
                                    <flux:button
                                        wire:click="confirmResend({{ $log->id }})"
                                        variant="ghost"
                                        size="sm"
                                        icon="arrow-path"
                                    >
                                        Reenviar
                                    </flux:button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center">
                            <flux:icon name="envelope" class="mx-auto size-12 text-zinc-300 dark:text-zinc-600" />
                            <p class="mt-4 text-zinc-500">Nenhum log de email encontrado</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($logs->hasPages())
        <div class="flex justify-center">
            {{ $logs->links() }}
        </div>
    @endif

    {{-- Details Modal --}}
    <flux:modal wire:model="showDetailsModal" class="max-w-xl">
        @if($selectedLog)
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Detalhes do Email</flux:heading>
                    <flux:subheading>Informacoes completas do log de email</flux:subheading>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:text class="text-sm font-medium text-zinc-500">Destinatario</flux:text>
                            <flux:text class="text-zinc-900 dark:text-white">{{ $selectedLog->recipient }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-sm font-medium text-zinc-500">Status</flux:text>
                            <div class="mt-1">
                                <flux:badge size="sm" color="{{ $selectedLog->status->color() }}" icon="{{ $selectedLog->status->icon() }}">
                                    {{ $selectedLog->status->label() }}
                                </flux:badge>
                            </div>
                        </div>
                    </div>

                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500">Assunto</flux:text>
                        <flux:text class="text-zinc-900 dark:text-white">{{ $selectedLog->subject }}</flux:text>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:text class="text-sm font-medium text-zinc-500">Tipo de Email</flux:text>
                            <flux:text class="text-zinc-900 dark:text-white">{{ class_basename($selectedLog->mailable_class) }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-sm font-medium text-zinc-500">Driver</flux:text>
                            <flux:text class="text-zinc-900 dark:text-white">{{ $selectedLog->driver ?? '-' }}</flux:text>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:text class="text-sm font-medium text-zinc-500">Data/Hora</flux:text>
                            <flux:text class="text-zinc-900 dark:text-white">{{ $selectedLog->created_at->format('d/m/Y H:i:s') }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-sm font-medium text-zinc-500">Classe Completa</flux:text>
                            <flux:text class="break-all text-xs text-zinc-600 dark:text-zinc-400">{{ $selectedLog->mailable_class }}</flux:text>
                        </div>
                    </div>

                    @if($selectedLog->error_message)
                        <div>
                            <flux:text class="text-sm font-medium text-zinc-500">Mensagem de Erro</flux:text>
                            <div class="mt-1 rounded-lg bg-red-50 p-3 dark:bg-red-900/20">
                                <flux:text class="text-sm text-red-700 dark:text-red-400">{{ $selectedLog->error_message }}</flux:text>
                            </div>
                        </div>
                    @endif

                    @if($selectedLog->resentFrom)
                        <div>
                            <flux:text class="text-sm font-medium text-zinc-500">Reenviado de</flux:text>
                            <flux:text class="text-zinc-900 dark:text-white">Log #{{ $selectedLog->resent_from_id }} - {{ $selectedLog->resentFrom->created_at->format('d/m/Y H:i') }}</flux:text>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end">
                    <flux:button wire:click="closeDetails" variant="ghost">
                        Fechar
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>

    {{-- Resend Confirmation Modal --}}
    <flux:modal wire:model="showResendModal" class="max-w-md">
        @if($logToResend)
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Reenviar Email</flux:heading>
                    <flux:subheading>Confirme o reenvio do email abaixo</flux:subheading>
                </div>

                <div class="space-y-3 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500">Destinatario</flux:text>
                        <flux:text class="text-zinc-900 dark:text-white">{{ $logToResend->recipient }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500">Assunto</flux:text>
                        <flux:text class="text-zinc-900 dark:text-white">{{ $logToResend->subject }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500">Data original</flux:text>
                        <flux:text class="text-zinc-900 dark:text-white">{{ $logToResend->created_at->format('d/m/Y H:i') }}</flux:text>
                    </div>
                </div>

                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    O email sera adicionado a fila de envio. Um novo registro de log sera criado para esta tentativa.
                </flux:text>

                <div class="flex justify-end gap-2">
                    <flux:button wire:click="cancelResend" variant="ghost">
                        Cancelar
                    </flux:button>
                    <flux:button wire:click="resend" variant="primary" icon="arrow-path">
                        Confirmar Reenvio
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
