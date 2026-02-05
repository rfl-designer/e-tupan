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
                    <a href="{{ route('admin.attributes.index') }}" class="text-neutral-400 hover:text-white transition-colors">
                        <flux:icon name="arrow-left" class="size-5" />
                    </a>
                    <div>
                        <flux:heading size="xl">{{ __('Novo Atributo') }}</flux:heading>
                        <flux:subheading>{{ __('Adicione um novo atributo global de produtos') }}</flux:subheading>
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
                    <form method="POST" action="{{ route('admin.attributes.store') }}" class="flex flex-col gap-6">
                        @csrf

                        {{-- Name --}}
                        <flux:input
                            name="name"
                            :label="__('Nome')"
                            :value="old('name')"
                            type="text"
                            required
                            autofocus
                            placeholder="Ex: Cor, Tamanho, Material"
                        />

                        {{-- Slug --}}
                        <flux:input
                            name="slug"
                            :label="__('Slug')"
                            :value="old('slug')"
                            type="text"
                            placeholder="cor (deixe vazio para gerar automaticamente)"
                        />

                        {{-- Type --}}
                        <flux:select name="type" :label="__('Tipo')" required>
                            <option value="">{{ __('Selecione um tipo') }}</option>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}" @selected(old('type') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </flux:select>

                        <div class="rounded-lg border border-neutral-700 bg-neutral-800/50 p-4">
                            <flux:heading size="sm" class="mb-2">{{ __('Tipos de Atributo') }}</flux:heading>
                            <ul class="text-sm text-neutral-400 space-y-1">
                                <li><strong class="text-neutral-300">{{ __('Selecao') }}:</strong> {{ __('Lista de opcoes (ex: P, M, G, GG)') }}</li>
                                <li><strong class="text-neutral-300">{{ __('Cor') }}:</strong> {{ __('Valores com codigo hexadecimal (ex: Vermelho #FF0000)') }}</li>
                                <li><strong class="text-neutral-300">{{ __('Texto') }}:</strong> {{ __('Texto livre (ex: Personalizacao)') }}</li>
                            </ul>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-800">
                            <a href="{{ route('admin.attributes.index') }}">
                                <flux:button variant="ghost">
                                    {{ __('Cancelar') }}
                                </flux:button>
                            </a>
                            <flux:button variant="primary" type="submit">
                                {{ __('Criar Atributo') }}
                            </flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
