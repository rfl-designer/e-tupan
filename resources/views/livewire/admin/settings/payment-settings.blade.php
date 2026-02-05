<div>
    <form wire:submit="save" class="space-y-6">
        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20">
            <div class="flex gap-3">
                <flux:icon name="exclamation-triangle" class="size-5 text-yellow-600 dark:text-yellow-400" />
                <div class="text-sm text-yellow-800 dark:text-yellow-200">
                    <p class="font-medium">Credenciais de pagamento</p>
                    <p class="mt-1">
                        As credenciais de pagamento (API Keys, tokens) sao configuradas no arquivo <code class="rounded bg-yellow-100 px-1 dark:bg-yellow-800">.env</code>.
                        Por seguranca, elas nao sao exibidas nem editaveis por esta interface.
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <flux:select
                wire:model="active_gateway"
                label="Gateway de pagamento ativo"
            >
                @foreach($this->getGatewayOptions() as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select
                wire:model="payment_environment"
                label="Ambiente"
            >
                @foreach($this->getEnvironmentOptions() as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        @if($payment_environment === 'production')
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                <div class="flex gap-3">
                    <flux:icon name="exclamation-circle" class="size-5 text-red-600 dark:text-red-400" />
                    <div class="text-sm text-red-800 dark:text-red-200">
                        <p class="font-medium">Ambiente de producao</p>
                        <p class="mt-1">
                            Voce esta usando o ambiente de producao. Todas as transacoes serao reais.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Salvar Alteracoes</span>
                <span wire:loading wire:target="save">Salvando...</span>
            </flux:button>
        </div>
    </form>
</div>
