<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">{{ __('Meus Enderecos') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Gerencie seus enderecos de entrega') }}</flux:text>
        </div>

        @if($addresses->count() < \App\Domain\Customer\Livewire\AddressManager::MAX_ADDRESSES)
            <flux:button wire:click="create" variant="primary" icon="plus">
                {{ __('Adicionar Endereco') }}
            </flux:button>
        @else
            <flux:text class="text-sm text-zinc-500">
                {{ __('Limite de :max enderecos atingido', ['max' => \App\Domain\Customer\Livewire\AddressManager::MAX_ADDRESSES]) }}
            </flux:text>
        @endif
    </div>

    {{-- Addresses List --}}
    <div class="space-y-4">
        @forelse($addresses as $address)
            <div wire:key="address-{{ $address->id }}" class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    {{-- Address Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2">
                            @if($address->label)
                                <flux:heading size="sm">{{ $address->label }}</flux:heading>
                            @else
                                <flux:heading size="sm">{{ __('Endereco') }}</flux:heading>
                            @endif

                            @if($address->is_default)
                                <flux:badge color="lime" size="sm">{{ __('Padrao') }}</flux:badge>
                            @endif
                        </div>

                        <div class="space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $address->recipient_name }}</p>
                            <p>{{ $address->street }}, {{ $address->number }}@if($address->complement), {{ $address->complement }}@endif</p>
                            <p>{{ $address->neighborhood }} - {{ $address->city }}/{{ $address->state }}</p>
                            <p>{{ __('CEP:') }} {{ $address->zipcode }}</p>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 sm:flex-shrink-0">
                        @if(!$address->is_default)
                            <flux:button
                                wire:click="setDefault({{ $address->id }})"
                                variant="ghost"
                                size="sm"
                                icon="star"
                                title="{{ __('Definir como padrao') }}"
                            />
                        @endif

                        <flux:button
                            wire:click="edit({{ $address->id }})"
                            variant="ghost"
                            size="sm"
                            icon="pencil"
                            title="{{ __('Editar') }}"
                        />

                        <flux:button
                            wire:click="confirmDelete({{ $address->id }})"
                            variant="ghost"
                            size="sm"
                            icon="trash"
                            title="{{ __('Excluir') }}"
                        />
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-8 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-4">
                    <flux:icon name="map-pin" class="w-6 h-6 text-zinc-400" />
                </div>
                <flux:heading size="sm" class="mb-2">{{ __('Nenhum endereco cadastrado') }}</flux:heading>
                <flux:text class="mb-4">{{ __('Adicione um endereco para facilitar suas compras.') }}</flux:text>
                <flux:button wire:click="create" variant="primary" icon="plus">
                    {{ __('Adicionar Endereco') }}
                </flux:button>
            </div>
        @endforelse
    </div>

    {{-- Address Form Modal --}}
    <flux:modal wire:model="showForm" class="md:w-[32rem]" :dismissible="false">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $editingAddressId ? __('Editar Endereco') : __('Novo Endereco') }}
                </flux:heading>
                <flux:text class="mt-1">
                    {{ __('Preencha os dados do endereco de entrega.') }}
                </flux:text>
            </div>

            <form wire:submit="save" class="space-y-4">
                {{-- Label --}}
                <flux:input
                    wire:model="label"
                    :label="__('Rotulo (opcional)')"
                    placeholder="{{ __('Ex: Casa, Trabalho') }}"
                    maxlength="50"
                />

                {{-- Recipient Name --}}
                <flux:input
                    wire:model="recipient_name"
                    :label="__('Nome do destinatario')"
                    placeholder="{{ __('Nome completo de quem vai receber') }}"
                    required
                />

                {{-- Zipcode with mask and search --}}
                <div
                    x-data="{
                        zipcode: @entangle('zipcode'),
                        formatZipcode() {
                            let value = this.zipcode.replace(/\D/g, '');
                            if (value.length > 5) {
                                value = value.substring(0, 5) + '-' + value.substring(5, 8);
                            }
                            this.zipcode = value;
                        },
                        async searchZipcode() {
                            const cleanZipcode = this.zipcode.replace(/\D/g, '');
                            if (cleanZipcode.length === 8) {
                                $wire.searchZipcode();
                            }
                        }
                    }"
                >
                    <flux:input
                        x-model="zipcode"
                        @input="formatZipcode()"
                        @blur="searchZipcode()"
                        :label="__('CEP')"
                        placeholder="00000-000"
                        maxlength="9"
                        required
                    >
                        <x-slot name="iconTrailing">
                            <button
                                type="button"
                                @click="searchZipcode()"
                                class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                                title="{{ __('Buscar CEP') }}"
                            >
                                <flux:icon name="magnifying-glass" class="w-4 h-4" />
                            </button>
                        </x-slot>
                    </flux:input>
                    @error('zipcode')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Street --}}
                <flux:input
                    wire:model="street"
                    :label="__('Rua')"
                    placeholder="{{ __('Nome da rua, avenida, etc.') }}"
                    required
                />

                {{-- Number and Complement --}}
                <div class="grid grid-cols-2 gap-4">
                    <flux:input
                        wire:model="number"
                        :label="__('Numero')"
                        placeholder="{{ __('123') }}"
                        required
                    />

                    <flux:input
                        wire:model="complement"
                        :label="__('Complemento')"
                        placeholder="{{ __('Apto, Bloco, etc.') }}"
                    />
                </div>

                {{-- Neighborhood --}}
                <flux:input
                    wire:model="neighborhood"
                    :label="__('Bairro')"
                    placeholder="{{ __('Nome do bairro') }}"
                    required
                />

                {{-- City and State --}}
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-2">
                        <flux:input
                            wire:model="city"
                            :label="__('Cidade')"
                            placeholder="{{ __('Nome da cidade') }}"
                            required
                        />
                    </div>

                    <flux:select wire:model="state" :label="__('Estado')" required>
                        <flux:select.option value="">{{ __('UF') }}</flux:select.option>
                        @foreach(['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'] as $uf)
                            <flux:select.option value="{{ $uf }}">{{ $uf }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                {{-- Default checkbox --}}
                <flux:checkbox
                    wire:model="is_default"
                    :label="__('Definir como endereco padrao')"
                />

                {{-- Form Actions --}}
                <div class="flex justify-end gap-2 pt-4">
                    <flux:button type="button" wire:click="cancel" variant="ghost">
                        {{ __('Cancelar') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">
                            {{ $editingAddressId ? __('Salvar') : __('Adicionar') }}
                        </span>
                        <span wire:loading wire:target="save">
                            {{ __('Salvando...') }}
                        </span>
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="showDeleteModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Excluir endereco') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('Tem certeza que deseja excluir este endereco? Esta acao nao pode ser desfeita.') }}
                </flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:button wire:click="cancelDelete" variant="ghost">
                    {{ __('Cancelar') }}
                </flux:button>
                <flux:button wire:click="delete" variant="danger">
                    {{ __('Excluir') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
