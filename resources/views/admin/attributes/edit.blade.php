<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        {{-- SortableJS --}}
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="min-h-svh p-6 md:p-10">
            <div class="mx-auto max-w-4xl">
                {{-- Header --}}
                <div class="mb-8 flex items-center gap-4">
                    <a href="{{ route('admin.attributes.index') }}" class="text-neutral-400 hover:text-white transition-colors">
                        <flux:icon name="arrow-left" class="size-5" />
                    </a>
                    <div>
                        <flux:heading size="xl">{{ __('Editar Atributo') }}</flux:heading>
                        <flux:subheading>{{ $attribute->name }}</flux:subheading>
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

                <div class="grid gap-6 lg:grid-cols-2">
                    {{-- Attribute Form --}}
                    <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-6">
                        <flux:heading size="lg" class="mb-4">{{ __('Dados do Atributo') }}</flux:heading>

                        <form method="POST" action="{{ route('admin.attributes.update', $attribute) }}" class="flex flex-col gap-6">
                            @csrf
                            @method('PUT')

                            {{-- Name --}}
                            <flux:input
                                name="name"
                                :label="__('Nome')"
                                :value="old('name', $attribute->name)"
                                type="text"
                                required
                                autofocus
                                placeholder="Ex: Cor, Tamanho, Material"
                            />

                            {{-- Slug --}}
                            <flux:input
                                name="slug"
                                :label="__('Slug')"
                                :value="old('slug', $attribute->slug)"
                                type="text"
                                placeholder="cor"
                            />

                            {{-- Type --}}
                            <flux:select name="type" :label="__('Tipo')" required>
                                @foreach ($types as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type', $attribute->type->value) === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </flux:select>

                            {{-- Actions --}}
                            <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-800">
                                <a href="{{ route('admin.attributes.index') }}">
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

                    {{-- Attribute Values Manager --}}
                    <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-6">
                        <flux:heading size="lg" class="mb-4">{{ __('Valores do Atributo') }}</flux:heading>
                        <livewire:admin.attribute-manager :attribute="$attribute" />
                    </div>
                </div>

                {{-- Back to List --}}
                <div class="mt-6">
                    <a href="{{ route('admin.attributes.index') }}" class="text-sm text-neutral-400 hover:text-white transition-colors">
                        &larr; {{ __('Voltar para Atributos') }}
                    </a>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
