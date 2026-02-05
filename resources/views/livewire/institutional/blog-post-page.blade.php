@php
    $post = $post ?? [];
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
                    <a href="{{ route('blog.index') }}" class="mb-8 inline-flex items-center gap-2 text-neutral-medium hover:text-primary">
                        <flux:icon name="arrow-left" class="size-5" />
                        Voltar para Conhecimento
                    </a>

                    <div class="mb-6 flex flex-wrap gap-4 text-sm font-medium text-primary">
                        <span class="rounded-full border border-primary-light bg-primary-bg px-3 py-1">
                            {{ $post['category'] ?? 'Blog' }}
                        </span>
                    </div>

                    <h1 class="mb-6 text-3xl font-bold leading-tight text-neutral-strong md:text-4xl lg:text-5xl">
                        {{ $post['title'] ?? 'Artigo' }}
                    </h1>

                    <div class="flex flex-wrap items-center gap-6 border-b border-neutral-border pb-8 text-sm text-neutral-medium">
                        <div class="flex items-center gap-2">
                            <flux:icon name="user" class="size-4 text-primary" />
                            <span>{{ $post['author'] ?? '' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:icon name="calendar" class="size-4 text-primary" />
                            <span>{{ $post['date'] ?? '' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:icon name="clock" class="size-4 text-primary" />
                            <span>{{ $post['read_time'] ?? '' }}</span>
                        </div>
                    </div>
                </x-institutional.reveal>
            </div>
        </div>

        <div class="container mx-auto max-w-5xl px-6 -mt-4">
            <x-institutional.reveal :delay="100">
                <div class="aspect-video overflow-hidden rounded-2xl shadow-xl md:aspect-[21/9]">
                    <img src="{{ $post['image'] ?? '' }}" alt="{{ $post['title'] ?? 'Imagem do artigo' }}" class="h-full w-full object-cover" />
                </div>
            </x-institutional.reveal>
        </div>

        <x-institutional.section variant="white" class="pt-16 pb-24">
            <div class="grid gap-12 lg:grid-cols-12">
                <div class="sticky top-32 hidden h-fit flex-col items-center gap-4 lg:col-span-2 lg:flex">
                    <span class="mb-2 text-xs font-bold uppercase tracking-wider text-neutral-medium">Compartilhar</span>
                    <button class="flex h-10 w-10 items-center justify-center rounded-full border border-neutral-border text-neutral-medium transition-colors hover:border-primary hover:bg-primary hover:text-white">
                        <flux:icon name="link" class="size-4" />
                    </button>
                    <button class="flex h-10 w-10 items-center justify-center rounded-full border border-neutral-border text-neutral-medium transition-colors hover:border-primary hover:bg-primary hover:text-white">
                        <flux:icon name="share" class="size-4" />
                    </button>
                    <button class="flex h-10 w-10 items-center justify-center rounded-full border border-neutral-border text-neutral-medium transition-colors hover:border-primary hover:bg-primary hover:text-white">
                        <flux:icon name="paper-airplane" class="size-4" />
                    </button>
                    <button class="flex h-10 w-10 items-center justify-center rounded-full border border-neutral-border text-neutral-medium transition-colors hover:border-primary hover:bg-primary hover:text-white">
                        <flux:icon name="chat-bubble-left" class="size-4" />
                    </button>
                </div>

                <div class="mx-auto max-w-3xl lg:col-span-8">
                    <x-institutional.reveal :delay="200">
                        <div class="prose prose-lg prose-neutral prose-headings:text-neutral-strong prose-headings:font-bold prose-p:text-neutral-medium prose-a:text-primary hover:prose-a:text-primary-hover prose-strong:text-neutral-strong max-w-none">
                            {!! $post['content'] ?? '' !!}
                        </div>
                    </x-institutional.reveal>

                    <div class="mt-16 border-t border-neutral-border pt-8">
                        <h4 class="mb-4 font-bold text-neutral-strong">Tags Relacionadas</h4>
                        <div class="flex flex-wrap gap-2">
                            <span class="cursor-pointer rounded-lg bg-bg-light px-4 py-2 text-sm text-neutral-medium transition-colors hover:bg-bg-cream">Saude</span>
                            <span class="cursor-pointer rounded-lg bg-bg-light px-4 py-2 text-sm text-neutral-medium transition-colors hover:bg-bg-cream">Conhecimento Tecnico</span>
                            <span class="cursor-pointer rounded-lg bg-bg-light px-4 py-2 text-sm text-neutral-medium transition-colors hover:bg-bg-cream">Hospitalar</span>
                            <span class="cursor-pointer rounded-lg bg-bg-light px-4 py-2 text-sm text-neutral-medium transition-colors hover:bg-bg-cream">{{ $post['category'] ?? 'Saude' }}</span>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2"></div>
            </div>
        </x-institutional.section>
    </article>

    <x-institutional.section variant="light" class="py-16">
        <div class="mb-8 flex items-center justify-between">
            <h3 class="text-2xl font-bold text-neutral-strong">Continue Lendo</h3>
            <a href="{{ route('blog.index') }}" class="inline-flex items-center justify-center rounded-[999px] border-2 border-primary bg-transparent px-6 py-2 text-sm font-semibold text-primary transition hover:bg-primary-bg hover:text-primary-hover">
                Acessar todo o conteudo
            </a>
        </div>
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($related as $relatedPost)
                <a href="{{ route('blog.show', $relatedPost['id']) }}" class="group cursor-pointer">
                    <div class="mb-4 h-48 overflow-hidden rounded-xl">
                        <img src="{{ $relatedPost['image'] }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" alt="{{ $relatedPost['title'] }}" />
                    </div>
                    <div class="mb-2 text-xs font-bold uppercase text-primary">{{ $relatedPost['category'] }}</div>
                    <h4 class="font-bold text-neutral-strong transition-colors group-hover:text-primary">{{ $relatedPost['title'] }}</h4>
                </a>
            @endforeach
        </div>
    </x-institutional.section>
</div>
