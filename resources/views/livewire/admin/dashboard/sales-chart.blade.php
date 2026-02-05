<div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
        <flux:heading size="lg">{{ __('Grafico de Vendas') }}</flux:heading>
        <div class="flex gap-2">
            <flux:button
                size="sm"
                class="touch-target"
                :variant="$days === 7 ? 'primary' : 'ghost'"
                wire:click="setDays(7)"
            >
                7 dias
            </flux:button>
            <flux:button
                size="sm"
                class="touch-target"
                :variant="$days === 30 ? 'primary' : 'ghost'"
                wire:click="setDays(30)"
            >
                30 dias
            </flux:button>
        </div>
    </div>

    <div
        x-data="{
            chart: null,
            init() {
                this.renderChart();
                Livewire.on('chartUpdated', () => this.renderChart());
            },
            renderChart() {
                if (this.chart) {
                    this.chart.destroy();
                }

                const ctx = this.$refs.canvas.getContext('2d');
                this.chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: @js($this->chartData['labels']),
                        datasets: [
                            {
                                label: 'Periodo Atual',
                                data: @js(array_map(fn($v) => $v / 100, $this->chartData['current'])),
                                borderColor: 'rgb(59, 130, 246)',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                fill: true,
                                tension: 0.3,
                            },
                            {
                                label: 'Periodo Anterior',
                                data: @js(array_map(fn($v) => $v / 100, $this->chartData['previous'])),
                                borderColor: 'rgb(156, 163, 175)',
                                backgroundColor: 'transparent',
                                borderDash: [5, 5],
                                tension: 0.3,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': R$ ' + context.raw.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'R$ ' + value.toLocaleString('pt-BR');
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }"
        wire:key="sales-chart-{{ $days }}"
        class="chart-responsive h-64 min-h-[200px] md:h-72 lg:h-64"
    >
        <canvas x-ref="canvas"></canvas>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush
