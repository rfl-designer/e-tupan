<div>
    <flux:heading size="lg" class="mb-6">
        Endereco de Entrega
    </flux:heading>

    @if ($isAuthenticated && $this->savedAddresses->isNotEmpty() && !$showNewAddressForm)
        {{-- Saved Addresses List --}}
        <div class="space-y-4 mb-6">
            @foreach ($this->savedAddresses as $address)
                <div
                    wire:key="address-{{ $address->id }}"
                    wire:click="selectAddress('{{ $address->id }}')"
                    class="p-4 border rounded-lg cursor-pointer transition-colors
                        {{ $selectedAddressId === (string) $address->id
                            ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                            : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600' }}"
                >
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 mt-1">
                            @if ($selectedAddressId === (string) $address->id)
                                <flux:icon name="check-circle" class="size-5 text-primary-600" />
                            @else
                                <div class="size-5 rounded-full border-2 border-zinc-300 dark:border-zinc-600"></div>
                            @endif
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $address->recipient_name }}
                                </span>
                                @if ($address->is_default)
                                    <span class="px-2 py-0.5 text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300 rounded">
                                        Padrao
                                    </span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $address->street }}, {{ $address->number }}
                                @if ($address->complement)
                                    - {{ $address->complement }}
                                @endif
                            </p>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $address->neighborhood }} - {{ $address->city }}/{{ $address->state }}
                            </p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-500">
                                CEP: {{ $address->zipcode }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex items-center gap-4 mb-6">
            <flux:button variant="ghost" wire:click="addNewAddress">
                <flux:icon name="plus" class="size-4 mr-2" />
                Adicionar novo endereco
            </flux:button>
        </div>
    @endif

    @if ($showNewAddressForm || !$isAuthenticated || $this->savedAddresses->isEmpty())
        {{-- Address Form --}}
        <form wire:submit="continueToShipping" class="space-y-6">
            @if ($isAuthenticated && $this->savedAddresses->isNotEmpty())
                <div class="flex items-center justify-between pb-4 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="md">Novo endereco</flux:heading>
                    <flux:button variant="ghost" size="sm" wire:click="cancelNewAddress">
                        Cancelar
                    </flux:button>
                </div>
            @endif

            <div class="grid gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <flux:input
                        wire:model="form.shipping_recipient_name"
                        label="Nome do destinatario *"
                        type="text"
                        placeholder="Nome de quem vai receber"
                        :error="$errors->first('form.shipping_recipient_name')"
                    />
                </div>

                <div>
                    <flux:input
                        wire:model.live.debounce.500ms="form.shipping_zipcode"
                        label="CEP *"
                        type="text"
                        placeholder="00000-000"
                        maxlength="9"
                        :error="$errors->first('form.shipping_zipcode') ?: $cepError"
                    />
                    @if ($isLoadingCep)
                        <p class="mt-1 text-sm text-zinc-500">Buscando endereco...</p>
                    @endif
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        <a href="https://buscacepinter.correios.com.br/app/endereco/index.php" target="_blank" rel="noopener" class="text-primary-600 hover:underline">
                            Nao sei meu CEP
                        </a>
                    </p>
                </div>

                <div class="sm:col-span-2">
                    <flux:input
                        wire:model="form.shipping_street"
                        label="Endereco *"
                        type="text"
                        placeholder="Rua, Avenida, etc."
                        :error="$errors->first('form.shipping_street')"
                    />
                </div>

                <div>
                    <flux:input
                        wire:model="form.shipping_number"
                        label="Numero *"
                        type="text"
                        placeholder="123"
                        :error="$errors->first('form.shipping_number')"
                    />
                </div>

                <div>
                    <flux:input
                        wire:model="form.shipping_complement"
                        label="Complemento"
                        type="text"
                        placeholder="Apto, Bloco, etc."
                        :error="$errors->first('form.shipping_complement')"
                    />
                </div>

                <div>
                    <flux:input
                        wire:model="form.shipping_neighborhood"
                        label="Bairro *"
                        type="text"
                        placeholder="Bairro"
                        :error="$errors->first('form.shipping_neighborhood')"
                    />
                </div>

                <div>
                    <flux:input
                        wire:model="form.shipping_city"
                        label="Cidade *"
                        type="text"
                        placeholder="Cidade"
                        :error="$errors->first('form.shipping_city')"
                    />
                </div>

                <div>
                    <flux:select
                        wire:model="form.shipping_state"
                        label="Estado *"
                        :error="$errors->first('form.shipping_state')"
                    >
                        <option value="">Selecione</option>
                        <option value="AC">Acre</option>
                        <option value="AL">Alagoas</option>
                        <option value="AP">Amapa</option>
                        <option value="AM">Amazonas</option>
                        <option value="BA">Bahia</option>
                        <option value="CE">Ceara</option>
                        <option value="DF">Distrito Federal</option>
                        <option value="ES">Espirito Santo</option>
                        <option value="GO">Goias</option>
                        <option value="MA">Maranhao</option>
                        <option value="MT">Mato Grosso</option>
                        <option value="MS">Mato Grosso do Sul</option>
                        <option value="MG">Minas Gerais</option>
                        <option value="PA">Para</option>
                        <option value="PB">Paraiba</option>
                        <option value="PR">Parana</option>
                        <option value="PE">Pernambuco</option>
                        <option value="PI">Piaui</option>
                        <option value="RJ">Rio de Janeiro</option>
                        <option value="RN">Rio Grande do Norte</option>
                        <option value="RS">Rio Grande do Sul</option>
                        <option value="RO">Rondonia</option>
                        <option value="RR">Roraima</option>
                        <option value="SC">Santa Catarina</option>
                        <option value="SP">Sao Paulo</option>
                        <option value="SE">Sergipe</option>
                        <option value="TO">Tocantins</option>
                    </flux:select>
                </div>
            </div>

            @if ($isAuthenticated && $showNewAddressForm)
                <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:checkbox
                        wire:model="saveAddress"
                        label="Salvar este endereco para compras futuras"
                    />
                </div>
            @endif

            <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="continueToShipping">Continuar para entrega</span>
                        <span wire:loading wire:target="continueToShipping">Processando...</span>
                        <flux:icon name="arrow-right" class="size-4 ml-2" wire:loading.remove wire:target="continueToShipping" />
                    </flux:button>
                </div>
            </div>
        </form>
    @else
        {{-- Continue button for selected address --}}
        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
            <div class="flex justify-end">
                <flux:button wire:click="continueToShipping" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="continueToShipping">Continuar para entrega</span>
                    <span wire:loading wire:target="continueToShipping">Processando...</span>
                    <flux:icon name="arrow-right" class="size-4 ml-2" wire:loading.remove wire:target="continueToShipping" />
                </flux:button>
            </div>
        </div>
    @endif
</div>
