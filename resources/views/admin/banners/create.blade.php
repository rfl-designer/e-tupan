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
                    <a href="{{ route('admin.banners.index') }}" class="text-neutral-400 hover:text-white transition-colors">
                        <flux:icon name="arrow-left" class="size-5" />
                    </a>
                    <div>
                        <flux:heading size="xl">{{ __('Novo Banner') }}</flux:heading>
                        <flux:subheading>{{ __('Crie um novo banner promocional') }}</flux:subheading>
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
                    <form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data" class="flex flex-col gap-6">
                        @csrf

                        {{-- Basic Info --}}
                        <div class="space-y-4">
                            <flux:heading size="sm">{{ __('Identificacao') }}</flux:heading>

                            {{-- Title --}}
                            <flux:input
                                name="title"
                                :label="__('Titulo')"
                                :value="old('title')"
                                type="text"
                                required
                                autofocus
                                placeholder="Ex: Black Friday 2024"
                                description="Usado internamente para identificacao (nao exibido no storefront)"
                            />
                        </div>

                        {{-- Desktop Image --}}
                        <div class="space-y-4 border-t border-neutral-800 pt-6">
                            <flux:heading size="sm">{{ __('Imagem Desktop') }} <span class="text-red-500">*</span></flux:heading>

                            <div class="space-y-2">
                                <flux:input
                                    name="image_desktop"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    required
                                />
                                <p class="text-sm text-neutral-400">
                                    {{ __('Formatos aceitos: JPG, PNG, WebP. Tamanho maximo: 2MB.') }}<br>
                                    {{ __('Dimensao recomendada: 1920x500 pixels.') }}
                                </p>
                            </div>
                        </div>

                        {{-- Mobile Image --}}
                        <div class="space-y-4 border-t border-neutral-800 pt-6">
                            <flux:heading size="sm">{{ __('Imagem Mobile (opcional)') }}</flux:heading>

                            <div class="space-y-2">
                                <flux:input
                                    name="image_mobile"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                />
                                <p class="text-sm text-neutral-400">
                                    {{ __('Formatos aceitos: JPG, PNG, WebP. Tamanho maximo: 2MB.') }}<br>
                                    {{ __('Dimensao recomendada: 768x400 pixels.') }}<br>
                                    {{ __('Se nao informada, a imagem desktop sera usada em dispositivos moveis.') }}
                                </p>
                            </div>
                        </div>

                        {{-- Link and Alt Text --}}
                        <div class="space-y-4 border-t border-neutral-800 pt-6">
                            <flux:heading size="sm">{{ __('Configuracoes') }}</flux:heading>

                            {{-- Link --}}
                            <flux:input
                                name="link"
                                :label="__('Link de Destino')"
                                :value="old('link')"
                                type="text"
                                placeholder="Ex: /categoria/promocoes ou https://example.com"
                                description="URL interna (/exemplo) ou externa (https://...)"
                            />

                            {{-- Alt Text --}}
                            <flux:input
                                name="alt_text"
                                :label="__('Texto Alternativo (alt)')"
                                :value="old('alt_text')"
                                type="text"
                                placeholder="Ex: Promocao Black Friday com ate 50% de desconto"
                                description="Descricao da imagem para acessibilidade e SEO"
                            />
                        </div>

                        {{-- Display Period --}}
                        <div class="space-y-4 border-t border-neutral-800 pt-6">
                            <flux:heading size="sm">{{ __('Periodo de Exibicao') }}</flux:heading>

                            <div class="grid grid-cols-2 gap-4">
                                {{-- Starts At --}}
                                <flux:input
                                    name="starts_at"
                                    :label="__('Data de Inicio')"
                                    :value="old('starts_at')"
                                    type="datetime-local"
                                    description="Deixe vazio para exibir imediatamente"
                                />

                                {{-- Ends At --}}
                                <flux:input
                                    name="ends_at"
                                    :label="__('Data de Fim')"
                                    :value="old('ends_at')"
                                    type="datetime-local"
                                    description="Deixe vazio para exibir indefinidamente"
                                />
                            </div>
                        </div>

                        {{-- Status --}}
                        <div class="border-t border-neutral-800 pt-6">
                            <flux:checkbox
                                name="is_active"
                                value="1"
                                :checked="old('is_active', true)"
                                label="{{ __('Banner ativo') }}"
                                description="{{ __('O banner ficara disponivel no carousel quando estiver dentro do periodo de exibicao') }}"
                            />
                        </div>

                        {{-- Help --}}
                        <div class="rounded-lg border border-neutral-700 bg-neutral-800/50 p-4">
                            <flux:heading size="sm" class="mb-2">{{ __('Dicas') }}</flux:heading>
                            <ul class="text-sm text-neutral-400 space-y-1">
                                <li><strong class="text-neutral-300">{{ __('Imagem Desktop') }}:</strong> {{ __('Sera redimensionada para 1920px (large) e 1024px (medium)') }}</li>
                                <li><strong class="text-neutral-300">{{ __('Imagem Mobile') }}:</strong> {{ __('Sera redimensionada para 1024px (large) e 768px (medium)') }}</li>
                                <li><strong class="text-neutral-300">{{ __('Links Internos') }}:</strong> {{ __('Comece com / para links dentro da loja') }}</li>
                                <li><strong class="text-neutral-300">{{ __('Links Externos') }}:</strong> {{ __('Use URL completa com https://') }}</li>
                                <li><strong class="text-neutral-300">{{ __('Periodo') }}:</strong> {{ __('Use as datas para agendar campanhas ou deixe vazio para exibicao imediata') }}</li>
                            </ul>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-800">
                            <a href="{{ route('admin.banners.index') }}">
                                <flux:button variant="ghost">
                                    {{ __('Cancelar') }}
                                </flux:button>
                            </a>
                            <flux:button variant="primary" type="submit">
                                {{ __('Criar Banner') }}
                            </flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
