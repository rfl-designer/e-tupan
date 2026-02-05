<div>
    <form wire:submit="save" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <flux:input
                wire:model="low_stock_threshold"
                type="number"
                label="Limite de estoque baixo (padrao)"
                min="1"
                max="1000"
                description="Quantidade abaixo da qual um produto e considerado com estoque baixo"
            />

            <flux:input
                wire:model="stock_alert_email"
                type="email"
                label="Email para alertas de estoque"
                placeholder="estoque@minhaloja.com"
                description="Deixe em branco para usar o email principal"
            />
        </div>

        <flux:field variant="inline">
            <flux:label>Permitir backorders</flux:label>
            <flux:switch wire:model.live="allow_backorders" />
            <flux:description>
                Permitir venda de produtos sem estoque disponivel
            </flux:description>
        </flux:field>

        <flux:select
            wire:model="stock_alert_frequency"
            label="Frequencia de alertas"
        >
            <flux:select.option value="realtime">Tempo real</flux:select.option>
            <flux:select.option value="daily">Diario</flux:select.option>
            <flux:select.option value="weekly">Semanal</flux:select.option>
        </flux:select>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Salvar Alteracoes</span>
                <span wire:loading wire:target="save">Salvando...</span>
            </flux:button>
        </div>
    </form>
</div>
