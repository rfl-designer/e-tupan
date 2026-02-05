<div>
    <form wire:submit="save" class="space-y-6">
        <div class="space-y-4">
            <flux:field variant="inline">
                <flux:label>Permitir checkout como visitante</flux:label>
                <flux:switch wire:model.live="guest_checkout_enabled" />
                <flux:description>
                    Clientes podem finalizar compras sem criar conta
                </flux:description>
            </flux:field>

            <flux:separator />

            <flux:field variant="inline">
                <flux:label>CPF obrigatorio</flux:label>
                <flux:switch wire:model.live="cpf_required" />
                <flux:description>
                    Exigir CPF no checkout
                </flux:description>
            </flux:field>

            <flux:field variant="inline">
                <flux:label>Telefone obrigatorio</flux:label>
                <flux:switch wire:model.live="phone_required" />
                <flux:description>
                    Exigir telefone no checkout
                </flux:description>
            </flux:field>

            <flux:separator />

            <div class="max-w-xs">
                <flux:input
                    wire:model="stock_reservation_minutes"
                    type="number"
                    label="Tempo de reserva de estoque (minutos)"
                    min="5"
                    max="120"
                    description="Por quanto tempo o estoque fica reservado durante o checkout"
                />
            </div>

            <flux:textarea
                wire:model="checkout_message"
                label="Mensagem personalizada no checkout"
                placeholder="Mensagem opcional exibida na pagina de checkout"
                rows="3"
            />
        </div>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Salvar Alteracoes</span>
                <span wire:loading wire:target="save">Salvando...</span>
            </flux:button>
        </div>
    </form>
</div>
