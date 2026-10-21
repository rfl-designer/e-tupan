@php
    $post = $post ?? [];
    $ctaRoute = $post['cta_route'] ?? ['institutional.home', [], 'solucoes'];
    $ctaHref = route($ctaRoute[0], $ctaRoute[1] ?? []) . (isset($ctaRoute[2]) ? '#' . $ctaRoute[2] : '');
    $shareUrl = url()->current();
    $shareTitle = (string) ($post['title'] ?? 'Artigo TUPAN');
    $related = collect(config('institutional.blog_posts'))
        ->where('id', '!=', $post['id'] ?? '')
        ->take(3)
        ->values();
@endphp

<div>
    <div class="h-20 bg-white"></div>

    <article>
        <div class="bg-bg-light pb-12 pt-12">
            <div class="container mx-auto max-w-4xl px-6">
                <x-institutional.reveal>
                    <a href="{{ route('blog.index') }}" class="mb-8 inline-flex items-center gap-2 text-neutral-medium hover:text-primary-strong">
                        <flux:icon name="arrow-left" class="size-5" />
                        Voltar para conhecimento
                    </a>

                    <div class="mb-6 flex flex-wrap gap-4 text-sm font-medium text-primary-strong">
                        <span class="rounded-full border border-primary-light bg-primary-bg px-3 py-1">
                            {{ $post['category'] ?? 'Blog' }}
                        </span>
                    </div>

                    <h1 class="mb-6 text-3xl font-medium leading-tight text-neutral-strong md:text-4xl lg:text-5xl">
                        {{ $post['title'] ?? 'Artigo' }}
                    </h1>

                    <div class="flex flex-wrap items-center gap-6 border-b border-neutral-border pb-8 text-sm text-neutral-medium">
                        <div class="flex items-center gap-2">
                            <flux:icon name="user" class="size-4 text-primary-strong" />
                            <span>{{ $post['author'] ?? '' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:icon name="calendar" class="size-4 text-primary-strong" />
                            <span>{{ $post['date'] ?? '' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:icon name="clock" class="size-4 text-primary-strong" />
                            <span>{{ $post['read_time'] ?? '' }}</span>
                        </div>
                    </div>
                </x-institutional.reveal>
            </div>
        </div>

        <div class="container mx-auto max-w-5xl px-6 -mt-4">
            <x-institutional.reveal :delay="100">
                <div class="aspect-video overflow-hidden rounded-2xl shadow-xl md:aspect-[21/9]">
                    <img src="{{ $post['image'] ?? '' }}" width="1200" height="514" decoding="async" fetchpriority="high" alt="Equipe técnica operando equipamentos em laboratório" class="h-full w-full object-cover" />
                </div>
            </x-institutional.reveal>
        </div>

        <x-institutional.section variant="white" class="pt-16 pb-24">
            <div class="grid gap-12 lg:grid-cols-12">
                <div class="sticky top-32 hidden h-fit flex-col items-center gap-4 lg:col-span-2 lg:flex">
                    <span class="mb-2 text-xs font-bold uppercase tracking-wider text-neutral-medium">Compartilhar</span>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($shareUrl) }}" target="_blank" rel="noopener noreferrer" aria-label="Compartilhar no LinkedIn" class="flex h-10 w-10 items-center justify-center rounded-full border border-neutral-border text-neutral-medium transition-colors hover:border-primary-strong hover:bg-primary-strong hover:text-white">
                        <flux:icon name="share" class="size-4" />
                    </a>
                    <a href="https://wa.me/?text={{ urlencode($shareTitle . ' ' . $shareUrl) }}" target="_blank" rel="noopener noreferrer" aria-label="Compartilhar no WhatsApp" class="flex h-10 w-10 items-center justify-center rounded-full border border-neutral-border text-neutral-medium transition-colors hover:border-primary-strong hover:bg-primary-strong hover:text-white">
                        <flux:icon name="chat-bubble-left" class="size-4" />
                    </a>
                    <button
                        type="button"
                        x-data="{ copied: false }"
                        x-on:click="navigator.clipboard.writeText(@js($shareUrl)).then(() => { copied = true; setTimeout(() => copied = false, 2500); })"
                        aria-label="Copiar link do artigo"
                        class="relative flex h-10 w-10 items-center justify-center rounded-full border border-neutral-border text-neutral-medium transition-colors hover:border-primary-strong hover:bg-primary-strong hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong focus-visible:ring-offset-2"
                    >
                        <flux:icon name="link" class="size-4" />
                        <span class="sr-only" role="status" aria-live="polite" x-text="copied ? 'Link copiado' : ''"></span>
                        <span x-show="copied" x-cloak class="absolute left-full top-1/2 ml-2 -translate-y-1/2 whitespace-nowrap rounded bg-neutral-strong px-2 py-1 text-xs text-white" aria-hidden="true">Link copiado</span>
                    </button>
                    <a href="mailto:?subject={{ rawurlencode($shareTitle) }}&body={{ rawurlencode($shareUrl) }}" aria-label="Compartilhar por e-mail" class="flex h-10 w-10 items-center justify-center rounded-full border border-neutral-border text-neutral-medium transition-colors hover:border-primary-strong hover:bg-primary-strong hover:text-white">
                        <flux:icon name="paper-airplane" class="size-4" />
                    </a>
                </div>

                <div class="mx-auto max-w-3xl lg:col-span-8">
                    <x-institutional.reveal :delay="200">
                        <div class="article-body text-lg text-neutral-medium">
                            {!! $post['content'] ?? '' !!}
                        </div>
                    </x-institutional.reveal>

                    @if(!empty($post['tags']))
                        <p class="mt-16 border-t border-neutral-border pt-8 text-sm text-neutral-medium">
                            <span class="font-semibold">Tags:</span>
                            {{ implode(' · ', $post['tags']) }}
                        </p>
                    @endif
                    <aside aria-labelledby="post-cta-title" class="mt-12 rounded-2xl border border-neutral-border bg-bg-light p-8">
                        <p id="post-cta-title" class="mb-4 text-xl font-semibold text-neutral-strong">{{ $post['cta_title'] ?? 'Quer um parceiro técnico para sua instituição?' }}</p>
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <a href="{{ route('contact') }}" data-cta="blog-post-contato" class="inline-flex items-center justify-center rounded-[999px] border border-transparent bg-primary-strong px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-strong-hover hover:shadow-md">
                                Falar com um consultor
                            </a>
                            @if(!empty($ctaHref))
                                <a href="{{ $ctaHref }}" data-cta="blog-post-{{ $post['id'] ?? 'artigo' }}-solucao" class="inline-flex items-center gap-2 py-1 text-sm font-semibold text-primary-strong underline-offset-4 hover:underline">
                                    {{ $post['cta_link_label'] ?? 'Conheça nossas soluções' }}
                                    <flux:icon name="arrow-right" class="size-4" />
                                </a>
                            @endif
                        </div>
                    </aside>

                    <div class="mt-8 flex items-center gap-3 border-t border-neutral-border pt-6 lg:hidden">
                        <span class="mr-1 text-xs font-bold uppercase tracking-wider text-neutral-medium">Compartilhar</span>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($shareUrl) }}" target="_blank" rel="noopener noreferrer" aria-label="Compartilhar no LinkedIn" class="flex h-11 w-11 items-center justify-center rounded-full border border-neutral-border text-neutral-medium transition-colors hover:border-primary-strong hover:bg-primary-strong hover:text-white">
                            <flux:icon name="share" class="size-5" />
                        </a>
                        <a href="https://wa.me/?text={{ urlencode($shareTitle . ' ' . $shareUrl) }}" target="_blank" rel="noopener noreferrer" aria-label="Compartilhar no WhatsApp" class="flex h-11 w-11 items-center justify-center rounded-full border border-neutral-border text-neutral-medium transition-colors hover:border-primary-strong hover:bg-primary-strong hover:text-white">
                            <flux:icon name="chat-bubble-left" class="size-5" />
                        </a>
                        <button
                            type="button"
                            x-data="{ copied: false }"
                            x-on:click="navigator.clipboard.writeText(@js($shareUrl)).then(() => { copied = true; setTimeout(() => copied = false, 2500); })"
                            aria-label="Copiar link do artigo"
                            class="relative flex h-11 w-11 items-center justify-center rounded-full border border-neutral-border text-neutral-medium transition-colors hover:border-primary-strong hover:bg-primary-strong hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong focus-visible:ring-offset-2"
                        >
                            <flux:icon name="link" class="size-5" />
                            <span class="sr-only" role="status" aria-live="polite" x-text="copied ? 'Link copiado' : ''"></span>
                            <span x-show="copied" x-cloak class="absolute bottom-full left-1/2 mb-2 -translate-x-1/2 whitespace-nowrap rounded bg-neutral-strong px-2 py-1 text-xs text-white" aria-hidden="true">Link copiado</span>
                        </button>
                        <a href="mailto:?subject={{ rawurlencode($shareTitle) }}&body={{ rawurlencode($shareUrl) }}" aria-label="Compartilhar por e-mail" class="flex h-11 w-11 items-center justify-center rounded-full border border-neutral-border text-neutral-medium transition-colors hover:border-primary-strong hover:bg-primary-strong hover:text-white">
                            <flux:icon name="paper-airplane" class="size-5" />
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-2"></div>
            </div>
        </x-institutional.section>
    </article>

    <x-institutional.section variant="light" class="py-16">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-2xl font-bold text-neutral-strong">Continue lendo</h2>
            <a href="{{ route('blog.index') }}" class="inline-flex w-fit items-center justify-center whitespace-nowrap rounded-[999px] border-2 border-primary-strong bg-transparent px-6 py-2 text-sm font-semibold text-primary-strong transition hover:bg-primary-bg hover:text-primary-strong-hover">
                Acessar todo o conteúdo
            </a>
        </div>
        <div @class([
            'grid gap-8 md:grid-cols-2',
            'lg:grid-cols-3' => $related->count() > 2,
        ])>
            @foreach($related as $relatedPost)
                <a href="{{ route('blog.show', $relatedPost['id']) }}" class="group block h-full">
                    <x-institutional.card class="flex h-full flex-col overflow-hidden border-transparent hover:shadow-[0_4px_16px_rgba(28,37,65,0.12)]" padding="none" variant="standard">
                        <div class="relative h-48 overflow-hidden">
                            <img src="{{ $relatedPost['image'] }}" srcset="{{ preg_replace('/w=\d+/', 'w=480', $relatedPost['image']) }} 480w, {{ $relatedPost['image'] }} 800w" sizes="(min-width: 1024px) 50vw, 100vw" width="800" height="192" loading="lazy" decoding="async" alt="" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                        </div>
                        <div class="flex flex-1 flex-col p-6">
                            <div class="mb-3 flex items-center gap-4 text-xs text-neutral-medium">
                                <span class="font-bold uppercase text-primary-strong">{{ $relatedPost['category'] }}</span>
                                <div class="flex items-center gap-1">
                                    <flux:icon name="calendar" class="size-4 text-primary-strong" />
                                    {{ $relatedPost['date'] }}
                                </div>
                            </div>
                            <h3 class="line-clamp-3 text-xl font-bold text-neutral-strong transition-colors group-hover:text-primary-strong">{{ $relatedPost['title'] }}</h3>
                        </div>
                    </x-institutional.card>
                </a>
            @endforeach
        </div>
    </x-institutional.section>
</div>
