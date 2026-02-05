<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="min-h-svh p-6 md:p-10">
            <div class="mx-auto max-w-4xl">
                {{-- Header --}}
                <div class="mb-8 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('admin.dashboard') }}" class="text-neutral-400 hover:text-white transition-colors">
                            <flux:icon name="arrow-left" class="size-5" />
                        </a>
                        <div>
                            <flux:heading size="xl">{{ __('Configuracoes de Frete') }}</flux:heading>
                            <flux:subheading>{{ __('Configure transportadoras, frete gratis e endereco de origem') }}</flux:subheading>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.shipping.settings.test-connection') }}">
                        @csrf
                        <flux:button type="submit" variant="filled">
                            <flux:icon name="signal" class="size-4 mr-1" />
                            {{ __('Testar Conexao') }}
                        </flux:button>
                    </form>
                </div>

                {{-- Success Message --}}
                @if (session('success'))
                    <flux:callout variant="success" class="mb-6">
                        {{ session('success') }}
                    </flux:callout>
                @endif

                {{-- Error Messages --}}
                @if (session('error'))
                    <flux:callout variant="danger" class="mb-6">
                        {{ session('error') }}
                    </flux:callout>
                @endif

                @if ($errors->any())
                    <flux:callout variant="danger" class="mb-6">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </flux:callout>
                @endif

                <div class="space-y-6">
                    {{-- Origin Address Section --}}
                    <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-6">
                        <flux:heading size="lg" class="mb-4">{{ __('Endereco de Origem') }}</flux:heading>
                        <flux:subheading class="mb-6">{{ __('Endereco de onde os produtos serao enviados') }}</flux:subheading>

                        <form method="POST" action="{{ route('admin.shipping.settings.origin') }}">
                            @csrf
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <flux:input
                                        name="zipcode"
                                        label="{{ __('CEP') }}"
                                        :value="old('zipcode', $originAddress['zipcode'])"
                                        placeholder="00000000"
                                        maxlength="8"
                                        required
                                    />
                                </div>
                                <div>
                                    <flux:input
                                        name="state"
                                        label="{{ __('Estado') }}"
                                        :value="old('state', $originAddress['state'])"
                                        placeholder="SP"
                                        maxlength="2"
                                        required
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <flux:input
                                        name="street"
                                        label="{{ __('Rua') }}"
                                        :value="old('street', $originAddress['street'])"
                                        placeholder="Nome da rua"
                                        required
                                    />
                                </div>
                                <div>
                                    <flux:input
                                        name="number"
                                        label="{{ __('Numero') }}"
                                        :value="old('number', $originAddress['number'])"
                                        placeholder="123"
                                        required
                                    />
                                </div>
                                <div>
                                    <flux:input
                                        name="complement"
                                        label="{{ __('Complemento') }}"
                                        :value="old('complement', $originAddress['complement'])"
                                        placeholder="Sala 101"
                                    />
                                </div>
                                <div>
                                    <flux:input
                                        name="neighborhood"
                                        label="{{ __('Bairro') }}"
                                        :value="old('neighborhood', $originAddress['neighborhood'])"
                                        placeholder="Centro"
                                        required
                                    />
                                </div>
                                <div>
                                    <flux:input
                                        name="city"
                                        label="{{ __('Cidade') }}"
                                        :value="old('city', $originAddress['city'])"
                                        placeholder="Sao Paulo"
                                        required
                                    />
                                </div>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <flux:button type="submit" variant="primary">
                                    {{ __('Salvar Endereco') }}
                                </flux:button>
                            </div>
                        </form>
                    </div>

                    {{-- Handling Days Section --}}
                    <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-6">
                        <flux:heading size="lg" class="mb-4">{{ __('Prazo de Manuseio') }}</flux:heading>
                        <flux:subheading class="mb-6">{{ __('Dias adicionais para preparacao do pedido') }}</flux:subheading>

                        <form method="POST" action="{{ route('admin.shipping.settings.handling') }}" class="flex items-end gap-4">
                            @csrf
                            <div class="w-32">
                                <flux:input
                                    type="number"
                                    name="handling_days"
                                    label="{{ __('Dias') }}"
                                    :value="old('handling_days', $handlingDays)"
                                    min="0"
                                    max="30"
                                    required
                                />
                            </div>
                            <flux:button type="submit" variant="primary">
                                {{ __('Salvar') }}
                            </flux:button>
                        </form>
                        <p class="mt-2 text-sm text-neutral-500">
                            {{ __('Este prazo sera adicionado ao prazo da transportadora') }}
                        </p>
                    </div>

                    {{-- Free Shipping Section --}}
                    <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-6">
                        <flux:heading size="lg" class="mb-4">{{ __('Frete Gratis') }}</flux:heading>
                        <flux:subheading class="mb-6">{{ __('Configure frete gratis para compras acima de um valor') }}</flux:subheading>

                        <form method="POST" action="{{ route('admin.shipping.settings.free-shipping') }}">
                            @csrf
                            <div class="space-y-4">
                                <div class="flex items-center gap-4">
                                    <flux:checkbox
                                        name="enabled"
                                        value="1"
                                        :checked="old('enabled', $freeShippingConfig['enabled'])"
                                        label="{{ __('Habilitar frete gratis') }}"
                                    />
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <flux:input
                                            type="number"
                                            name="min_amount"
                                            label="{{ __('Valor minimo (R$)') }}"
                                            :value="old('min_amount', $freeShippingConfig['min_amount'] / 100)"
                                            min="0"
                                            step="0.01"
                                            placeholder="150.00"
                                        />
                                    </div>
                                    <div>
                                        <flux:select name="carrier" label="{{ __('Transportadora') }}">
                                            @foreach ($carrierOptions as $value => $label)
                                                <option value="{{ $value }}" @selected(old('carrier', $freeShippingConfig['carrier']) === $value)>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </flux:select>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <flux:button type="submit" variant="primary">
                                    {{ __('Salvar Frete Gratis') }}
                                </flux:button>
                            </div>
                        </form>
                    </div>

                    {{-- Carriers Section --}}
                    <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-6">
                        <flux:heading size="lg" class="mb-4">{{ __('Transportadoras') }}</flux:heading>
                        <flux:subheading class="mb-6">{{ __('Habilite e configure as transportadoras disponiveis') }}</flux:subheading>

                        <form method="POST" action="{{ route('admin.shipping.settings.carriers') }}">
                            @csrf
                            <div class="space-y-4">
                                @foreach ($carriers as $index => $item)
                                    @php
                                        $carrier = $item['carrier'];
                                        $config = $item['config'];
                                    @endphp
                                    <div class="rounded-lg border border-neutral-700 bg-neutral-800/50 p-4">
                                        <div class="flex flex-wrap items-center gap-4">
                                            <div class="flex items-center gap-3 min-w-[200px]">
                                                <flux:checkbox
                                                    name="carriers[{{ $carrier->value }}][enabled]"
                                                    value="1"
                                                    :checked="old('carriers.' . $carrier->value . '.enabled', $config['enabled'])"
                                                />
                                                <div>
                                                    <p class="font-medium text-white">{{ $carrier->label() }}</p>
                                                    <p class="text-xs text-neutral-500">{{ $carrier->company() }}</p>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <div class="w-24">
                                                    <flux:input
                                                        type="number"
                                                        name="carriers[{{ $carrier->value }}][additional_days]"
                                                        :value="old('carriers.' . $carrier->value . '.additional_days', $config['additional_days'])"
                                                        min="0"
                                                        max="30"
                                                        placeholder="0"
                                                    />
                                                </div>
                                                <span class="text-sm text-neutral-500">{{ __('dias extras') }}</span>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <div class="w-20">
                                                    <flux:input
                                                        type="number"
                                                        name="carriers[{{ $carrier->value }}][price_margin]"
                                                        :value="old('carriers.' . $carrier->value . '.price_margin', $config['price_margin'])"
                                                        min="0"
                                                        max="100"
                                                        step="0.1"
                                                        placeholder="0"
                                                    />
                                                </div>
                                                <span class="text-sm text-neutral-500">% {{ __('margem') }}</span>
                                            </div>

                                            <input
                                                type="hidden"
                                                name="carriers[{{ $carrier->value }}][position]"
                                                value="{{ $config['position'] }}"
                                            />

                                            @if ($carrier->isExpress())
                                                <flux:badge color="yellow" size="sm">{{ __('Expresso') }}</flux:badge>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-6 flex justify-end">
                                <flux:button type="submit" variant="primary">
                                    {{ __('Salvar Transportadoras') }}
                                </flux:button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Back to Dashboard --}}
                <div class="mt-6">
                    <a href="{{ route('admin.dashboard') }}" class="text-sm text-neutral-400 hover:text-white transition-colors">
                        &larr; {{ __('Voltar ao Dashboard') }}
                    </a>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
