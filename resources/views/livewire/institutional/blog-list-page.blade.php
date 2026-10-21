@php
    $posts = $posts ?? [];
@endphp

<div>
    <div class="relative overflow-hidden bg-bg-dark pb-20 pt-40 text-white">
        <div class="absolute right-0 top-0 h-full w-1/2 rounded-l-[100px] bg-primary-strong opacity-10"></div>
        <div class="absolute bottom-0 left-0 h-32 w-32 rounded-tr-full bg-secondary opacity-20"></div>

        <div class="container mx-auto max-w-7xl px-6 relative z-10">
            <x-institutional.reveal>
                <span class="mb-4 block text-xs font-bold uppercase tracking-wider text-primary-light">Conhecimento Técnico</span>
                <h1 class="mb-6 text-4xl font-medium md:text-5xl lg:text-6xl">
                    Informação Confiável <br />
                    <span class="text-primary-light">em Saúde</span>
                </h1>
                <p class="max-w-2xl text-xl leading-relaxed text-neutral-light">
                    Conteúdo técnico e atualizado sobre inovações em saúde, boas práticas e tecnologias que impactam o cuidado ao paciente. Em um mundo onde sobra discurso e falta verdade, oferecemos informação confiável.
                </p>
            </x-institutional.reveal>
        </div>
    </div>

    <x-institutional.section variant="light">
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
            @foreach($posts as $index => $post)
                <x-institutional.reveal :delay="$index * 100">
                    <a href="{{ route('blog.show', $post['id']) }}" class="group block h-full">
                        <x-institutional.card class="flex h-full flex-col overflow-hidden hover:shadow-[0_4px_16px_rgba(28,37,65,0.12)] border-transparent" padding="none" variant="standard">
                            <div class="relative h-48 overflow-hidden">
                                <img src="{{ $post['image'] }}" srcset="{{ preg_replace('/w=\d+/', 'w=480', $post['image']) }} 480w, {{ $post['image'] }} 800w" sizes="(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw" width="800" height="192" loading="lazy" decoding="async" alt="{{ $post['title'] }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                                <div class="absolute left-4 top-4 whitespace-nowrap rounded-full bg-white/90 px-3 py-1 text-xs font-bold uppercase tracking-wide text-primary-strong">
                                    {{ $post['category'] }}
                                </div>
                            </div>
                            <div class="flex flex-1 flex-col p-6">
                                <div class="mb-3 flex items-center gap-4 text-xs text-neutral-medium">
                                    <div class="flex items-center gap-1">
                                        <flux:icon name="calendar" class="size-4 text-primary-strong" />
                                        {{ $post['date'] }}
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <flux:icon name="clock" class="size-4 text-primary-strong" />
                                        {{ $post['read_time'] }}
                                    </div>
                                </div>
                                <h3 class="mb-3 line-clamp-3 text-xl font-bold text-neutral-strong transition-colors group-hover:text-primary-strong">
                                    {{ $post['title'] }}
                                </h3>
                                <p class="mb-6 line-clamp-3 flex-grow text-sm text-neutral-medium">
                                    {{ $post['excerpt'] }}
                                </p>
                                <div class="flex items-center text-sm font-medium text-primary-strong group-hover:underline">
                                    Ler artigo completo
                                    <flux:icon name="arrow-right" class="ml-2 size-4" />
                                </div>
                            </div>
                        </x-institutional.card>
                    </a>
                </x-institutional.reveal>
            @endforeach
        </div>
    </x-institutional.section>
</div>
