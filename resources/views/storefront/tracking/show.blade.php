<x-layouts.public title="Rastreamento {{ $code }}">
    <div class="max-w-3xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        {{-- Back Link --}}
        <div class="mb-6">
            <a href="{{ route('tracking.index') }}" class="text-neutral-400 hover:text-white transition-colors inline-flex items-center gap-2">
                <flux:icon name="arrow-left" class="size-4" />
                Nova consulta
            </a>
        </div>

        @if ($tracking)
            {{-- Tracking Header --}}
            <div class="bg-neutral-900 rounded-lg border border-neutral-800 p-6 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <flux:subheading>Codigo de Rastreamento</flux:subheading>
                        <code class="text-xl font-mono text-white">{{ $tracking['tracking_number'] }}</code>
                    </div>
                    <div class="text-right">
                        <flux:badge color="{{ $tracking['status_color'] }}" size="lg">
                            {{ $tracking['status_label'] }}
                        </flux:badge>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-neutral-800 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <flux:subheading>Transportadora</flux:subheading>
                        <p class="text-white">{{ $tracking['carrier'] }}</p>
                        <p class="text-sm text-neutral-500">{{ $tracking['service'] }}</p>
                    </div>
                    <div>
                        <flux:subheading>Destino</flux:subheading>
                        <p class="text-white">{{ $tracking['recipient_city'] }}/{{ $tracking['recipient_state'] }}</p>
                    </div>
                    <div>
                        <flux:subheading>Previsao de Entrega</flux:subheading>
                        @if ($tracking['delivered_at'])
                            <p class="text-green-400">Entregue em {{ $tracking['delivered_at'] }}</p>
                        @elseif ($tracking['estimated_delivery'])
                            <p class="text-white">{{ $tracking['estimated_delivery'] }}</p>
                        @else
                            <p class="text-neutral-500">Nao informada</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Timeline --}}
            <div class="bg-neutral-900 rounded-lg border border-neutral-800 p-6">
                <flux:heading size="lg" class="mb-4">Historico de Movimentacao</flux:heading>

                @if (count($tracking['events']) > 0)
                    <div class="space-y-0">
                        @foreach ($tracking['events'] as $index => $event)
                            <div class="relative pl-8 pb-6 {{ !$loop->last ? 'border-l-2 border-neutral-700 ml-3' : '' }}">
                                {{-- Timeline Dot --}}
                                <div class="absolute left-0 -translate-x-1/2 w-6 h-6 rounded-full flex items-center justify-center
                                    {{ $event['is_delivery'] ? 'bg-green-600' : ($event['is_problem'] ? 'bg-red-600' : 'bg-blue-600') }}">
                                    @if ($event['is_delivery'])
                                        <flux:icon name="check" class="size-3 text-white" />
                                    @elseif ($event['is_problem'])
                                        <flux:icon name="exclamation-triangle" class="size-3 text-white" />
                                    @else
                                        <flux:icon name="truck" class="size-3 text-white" />
                                    @endif
                                </div>

                                {{-- Event Content --}}
                                <div class="ml-4">
                                    <p class="text-white font-medium">{{ $event['description'] }}</p>
                                    <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1 text-sm text-neutral-500">
                                        <span>{{ $event['date'] }}</span>
                                        @if ($event['location'])
                                            <span>{{ $event['location'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-neutral-500">
                        <flux:icon name="clock" class="size-12 mx-auto mb-4 text-neutral-600" />
                        <p>Nenhuma movimentacao registrada ainda.</p>
                        <p class="text-sm mt-2">O rastreamento sera atualizado assim que o objeto for postado.</p>
                    </div>
                @endif
            </div>
        @else
            {{-- Not Found --}}
            <div class="bg-neutral-900 rounded-lg border border-neutral-800 p-12 text-center">
                <flux:icon name="magnifying-glass" class="size-16 mx-auto mb-4 text-neutral-600" />
                <flux:heading size="lg">Rastreamento nao encontrado</flux:heading>
                <flux:subheading class="mt-2">
                    Nao foi possivel encontrar informacoes para o codigo <code class="text-white">{{ $code }}</code>
                </flux:subheading>
                <div class="mt-6">
                    <a href="{{ route('tracking.index') }}">
                        <flux:button variant="primary">
                            Tentar novamente
                        </flux:button>
                    </a>
                </div>
            </div>
        @endif
    </div>
</x-layouts.public>
