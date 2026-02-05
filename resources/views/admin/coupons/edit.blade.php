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
                        <flux:heading size="xl">{{ __('Editar Cupom') }}</flux:heading>
                        <flux:subheading>{{ $coupon->code }} - {{ $coupon->name }}</flux:subheading>
                    </div>
                </div>

                {{-- Usage Stats --}}
                <div class="mb-6 rounded-lg border border-neutral-800 bg-neutral-900 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-neutral-400">{{ __('Total de Usos') }}</p>
                            <p class="text-2xl font-bold text-white">{{ $coupon->times_used }}</p>
                        </div>
                        @if ($coupon->usage_limit)
                            <div class="text-right">
                                <p class="text-sm text-neutral-400">{{ __('Limite') }}</p>
                                <p class="text-2xl font-bold text-white">{{ $coupon->usage_limit }}</p>
                            </div>
                        @endif
                        <div class="text-right">
                            <p class="text-sm text-neutral-400">{{ __('Status') }}</p>
                            @if (!$coupon->is_active)
                                <flux:badge color="zinc">{{ __('Inativo') }}</flux:badge>
                            @elseif ($coupon->expires_at && $coupon->expires_at->isPast())
                                <flux:badge color="red">{{ __('Expirado') }}</flux:badge>
                            @elseif ($coupon->starts_at && $coupon->starts_at->isFuture())
                                <flux:badge color="yellow">{{ __('Agendado') }}</flux:badge>
                            @elseif ($coupon->usage_limit && $coupon->times_used >= $coupon->usage_limit)
                                <flux:badge color="orange">{{ __('Esgotado') }}</flux:badge>
                            @else
                                <flux:badge color="green">{{ __('Ativo') }}</flux:badge>
                            @endif
                        </div>
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
                    <form method="POST" action="{{ route('admin.coupons.update', $coupon) }}" class="flex flex-col gap-6">
                        @csrf
                        @method('PUT')

                        {{-- Basic Info --}}
                        <div class="space-y-4">
                            <flux:heading size="sm">{{ __('Informacoes Basicas') }}</flux:heading>

                            <div class="grid grid-cols-2 gap-4">
                                {{-- Code --}}
                                <flux:input
                                    name="code"
                                    :label="__('Codigo')"
                                    :value="old('code', $coupon->code)"
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
                                    :value="old('name', $coupon->name)"
                                    type="text"
                                    required
                                    placeholder="Ex: Promocao de Lancamento"
                                />
                            </div>

                            {{-- Description --}}
                            <flux:textarea
                                name="description"
                                :label="__('Descricao (opcional)')"
                                :value="old('description', $coupon->description)"
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
                                        <option value="{{ $value }}" @selected(old('type', $coupon->type->value) === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </flux:select>

                                {{-- Value --}}
                                @php
                                    $displayValue = $coupon->type === \App\Domain\Marketing\Enums\CouponType::Fixed
                                        ? $coupon->value / 100
                                        : $coupon->value;
                                @endphp
                                <flux:input
                                    name="value"
                                    :label="__('Valor')"
                                    :value="old('value', $displayValue)"
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
                                    :value="old('minimum_order_value', $coupon->minimum_order_value ? $coupon->minimum_order_value / 100 : '')"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="100.00"
                                />

                                {{-- Maximum Discount --}}
                                <flux:input
                                    name="maximum_discount"
                                    :label="__('Desconto Maximo (R$)')"
                                    :value="old('maximum_discount', $coupon->maximum_discount ? $coupon->maximum_discount / 100 : '')"
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
                                    :value="old('usage_limit', $coupon->usage_limit)"
                                    type="number"
                                    min="1"
                                    placeholder="100"
                                    description="Deixe vazio para ilimitado"
                                />

                                {{-- Usage Limit Per User --}}
                                <flux:input
                                    name="usage_limit_per_user"
                                    :label="__('Limite por Usuario')"
                                    :value="old('usage_limit_per_user', $coupon->usage_limit_per_user)"
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
                                    :value="old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i'))"
                                    type="datetime-local"
                                    description="Deixe vazio para comecar agora"
                                />

                                {{-- Expires At --}}
                                <flux:input
                                    name="expires_at"
                                    :label="__('Data de Expiracao')"
                                    :value="old('expires_at', $coupon->expires_at?->format('Y-m-d\TH:i'))"
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
                                :checked="old('is_active', $coupon->is_active)"
                                label="{{ __('Cupom ativo') }}"
                                description="{{ __('O cupom pode ser usado pelos clientes') }}"
                            />
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-800">
                            <a href="{{ route('admin.coupons.index') }}">
                                <flux:button variant="ghost">
                                    {{ __('Cancelar') }}
                                </flux:button>
                            </a>
                            <flux:button variant="primary" type="submit">
                                {{ __('Salvar Alteracoes') }}
                            </flux:button>
                        </div>
                    </form>
                </div>

                {{-- Danger Zone --}}
                <div class="mt-6 rounded-lg border border-red-900/50 bg-red-950/20 p-6">
                    <flux:heading size="sm" class="text-red-400 mb-4">{{ __('Zona de Perigo') }}</flux:heading>
                    <p class="text-sm text-neutral-400 mb-4">{{ __('Uma vez excluido, este cupom nao pode ser recuperado. Todos os dados associados serao perdidos.') }}</p>
                    <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" onsubmit="return confirm('Tem certeza que deseja excluir este cupom? Esta acao nao pode ser desfeita.')">
                        @csrf
                        @method('DELETE')
                        <flux:button variant="danger" type="submit">
                            <flux:icon name="trash" class="size-4 mr-1" />
                            {{ __('Excluir Cupom') }}
                        </flux:button>
                    </form>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
