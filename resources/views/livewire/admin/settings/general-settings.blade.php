<div>
    <form wire:submit="save" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <flux:input
                wire:model="store_name"
                label="Nome da Loja"
                placeholder="Minha Loja"
                required
            />

            <flux:input
                wire:model="store_email"
                type="email"
                label="Email de Contato"
                placeholder="contato@minhaloja.com"
                required
            />

            <flux:input
                wire:model="store_phone"
                label="Telefone"
                placeholder="(11) 99999-9999"
            />

            <flux:input
                wire:model="store_cnpj"
                label="CNPJ"
                placeholder="00.000.000/0000-00"
            />
        </div>

        <flux:textarea
            wire:model="store_address"
            label="Endereco da Empresa"
            placeholder="Rua, numero, bairro, cidade - UF"
            rows="2"
        />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="space-y-2">
                <flux:label>Logo da Loja</flux:label>
                <div class="flex items-center gap-4">
                    @if($currentLogo)
                        <div class="relative">
                            <img
                                src="{{ Storage::url($currentLogo) }}"
                                alt="Logo atual"
                                class="h-16 w-auto rounded border border-zinc-200 dark:border-zinc-700"
                            />
                            <button
                                type="button"
                                wire:click="deleteLogo"
                                wire:confirm="Tem certeza que deseja remover o logo?"
                                class="absolute -right-2 -top-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600"
                            >
                                <flux:icon name="x-mark" class="size-3" />
                            </button>
                        </div>
                    @endif
                    <flux:input
                        wire:model="logo"
                        type="file"
                        accept="image/*"
                    />
                </div>
                @error('logo') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            <div class="space-y-2">
                <flux:label>Favicon</flux:label>
                <div class="flex items-center gap-4">
                    @if($currentFavicon)
                        <div class="relative">
                            <img
                                src="{{ Storage::url($currentFavicon) }}"
                                alt="Favicon atual"
                                class="h-8 w-auto rounded border border-zinc-200 dark:border-zinc-700"
                            />
                            <button
                                type="button"
                                wire:click="deleteFavicon"
                                wire:confirm="Tem certeza que deseja remover o favicon?"
                                class="absolute -right-2 -top-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600"
                            >
                                <flux:icon name="x-mark" class="size-3" />
                            </button>
                        </div>
                    @endif
                    <flux:input
                        wire:model="favicon"
                        type="file"
                        accept="image/*"
                    />
                </div>
                @error('favicon') <flux:error>{{ $message }}</flux:error> @enderror
            </div>
        </div>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Salvar Alteracoes</span>
                <span wire:loading wire:target="save">Salvando...</span>
            </flux:button>
        </div>
    </form>
</div>
