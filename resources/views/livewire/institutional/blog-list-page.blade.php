@php
    $posts = $posts ?? [];
@endphp

<div>
    <div class="relative overflow-hidden bg-bg-dark pb-20 pt-40 text-white">
        <div class="absolute right-0 top-0 h-full w-1/2 rounded-l-[100px] bg-primary-strong opacity-10"></div>
        <div class="absolute bottom-0 left-0 h-32 w-32 rounded-tr-full bg-secondary opacity-20"></div>

        <div class="container relative z-10 mx-auto grid max-w-7xl items-center gap-12 px-6 lg:grid-cols-2 lg:gap-16">
            <x-institutional.reveal>
                <span class="mb-4 block text-xs font-bold uppercase tracking-wider text-primary-light">Conhecimento técnico</span>
                <h1 class="mb-6 text-4xl font-medium md:text-5xl">
                    Informação confiável <br />
                    <span class="text-primary-light">em saúde</span>
                </h1>
                <p class="max-w-2xl text-xl leading-relaxed text-neutral-light">
                    Conteúdo técnico e atualizado sobre inovações em saúde, boas práticas e tecnologias que impactam o cuidado ao paciente. Em um mundo onde sobra discurso e falta verdade, oferecemos informação confiável.
                </p>

                <div class="mt-8 flex flex-col gap-4 sm:flex-row">
                    <a href="{{ route('contact') }}" data-cta="blog-hero-contato" class="inline-flex items-center justify-center rounded-[999px] border border-transparent bg-primary-strong px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-strong-hover hover:shadow-md">
                        Falar com um consultor
                    </a>
                </div>
            </x-institutional.reveal>

            @if(! empty($posts[0]))
                @php($featured = $posts[0])
                <x-institutional.reveal :delay="150" class="hidden lg:block">
                    <a href="{{ route('blog.show', $featured['id']) }}" data-cta="blog-hero-destaque" class="group block overflow-hidden rounded-[32px] bg-white shadow-2xl">
                        <div class="relative h-56 overflow-hidden">
                            <img src="{{ $featured['image'] }}" width="800" height="224" fetchpriority="high" decoding="async" alt="" class="h-full w-full object-cover transition-transform duration-500 motion-reduce:transition-none group-hover:scale-105 motion-reduce:group-hover:scale-100" />
                            <div class="absolute left-4 top-4 whitespace-nowrap rounded-full bg-white/90 px-3 py-1 text-xs font-bold uppercase tracking-wide text-primary-strong">
                                {{ $featured['category'] }}
                            </div>
                        </div>
                        <div class="p-6">
                            <span class="mb-2 block text-xs font-bold uppercase tracking-wider text-neutral-medium">Artigo em destaque</span>
                            <p class="text-2xl font-semibold leading-snug text-neutral-strong transition-colors group-hover:text-primary-strong">{{ $featured['title'] }}</p>
                            <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-neutral-medium">
                                <div class="flex items-center gap-1">
                                    <flux:icon name="calendar" class="size-4 text-primary-strong" />
                                    {{ $featured['date'] }}
                                </div>
                                @if(! empty($featured['read_time']))
                                    <div class="flex items-center gap-1">
                                        <flux:icon name="clock" class="size-4 text-primary-strong" />
                                        {{ $featured['read_time'] }}
                                    </div>
                                @endif
                            </div>
                            <div class="mt-4 flex items-center text-sm font-medium text-primary-strong group-hover:underline">
                                Ler artigo
                                <flux:icon name="arrow-right" class="ml-2 size-4" />
                            </div>
                        </div>
                    </a>
                </x-institutional.reveal>
            @endif
        </div>
    </div>

    <x-institutional.section variant="light">
        <h2 class="mb-8 text-3xl font-medium text-neutral-strong lg:hidden">Artigos</h2>
        @if(count($posts) > 1)
            <h2 class="mb-8 hidden text-3xl font-medium text-neutral-strong lg:block">Mais artigos</h2>
        @endif
        <div class="grid gap-8 md:grid-cols-2 {{ count($posts) > 1 ? 'lg:grid-cols-2' : 'lg:grid-cols-3' }} md:[&>*:last-child:nth-child(odd)]:col-span-2 lg:[&>*:last-child:nth-child(odd)]:col-span-1">
            @foreach($posts as $index => $post)
                <x-institutional.reveal :delay="$index * 100" class="{{ $index === 0 && count($posts) > 1 ? 'lg:hidden' : '' }}">
                    <x-institutional.article-card :post="$post" />
                </x-institutional.reveal>
            @endforeach
        </div>
    </x-institutional.section>

    <x-institutional.sections.cta :href="route('contact')" />
</div>
