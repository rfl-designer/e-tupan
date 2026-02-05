<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="min-h-svh p-6 md:p-10">
            <div class="mx-auto max-w-2xl">
                {{-- Header --}}
                <div class="mb-8 flex items-center gap-4">
                    <a href="{{ route('admin.coupons.index') }}" class="text-neutral-400 hover:text-white transition-colors">
                        <flux:icon name="arrow-left" class="size-5" />
                    </a>
                    <div>
                        <flux:heading size="xl">{{ __('Novo Cupom') }}</flux:heading>
                        <flux:subheading>{{ __('Crie um novo cupom de desconto') }}</flux:subheading>
                    </div>
                </div>

                {{-- Error Messages --}}
                @if ($errors->any())
                    <flux:callout variant="danger" class="mb-6">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </flux:callout>
                @endif

                {{-- Form --}}
                <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-6">
                    <form method="POST" action="{{ route('admin.coupons.store') }}" class="flex flex-col gap-6">
                        @csrf

                        {{-- Basic Info --}}
                        <div class="space-y-4">
                            <flux:heading size="sm">{{ __('Informacoes Basicas') }}</flux:heading>

                            <div class="grid grid-cols-2 gap-4">
                                {{-- Code --}}
                                <flux:input
                                    name="code"
                                    :label="__('Codigo')"
                                    :value="old('code')"
                                    type="text"
                                    required
                                    autofocus
                                    placeholder="Ex: PROMO10"
                                    class="uppercase"
                                />

                                {{-- Name --}}
                                <flux:input
                                    name="name"
                                    :label="__('Nome')"
                                    :value="old('name')"
                                    type="text"
                                    required
                                    placeholder="Ex: Promocao de Lancamento"
                                />
                            </div>

                            {{-- Description --}}
                            <flux:textarea
                                name="description"
                                :label="__('Descricao (opcional)')"
                                :value="old('description')"
                                rows="2"
                                placeholder="Descricao interna do cupom..."
                            />
                        </div>

                        {{-- Discount Settings --}}
                        <div class="space-y-4 border-t border-neutral-800 pt-6">
                            <flux:heading size="sm">{{ __('Configuracao do Desconto') }}</flux:heading>

                            <div class="grid grid-cols-2 gap-4">
                                {{-- Type --}}
                                <flux:select name="type" :label="__('Tipo de Desconto')" required>
                                    <option value="">{{ __('Selecione um tipo') }}</option>
                                    @foreach ($types as $value => $label)
                                        <option value="{{ $value }}" @selected(old('type') === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </flux:select>

                                {{-- Value --}}
                                <flux:input
                                    name="value"
                                    :label="__('Valor')"
                                    :value="old('value')"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="10 ou 25.50"
                                    description="Porcentagem (0-100) ou valor em R$"
                                />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                {{-- Minimum Order Value --}}
                                <flux:input
                                    name="minimum_order_value"
                                    :label="__('Valor Minimo do Pedido (R$)')"
                                    :value="old('minimum_order_value')"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="100.00"
                                />

                                {{-- Maximum Discount --}}
                                <flux:input
                                    name="maximum_discount"
                                    :label="__('Desconto Maximo (R$)')"
                                    :value="old('maximum_discount')"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="50.00"
                                    description="Para cupons de porcentagem"
                                />
                            </div>
                        </div>

                        {{-- Usage Limits --}}
                        <div class="space-y-4 border-t border-neutral-800 pt-6">
                            <flux:heading size="sm">{{ __('Limites de Uso') }}</flux:heading>

                            <div class="grid grid-cols-2 gap-4">
                                {{-- Usage Limit --}}
                                <flux:input
                                    name="usage_limit"
                                    :label="__('Limite Total de Usos')"
                                    :value="old('usage_limit')"
                                    type="number"
                                    min="1"
                                    placeholder="100"
                                    description="Deixe vazio para ilimitado"
                                />

                                {{-- Usage Limit Per User --}}
                                <flux:input
                                    name="usage_limit_per_user"
                                    :label="__('Limite por Usuario')"
                                    :value="old('usage_limit_per_user')"
                                    type="number"
                                    min="1"
                                    placeholder="1"
                                    description="Deixe vazio para ilimitado"
                                />
                            </div>
                        </div>

                        {{-- Validity --}}
                        <div class="space-y-4 border-t border-neutral-800 pt-6">
                            <flux:heading size="sm">{{ __('Validade') }}</flux:heading>

                            <div class="grid grid-cols-2 gap-4">
                                {{-- Starts At --}}
                                <flux:input
                                    name="starts_at"
                                    :label="__('Data de Inicio')"
                                    :value="old('starts_at')"
                                    type="datetime-local"
                                    description="Deixe vazio para comecar agora"
                                />

                                {{-- Expires At --}}
                                <flux:input
                                    name="expires_at"
                                    :label="__('Data de Expiracao')"
                                    :value="old('expires_at')"
                                    type="datetime-local"
                                    description="Deixe vazio para nunca expirar"
                                />
                            </div>
                        </div>

                        {{-- Status --}}
                        <div class="border-t border-neutral-800 pt-6">
                            <flux:checkbox
                                name="is_active"
                                value="1"
                                :checked="old('is_active', true)"
                                label="{{ __('Cupom ativo') }}"
                                description="{{ __('O cupom pode ser usado imediatamente') }}"
                            />
                        </div>

                        {{-- Type Help --}}
                        <div class="rounded-lg border border-neutral-700 bg-neutral-800/50 p-4">
                            <flux:heading size="sm" class="mb-2">{{ __('Tipos de Cupom') }}</flux:heading>
                            <ul class="text-sm text-neutral-400 space-y-1">
                                <li><strong class="text-neutral-300">{{ __('Porcentagem') }}:</strong> {{ __('Desconto em % do subtotal (ex: 10% de desconto)') }}</li>
                                <li><strong class="text-neutral-300">{{ __('Valor Fixo') }}:</strong> {{ __('Desconto em valor fixo (ex: R$ 25 de desconto)') }}</li>
                                <li><strong class="text-neutral-300">{{ __('Frete Gratis') }}:</strong> {{ __('Remove o custo do frete do pedido') }}</li>
                            </ul>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-800">
                            <a href="{{ route('admin.coupons.index') }}">
                                <flux:button variant="ghost">
                                    {{ __('Cancelar') }}
                                </flux:button>
                            </a>
                            <flux:button variant="primary" type="submit">
                                {{ __('Criar Cupom') }}
                            </flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
