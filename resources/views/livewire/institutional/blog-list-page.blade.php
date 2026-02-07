@php
    $posts = $posts ?? [];
@endphp

<div>
    <div class="relative overflow-hidden bg-bg-dark pb-20 pt-40 text-white">
        <div class="absolute right-0 top-0 h-full w-1/2 rounded-l-[100px] bg-primary opacity-10"></div>
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
                        <x-institutional.card class="flex h-full flex-col overflow-hidden p-0 hover:shadow-[0_4px_16px_rgba(28,37,65,0.12)] border-transparent" variant="standard">
                            <div class="relative h-48 overflow-hidden">
                                <img src="{{ $post['image'] }}" alt="{{ $post['title'] }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                                <div class="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-xs font-bold uppercase tracking-wide text-primary">
                                    {{ $post['category'] }}
                                </div>
                            </div>
                            <div class="flex flex-1 flex-col p-6">
                                <div class="mb-3 flex items-center gap-4 text-xs text-neutral-medium">
                                    <div class="flex items-center gap-1">
                                        <flux:icon name="calendar" class="size-4 text-primary" />
                                        {{ $post['date'] }}
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <flux:icon name="clock" class="size-4 text-primary" />
                                        {{ $post['read_time'] }}
                                    </div>
                                </div>
                                <h3 class="mb-3 line-clamp-2 text-xl font-bold text-neutral-strong transition-colors group-hover:text-primary">
                                    {{ $post['title'] }}
                                </h3>
                                <p class="mb-6 line-clamp-3 flex-grow text-sm text-neutral-medium">
                                    {{ $post['excerpt'] }}
                                </p>
                                <div class="flex items-center text-sm font-medium text-primary group-hover:underline">
                                    Ler artigo completo
                                    <flux:icon name="arrow-right" class="ml-2 size-4" />
                                </div>
                            </div>
                        </x-institutional.card>
                    </a>
                </x-institutional.reveal>
            @endforeach
        </div>

        <div class="mt-20">
            <x-institutional.reveal>
                <div class="relative overflow-hidden rounded-3xl bg-primary p-8 text-center text-white md:p-12">
                    <div class="absolute left-0 top-0 h-full w-full bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
                    <div class="relative z-10 mx-auto max-w-2xl">
                        <h3 class="mb-4 text-2xl font-bold md:text-3xl">Receba Conhecimento Técnico da TUPAN</h3>
                        <p class="mb-8 text-primary-bg">
                            Inscreva-se para receber conteúdo técnico, novidades sobre produtos e informações relevantes para quem atua em saúde.
                        </p>
                        <div class="flex flex-col justify-center gap-4 sm:flex-row">
                            <input
                                type="email"
                                placeholder="Seu e-mail profissional"
                                class="h-12 w-full min-w-[300px] rounded-[999px] px-6 text-neutral-strong outline-none focus:ring-2 focus:ring-primary-light sm:w-auto"
                            />
                            <button class="inline-flex items-center justify-center rounded-[999px] border border-transparent bg-secondary px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-secondary-hover hover:shadow-md">
                                Inscrever-se
                            </button>
                        </div>
                    </div>
                </div>
            </x-institutional.reveal>
        </div>
    </x-institutional.section>
</div>
