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
                        <flux:heading size="xl">{{ __('Editar Banner') }}</flux:heading>
                        <flux:subheading>{{ $banner->title }}</flux:subheading>
                    </div>
                </div>

                {{-- Banner Status --}}
                <div class="mb-6 rounded-lg border border-neutral-800 bg-neutral-900 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-neutral-400">{{ __('Posicao') }}</p>
                            <p class="text-2xl font-bold text-white">{{ $banner->position }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-neutral-400">{{ __('Criado em') }}</p>
                            <p class="text-sm text-neutral-300">{{ $banner->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-neutral-400">{{ __('Status') }}</p>
                            @if (!$banner->is_active)
                                <flux:badge color="zinc">{{ __('Inativo') }}</flux:badge>
                            @else
                                <flux:badge :color="$banner->getPeriodStatusColor()">{{ __($banner->getPeriodStatusLabel()) }}</flux:badge>
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
                    <form method="POST" action="{{ route('admin.banners.update', $banner) }}" enctype="multipart/form-data" class="flex flex-col gap-6">
                        @csrf
                        @method('PUT')

                        {{-- Basic Info --}}
                        <div class="space-y-4">
                            <flux:heading size="sm">{{ __('Identificacao') }}</flux:heading>

                            {{-- Title --}}
                            <flux:input
                                name="title"
                                :label="__('Titulo')"
                                :value="old('title', $banner->title)"
                                type="text"
                                required
                                autofocus
                                placeholder="Ex: Black Friday 2024"
                                description="Usado internamente para identificacao (nao exibido no storefront)"
                            />
                        </div>

                        {{-- Current Desktop Image --}}
                        <div class="space-y-4 border-t border-neutral-800 pt-6">
                            <flux:heading size="sm">{{ __('Imagem Desktop') }}</flux:heading>

                            <div class="mb-4">
                                <p class="text-sm text-neutral-400 mb-2">{{ __('Imagem atual:') }}</p>
                                <img
                                    src="{{ Storage::disk('banners')->url(str_replace('banners/', '', str_replace('/large/', '/medium/', $banner->image_desktop))) }}"
                                    alt="{{ $banner->alt_text ?? $banner->title }}"
                                    class="h-24 w-48 object-cover rounded border border-neutral-700"
                                >
                            </div>

                            <div class="space-y-2">
                                <flux:input
                                    name="image_desktop"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                />
                                <p class="text-sm text-neutral-400">
                                    {{ __('Formatos aceitos: JPG, PNG, WebP. Tamanho maximo: 2MB.') }}<br>
                                    {{ __('Dimensao recomendada: 1920x500 pixels.') }}<br>
                                    {{ __('Deixe vazio para manter a imagem atual.') }}
                                </p>
                            </div>
                        </div>

                        {{-- Current Mobile Image --}}
                        <div class="space-y-4 border-t border-neutral-800 pt-6">
                            <flux:heading size="sm">{{ __('Imagem Mobile (opcional)') }}</flux:heading>

                            @if ($banner->image_mobile)
                                <div class="mb-4">
                                    <p class="text-sm text-neutral-400 mb-2">{{ __('Imagem atual:') }}</p>
                                    <img
                                        src="{{ Storage::disk('banners')->url(str_replace('banners/', '', str_replace('/large/', '/medium/', $banner->image_mobile))) }}"
                                        alt="{{ $banner->alt_text ?? $banner->title }}"
                                        class="h-24 w-32 object-cover rounded border border-neutral-700"
                                    >
                                </div>
                            @else
                                <p class="text-sm text-neutral-500 mb-4">{{ __('Nenhuma imagem mobile definida. A imagem desktop sera usada.') }}</p>
                            @endif

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
                                :value="old('link', $banner->link)"
                                type="text"
                                placeholder="Ex: /categoria/promocoes ou https://example.com"
                                description="URL interna (/exemplo) ou externa (https://...)"
                            />

                            {{-- Alt Text --}}
                            <flux:input
                                name="alt_text"
                                :label="__('Texto Alternativo (alt)')"
                                :value="old('alt_text', $banner->alt_text)"
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
                                    :value="old('starts_at', $banner->starts_at?->format('Y-m-d\TH:i'))"
                                    type="datetime-local"
                                    description="Deixe vazio para exibir imediatamente"
                                />

                                {{-- Ends At --}}
                                <flux:input
                                    name="ends_at"
                                    :label="__('Data de Fim')"
                                    :value="old('ends_at', $banner->ends_at?->format('Y-m-d\TH:i'))"
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
                                :checked="old('is_active', $banner->is_active)"
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
                                {{ __('Salvar Alteracoes') }}
                            </flux:button>
                        </div>
                    </form>
                </div>

                {{-- Danger Zone --}}
                <div class="mt-6 rounded-lg border border-red-900/50 bg-red-950/20 p-6">
                    <flux:heading size="sm" class="text-red-400 mb-4">{{ __('Zona de Perigo') }}</flux:heading>
                    <p class="text-sm text-neutral-400 mb-4">{{ __('Uma vez excluido, este banner nao pode ser recuperado. As imagens associadas tambem serao removidas.') }}</p>
                    <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" onsubmit="return confirm('Tem certeza que deseja excluir este banner? Esta acao nao pode ser desfeita.')">
                        @csrf
                        @method('DELETE')
                        <flux:button variant="danger" type="submit">
                            <flux:icon name="trash" class="size-4 mr-1" />
                            {{ __('Excluir Banner') }}
                        </flux:button>
                    </form>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
