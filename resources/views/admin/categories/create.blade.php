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
                    <a href="{{ route('admin.categories.index') }}" class="text-neutral-400 hover:text-white transition-colors">
                        <flux:icon name="arrow-left" class="size-5" />
                    </a>
                    <div>
                        <flux:heading size="xl">{{ __('Nova Categoria') }}</flux:heading>
                        <flux:subheading>{{ __('Adicione uma nova categoria de produtos') }}</flux:subheading>
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
                    <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data" class="flex flex-col gap-6">
                        @csrf

                        {{-- Name --}}
                        <flux:input
                            name="name"
                            :label="__('Nome')"
                            :value="old('name')"
                            type="text"
                            required
                            autofocus
                            placeholder="Nome da categoria"
                        />

                        {{-- Slug --}}
                        <flux:input
                            name="slug"
                            :label="__('Slug')"
                            :value="old('slug')"
                            type="text"
                            placeholder="nome-da-categoria (deixe vazio para gerar automaticamente)"
                        />

                        {{-- Parent Category --}}
                        <flux:select name="parent_id" :label="__('Categoria Pai')">
                            <option value="">{{ __('Nenhuma (categoria raiz)') }}</option>
                            @foreach ($parentCategories as $parent)
                                <option value="{{ $parent['id'] }}" @selected(old('parent_id') == $parent['id'])>
                                    {{ $parent['name'] }}
                                </option>
                            @endforeach
                        </flux:select>

                        {{-- Description --}}
                        <flux:textarea
                            name="description"
                            :label="__('Descrição')"
                            :value="old('description')"
                            placeholder="Descrição da categoria"
                            rows="3"
                        />

                        {{-- Image --}}
                        <flux:input
                            name="image"
                            :label="__('Imagem')"
                            type="file"
                            accept="image/*"
                        />

                        {{-- SEO Section --}}
                        <div class="border-t border-neutral-800 pt-6">
                            <flux:heading size="sm" class="mb-4">{{ __('SEO') }}</flux:heading>

                            <div class="flex flex-col gap-4">
                                <flux:input
                                    name="meta_title"
                                    :label="__('Título SEO')"
                                    :value="old('meta_title')"
                                    type="text"
                                    placeholder="Título para mecanismos de busca (máx. 60 caracteres)"
                                    maxlength="60"
                                />

                                <flux:textarea
                                    name="meta_description"
                                    :label="__('Descrição SEO')"
                                    :value="old('meta_description')"
                                    placeholder="Descrição para mecanismos de busca (máx. 160 caracteres)"
                                    rows="2"
                                    maxlength="160"
                                />
                            </div>
                        </div>

                        {{-- Status --}}
                        <div class="border-t border-neutral-800 pt-6">
                            <flux:field>
                                <flux:label>{{ __('Status') }}</flux:label>
                                <flux:switch
                                    name="is_active"
                                    value="1"
                                    :checked="old('is_active', true)"
                                    label="{{ __('Categoria ativa') }}"
                                />
                            </flux:field>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-800">
                            <a href="{{ route('admin.categories.index') }}">
                                <flux:button variant="ghost">
                                    {{ __('Cancelar') }}
                                </flux:button>
                            </a>
                            <flux:button variant="primary" type="submit">
                                {{ __('Criar Categoria') }}
                            </flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
