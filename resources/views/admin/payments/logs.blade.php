<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="min-h-svh p-6 md:p-10">
            <div class="mx-auto max-w-7xl">
                {{-- Header --}}
                <div class="mb-8 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('admin.dashboard') }}" class="text-neutral-400 hover:text-white transition-colors">
                            <flux:icon name="arrow-left" class="size-5" />
                        </a>
                        <div>
                            <flux:heading size="xl">{{ __('Logs de Pagamento') }}</flux:heading>
                            <flux:subheading>{{ __('Auditoria e monitoramento de transacoes') }}</flux:subheading>
                        </div>
                    </div>
                </div>

                {{-- Statistics Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-4">
                        <div class="text-sm text-neutral-400">{{ __('Total (30 dias)') }}</div>
                        <div class="text-2xl font-bold text-white">{{ number_format($statistics['total']) }}</div>
                    </div>
                    <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-4">
                        <div class="text-sm text-neutral-400">{{ __('Sucesso') }}</div>
                        <div class="text-2xl font-bold text-green-400">{{ number_format($statistics['successful']) }}</div>
                    </div>
                    <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-4">
                        <div class="text-sm text-neutral-400">{{ __('Falhas') }}</div>
                        <div class="text-2xl font-bold text-red-400">{{ number_format($statistics['failed']) }}</div>
                    </div>
                    <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-4">
                        <div class="text-sm text-neutral-400">{{ __('Taxa de Sucesso') }}</div>
                        <div class="text-2xl font-bold text-white">{{ $statistics['success_rate'] }}%</div>
                    </div>
                </div>

                {{-- Filters --}}
                <div class="mb-6 rounded-lg border border-neutral-800 bg-neutral-900 p-4">
                    <form method="GET" action="{{ route('admin.payments.logs') }}" class="flex flex-wrap items-end gap-4">
                        {{-- Search --}}
                        <div class="flex-1 min-w-[200px]">
                            <flux:input
                                name="search"
                                :value="$filters['search'] ?? ''"
                                placeholder="Buscar por ID de transacao, pedido..."
                            />
                        </div>

                        {{-- Gateway Filter --}}
                        <div class="w-40">
                            <flux:select name="gateway">
                                <option value="">{{ __('Todos os gateways') }}</option>
                                @foreach ($gateways as $gateway)
                                    <option value="{{ $gateway }}" @selected(($filters['gateway'] ?? '') === $gateway)>
                                        {{ ucfirst($gateway) }}
                                    </option>
                                @endforeach
                            </flux:select>
                        </div>

                        {{-- Action Filter --}}
                        <div class="w-44">
                            <flux:select name="action">
                                <option value="">{{ __('Todas as acoes') }}</option>
                                @foreach ($actions as $action)
                                    <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>
                                        {{ str_replace('_', ' ', ucfirst($action)) }}
                                    </option>
                                @endforeach
                            </flux:select>
                        </div>

                        {{-- Status Filter --}}
                        <div class="w-36">
                            <flux:select name="status">
                                <option value="">{{ __('Todos os status') }}</option>
                                <option value="success" @selected(($filters['status'] ?? '') === 'success')>{{ __('Sucesso') }}</option>
                                <option value="failed" @selected(($filters['status'] ?? '') === 'failed')>{{ __('Falha') }}</option>
                                <option value="error" @selected(($filters['status'] ?? '') === 'error')>{{ __('Erro') }}</option>
                            </flux:select>
                        </div>

                        {{-- Date From --}}
                        <div class="w-40">
                            <flux:input
                                type="date"
                                name="date_from"
                                :value="$filters['date_from'] ?? ''"
                                placeholder="Data inicial"
                            />
                        </div>

                        {{-- Date To --}}
                        <div class="w-40">
                            <flux:input
                                type="date"
                                name="date_to"
                                :value="$filters['date_to'] ?? ''"
                                placeholder="Data final"
                            />
                        </div>

                        <flux:button type="submit" variant="filled">
                            <flux:icon name="magnifying-glass" class="size-4" />
                        </flux:button>

                        @if (!empty(array_filter($filters)))
                            <a href="{{ route('admin.payments.logs') }}">
                                <flux:button variant="ghost">
                                    {{ __('Limpar') }}
                                </flux:button>
                            </a>
                        @endif
                    </form>
                </div>

                {{-- Logs Table --}}
                <div class="overflow-hidden rounded-lg border border-neutral-800 bg-neutral-900">
                    <table class="w-full">
                        <thead class="border-b border-neutral-800 bg-neutral-900/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-medium text-neutral-400">{{ __('Data/Hora') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-neutral-400">{{ __('Gateway') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-neutral-400">{{ __('Acao') }}</th>
                                <th class="px-4 py-3 text-center text-sm font-medium text-neutral-400">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-neutral-400">{{ __('Pedido') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-neutral-400">{{ __('Transacao') }}</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-neutral-400">{{ __('Tempo') }}</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-neutral-400">{{ __('Detalhes') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-800">
                            @forelse ($logs as $log)
                                <tr wire:key="log-{{ $log->id }}" class="hover:bg-neutral-800/50 transition-colors">
                                    <td class="px-4 py-3 text-sm text-neutral-300">
                                        {{ $log->created_at->format('d/m/Y H:i:s') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-sm text-neutral-300">{{ ucfirst($log->gateway) }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <code class="rounded bg-neutral-800 px-2 py-1 text-xs font-medium text-neutral-300">
                                            {{ str_replace('_', ' ', $log->action) }}
                                        </code>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($log->status === 'success')
                                            <flux:badge color="green">{{ __('Sucesso') }}</flux:badge>
                                        @elseif ($log->status === 'failed')
                                            <flux:badge color="red">{{ __('Falha') }}</flux:badge>
                                        @else
                                            <flux:badge color="yellow">{{ __('Erro') }}</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($log->order_id)
                                            <code class="text-xs text-neutral-400">{{ Str::limit($log->order_id, 8) }}</code>
                                        @else
                                            <span class="text-neutral-500">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($log->transaction_id)
                                            <code class="text-xs text-neutral-400">{{ Str::limit($log->transaction_id, 12) }}</code>
                                        @else
                                            <span class="text-neutral-500">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm text-neutral-400">
                                        @if ($log->response_time_ms)
                                            {{ $log->response_time_ms }}ms
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <flux:modal.trigger name="log-details-{{ $log->id }}">
                                            <flux:button variant="ghost" size="sm">
                                                <flux:icon name="eye" class="size-4" />
                                            </flux:button>
                                        </flux:modal.trigger>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-neutral-400">
                                        <flux:icon name="document-magnifying-glass" class="mx-auto size-12 mb-4 text-neutral-600" />
                                        <p class="text-lg font-medium">{{ __('Nenhum log encontrado') }}</p>
                                        <p class="mt-1 text-sm">{{ __('Os logs de pagamento aparecerao aqui quando houver transacoes.') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($logs->hasPages())
                    <div class="mt-6">
                        {{ $logs->appends($filters)->links() }}
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

        {{-- Log Details Modals --}}
        @foreach ($logs as $log)
            <flux:modal name="log-details-{{ $log->id }}" class="max-w-2xl">
                <div class="space-y-4">
                    <flux:heading size="lg">{{ __('Detalhes do Log') }}</flux:heading>

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-neutral-400">{{ __('Data/Hora:') }}</span>
                            <span class="text-white ml-2">{{ $log->created_at->format('d/m/Y H:i:s') }}</span>
                        </div>
                        <div>
                            <span class="text-neutral-400">{{ __('Gateway:') }}</span>
                            <span class="text-white ml-2">{{ ucfirst($log->gateway) }}</span>
                        </div>
                        <div>
                            <span class="text-neutral-400">{{ __('Acao:') }}</span>
                            <span class="text-white ml-2">{{ str_replace('_', ' ', $log->action) }}</span>
                        </div>
                        <div>
                            <span class="text-neutral-400">{{ __('Status:') }}</span>
                            @if ($log->status === 'success')
                                <flux:badge color="green" class="ml-2">{{ __('Sucesso') }}</flux:badge>
                            @elseif ($log->status === 'failed')
                                <flux:badge color="red" class="ml-2">{{ __('Falha') }}</flux:badge>
                            @else
                                <flux:badge color="yellow" class="ml-2">{{ __('Erro') }}</flux:badge>
                            @endif
                        </div>
                        @if ($log->order_id)
                            <div>
                                <span class="text-neutral-400">{{ __('Pedido:') }}</span>
                                <code class="text-white ml-2">{{ $log->order_id }}</code>
                            </div>
                        @endif
                        @if ($log->transaction_id)
                            <div>
                                <span class="text-neutral-400">{{ __('Transacao:') }}</span>
                                <code class="text-white ml-2">{{ $log->transaction_id }}</code>
                            </div>
                        @endif
                        @if ($log->response_time_ms)
                            <div>
                                <span class="text-neutral-400">{{ __('Tempo de Resposta:') }}</span>
                                <span class="text-white ml-2">{{ $log->response_time_ms }}ms</span>
                            </div>
                        @endif
                        @if ($log->ip_address)
                            <div>
                                <span class="text-neutral-400">{{ __('IP:') }}</span>
                                <span class="text-white ml-2">{{ $log->ip_address }}</span>
                            </div>
                        @endif
                    </div>

                    @if ($log->error_message)
                        <div class="mt-4">
                            <span class="text-neutral-400 text-sm">{{ __('Mensagem de Erro:') }}</span>
                            <div class="mt-1 rounded bg-red-900/20 border border-red-800 p-3 text-sm text-red-300">
                                {{ $log->error_message }}
                            </div>
                        </div>
                    @endif

                    @if ($log->request_data)
                        <div class="mt-4">
                            <span class="text-neutral-400 text-sm">{{ __('Dados da Requisicao:') }}</span>
                            <pre class="mt-1 rounded bg-neutral-800 p-3 text-xs text-neutral-300 overflow-x-auto">{{ json_encode($log->request_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    @endif

                    @if ($log->response_data)
                        <div class="mt-4">
                            <span class="text-neutral-400 text-sm">{{ __('Dados da Resposta:') }}</span>
                            <pre class="mt-1 rounded bg-neutral-800 p-3 text-xs text-neutral-300 overflow-x-auto">{{ json_encode($log->response_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    @endif

                    @if ($log->user_agent)
                        <div class="mt-4">
                            <span class="text-neutral-400 text-sm">{{ __('User Agent:') }}</span>
                            <p class="mt-1 text-xs text-neutral-500 break-all">{{ $log->user_agent }}</p>
                        </div>
                    @endif
                </div>
            </flux:modal>
        @endforeach

        @fluxScripts
    </body>
</html>
