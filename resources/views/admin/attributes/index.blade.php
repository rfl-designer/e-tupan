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
                            <flux:heading size="xl">{{ __('Atributos') }}</flux:heading>
                            <flux:subheading>{{ __('Gerencie os atributos globais de produtos') }}</flux:subheading>
                        </div>
                    </div>
                    <a href="{{ route('admin.attributes.create') }}">
                        <flux:button variant="primary">
                            <flux:icon name="plus" class="size-4 mr-1" />
                            {{ __('Novo Atributo') }}
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

                {{-- Attributes Table --}}
                <div class="overflow-hidden rounded-lg border border-neutral-800 bg-neutral-900">
                    <table class="w-full">
                        <thead class="border-b border-neutral-800 bg-neutral-900/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-medium text-neutral-400">{{ __('Nome') }}</th>
                                <th class="px-6 py-4 text-left text-sm font-medium text-neutral-400">{{ __('Slug') }}</th>
                                <th class="px-6 py-4 text-left text-sm font-medium text-neutral-400">{{ __('Tipo') }}</th>
                                <th class="px-6 py-4 text-center text-sm font-medium text-neutral-400">{{ __('Valores') }}</th>
                                <th class="px-6 py-4 text-right text-sm font-medium text-neutral-400">{{ __('Acoes') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-800">
                            @forelse ($attributes as $attribute)
                                <tr wire:key="attribute-{{ $attribute->id }}" class="hover:bg-neutral-800/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="text-sm font-medium text-white">{{ $attribute->name }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-neutral-300">
                                        <code class="rounded bg-neutral-800 px-2 py-1 text-xs">{{ $attribute->slug }}</code>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($attribute->isColor())
                                            <flux:badge color="pink">{{ $attribute->type->label() }}</flux:badge>
                                        @elseif ($attribute->isSelect())
                                            <flux:badge color="blue">{{ $attribute->type->label() }}</flux:badge>
                                        @else
                                            <flux:badge color="zinc">{{ $attribute->type->label() }}</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-sm text-neutral-300">{{ $attribute->values_count }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('admin.attributes.edit', $attribute) }}">
                                                <flux:button variant="ghost" size="sm">
                                                    <flux:icon name="pencil" class="size-4" />
                                                </flux:button>
                                            </a>
                                            <form method="POST" action="{{ route('admin.attributes.destroy', $attribute) }}" onsubmit="return confirm('Tem certeza que deseja excluir este atributo?')">
                                                @csrf
                                                @method('DELETE')
                                                <flux:button variant="ghost" size="sm" type="submit">
                                                    <flux:icon name="trash" class="size-4 text-red-400" />
                                                </flux:button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-neutral-400">
                                        <flux:icon name="tag" class="mx-auto size-12 mb-4 text-neutral-600" />
                                        <p class="text-lg font-medium">{{ __('Nenhum atributo encontrado') }}</p>
                                        <p class="mt-1 text-sm">{{ __('Comece criando seu primeiro atributo.') }}</p>
                                        <a href="{{ route('admin.attributes.create') }}" class="mt-4 inline-block">
                                            <flux:button variant="primary" size="sm">
                                                <flux:icon name="plus" class="size-4 mr-1" />
                                                {{ __('Criar Atributo') }}
                                            </flux:button>
                                        </a>
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
