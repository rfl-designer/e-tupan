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
                        <flux:heading size="xl">{{ __('Editar Administrador') }}</flux:heading>
                        <flux:subheading>{{ __('Atualize as informações do administrador') }}</flux:subheading>
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
                    <form method="POST" action="{{ route('admin.administrators.update', $admin) }}" class="flex flex-col gap-6">
                        @csrf
                        @method('PUT')

                        {{-- Name --}}
                        <flux:input
                            name="name"
                            :label="__('Nome')"
                            :value="old('name', $admin->name)"
                            type="text"
                            required
                            autofocus
                            placeholder="Nome completo"
                        />

                        {{-- Email --}}
                        <flux:input
                            name="email"
                            :label="__('Email')"
                            :value="old('email', $admin->email)"
                            type="email"
                            required
                            placeholder="admin@exemplo.com"
                        />

                        {{-- Role --}}
                        <flux:select name="role" :label="__('Papel')" required>
                            <option value="operator" @selected(old('role', $admin->role) === 'operator')>{{ __('Operador') }}</option>
                            <option value="master" @selected(old('role', $admin->role) === 'master')>{{ __('Master') }}</option>
                        </flux:select>

                        {{-- Status --}}
                        <div>
                            <flux:field>
                                <flux:label>{{ __('Status') }}</flux:label>
                                <flux:switch
                                    name="is_active"
                                    value="1"
                                    :checked="old('is_active', $admin->is_active)"
                                    label="{{ __('Administrador ativo') }}"
                                />
                            </flux:field>
                            <flux:text class="mt-1 text-sm text-neutral-400">
                                {{ __('Administradores inativos não podem acessar o painel.') }}
                            </flux:text>
                        </div>

                        {{-- Info --}}
                        @if ($admin->id === auth('admin')->id())
                            <flux:callout variant="warning">
                                {{ __('Você está editando sua própria conta. Tenha cuidado ao alterar o papel ou status.') }}
                            </flux:callout>
                        @endif

                        {{-- Actions --}}
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-800">
                            <a href="{{ route('admin.administrators.index') }}">
                                <flux:button variant="ghost">
                                    {{ __('Cancelar') }}
                                </flux:button>
                            </a>
                            <flux:button variant="primary" type="submit">
                                {{ __('Salvar Alterações') }}
                            </flux:button>
                        </div>
                    </form>
                </div>

                {{-- Metadata --}}
                <div class="mt-6 rounded-lg border border-neutral-800 bg-neutral-900/50 p-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-neutral-400">{{ __('Criado em:') }}</span>
                            <span class="ml-2 text-white">{{ $admin->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div>
                            <span class="text-neutral-400">{{ __('Último login:') }}</span>
                            <span class="ml-2 text-white">
                                @if ($admin->last_login_at)
                                    {{ $admin->last_login_at->format('d/m/Y H:i') }}
                                @else
                                    {{ __('Nunca') }}
                                @endif
                            </span>
                        </div>
                        @if ($admin->last_login_ip)
                            <div>
                                <span class="text-neutral-400">{{ __('IP do último login:') }}</span>
                                <span class="ml-2 text-white">{{ $admin->last_login_ip }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
