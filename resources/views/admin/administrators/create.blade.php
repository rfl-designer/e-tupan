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
                    <a href="{{ route('admin.administrators.index') }}" class="text-neutral-400 hover:text-white transition-colors">
                        <flux:icon name="arrow-left" class="size-5" />
                    </a>
                    <div>
                        <flux:heading size="xl">{{ __('Novo Administrador') }}</flux:heading>
                        <flux:subheading>{{ __('Adicione um novo administrador ao sistema') }}</flux:subheading>
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
                    <form method="POST" action="{{ route('admin.administrators.store') }}" class="flex flex-col gap-6">
                        @csrf

                        {{-- Name --}}
                        <flux:input
                            name="name"
                            :label="__('Nome')"
                            :value="old('name')"
                            type="text"
                            required
                            autofocus
                            placeholder="Nome completo"
                        />

                        {{-- Email --}}
                        <flux:input
                            name="email"
                            :label="__('Email')"
                            :value="old('email')"
                            type="email"
                            required
                            placeholder="admin@exemplo.com"
                        />

                        {{-- Role --}}
                        <flux:select name="role" :label="__('Papel')" required>
                            <option value="">{{ __('Selecione um papel') }}</option>
                            <option value="operator" @selected(old('role') === 'operator')>{{ __('Operador') }}</option>
                            <option value="master" @selected(old('role') === 'master')>{{ __('Master') }}</option>
                        </flux:select>

                        <flux:callout variant="info">
                            {{ __('Um email de convite será enviado para o administrador definir sua senha.') }}
                        </flux:callout>

                        {{-- Actions --}}
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-800">
                            <a href="{{ route('admin.administrators.index') }}">
                                <flux:button variant="ghost">
                                    {{ __('Cancelar') }}
                                </flux:button>
                            </a>
                            <flux:button variant="primary" type="submit">
                                {{ __('Criar Administrador') }}
                            </flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
