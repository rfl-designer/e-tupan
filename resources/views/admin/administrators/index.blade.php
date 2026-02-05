<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="min-h-svh p-6 md:p-10">
            <div class="mx-auto max-w-6xl">
                {{-- Header --}}
                <div class="mb-8 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('admin.dashboard') }}" class="text-neutral-400 hover:text-white transition-colors">
                            <flux:icon name="arrow-left" class="size-5" />
                        </a>
                        <div>
                            <flux:heading size="xl">{{ __('Administradores') }}</flux:heading>
                            <flux:subheading>{{ __('Gerencie os administradores do sistema') }}</flux:subheading>
                        </div>
                    </div>
                    <a href="{{ route('admin.administrators.create') }}">
                        <flux:button variant="primary">
                            <flux:icon name="plus" class="size-4 mr-1" />
                            {{ __('Novo Administrador') }}
                        </flux:button>
                    </a>
                </div>

                {{-- Success Message --}}
                @if (session('success'))
                    <flux:callout variant="success" class="mb-6">
                        {{ session('success') }}
                    </flux:callout>
                @endif

                {{-- Error Messages --}}
                @if ($errors->any())
                    <flux:callout variant="danger" class="mb-6">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </flux:callout>
                @endif

                {{-- Administrators Table --}}
                <div class="overflow-hidden rounded-lg border border-neutral-800 bg-neutral-900">
                    <table class="w-full">
                        <thead class="border-b border-neutral-800 bg-neutral-900/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-medium text-neutral-400">{{ __('Nome') }}</th>
                                <th class="px-6 py-4 text-left text-sm font-medium text-neutral-400">{{ __('Email') }}</th>
                                <th class="px-6 py-4 text-left text-sm font-medium text-neutral-400">{{ __('Papel') }}</th>
                                <th class="px-6 py-4 text-left text-sm font-medium text-neutral-400">{{ __('Status') }}</th>
                                <th class="px-6 py-4 text-left text-sm font-medium text-neutral-400">{{ __('Último Login') }}</th>
                                <th class="px-6 py-4 text-right text-sm font-medium text-neutral-400">{{ __('Ações') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-800">
                            @forelse ($admins as $admin)
                                <tr wire:key="admin-{{ $admin->id }}" class="hover:bg-neutral-800/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <flux:avatar size="sm" name="{{ $admin->name }}" />
                                            <span class="text-sm font-medium text-white">{{ $admin->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-neutral-300">{{ $admin->email }}</td>
                                    <td class="px-6 py-4">
                                        @if ($admin->isMaster())
                                            <flux:badge color="amber">{{ __('Master') }}</flux:badge>
                                        @else
                                            <flux:badge color="zinc">{{ __('Operador') }}</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($admin->is_active)
                                            <flux:badge color="green">{{ __('Ativo') }}</flux:badge>
                                        @else
                                            <flux:badge color="red">{{ __('Inativo') }}</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-neutral-400">
                                        @if ($admin->last_login_at)
                                            {{ $admin->last_login_at->diffForHumans() }}
                                        @else
                                            <span class="text-neutral-500">{{ __('Nunca') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('admin.administrators.edit', $admin) }}">
                                                <flux:button variant="ghost" size="sm">
                                                    <flux:icon name="pencil" class="size-4" />
                                                </flux:button>
                                            </a>
                                            @if ($admin->id !== auth('admin')->id())
                                                <form method="POST" action="{{ route('admin.administrators.destroy', $admin) }}" onsubmit="return confirm('Tem certeza que deseja excluir este administrador?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <flux:button variant="ghost" size="sm" type="submit">
                                                        <flux:icon name="trash" class="size-4 text-red-400" />
                                                    </flux:button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-neutral-400">
                                        {{ __('Nenhum administrador encontrado.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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
