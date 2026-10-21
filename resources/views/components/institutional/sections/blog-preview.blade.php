@php
    $posts = array_slice(config('institutional.blog_posts'), 0, 3);
@endphp

<x-institutional.section variant="light">
    <div class="mb-12 flex flex-col items-end justify-between gap-6 md:flex-row">
        <x-institutional.reveal>
            <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Conhecimento técnico</span>
            <h2 class="text-3xl font-medium text-neutral-strong md:text-4xl">Informação confiável em saúde</h2>
        </x-institutional.reveal>
        <x-institutional.reveal :delay="100">
            <a href="{{ route('blog.index') }}" class="inline-flex items-center justify-center rounded-[999px] whitespace-nowrap border-2 border-primary-strong bg-transparent px-6 py-2 text-sm font-semibold text-primary-strong transition hover:bg-primary-bg hover:text-primary-strong-hover">
                Acessar todo o conteúdo
            </a>
        </x-institutional.reveal>
    </div>

    <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3 md:[&>*:last-child:nth-child(odd)]:col-span-2 lg:[&>*:last-child:nth-child(odd)]:col-span-1">
        @foreach($posts as $index => $post)
            <x-institutional.reveal :delay="$index * 100">
                <a href="{{ route('blog.show', $post['id']) }}" class="group block h-full">
                    <x-institutional.card class="flex h-full flex-col overflow-hidden" padding="none" variant="standard">
                        <div class="relative h-48 overflow-hidden">
                            <img src="{{ $post['image'] }}" alt="{{ $post['title'] }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                            <div class="absolute left-4 top-4 whitespace-nowrap rounded-full bg-white/90 px-3 py-1 text-xs font-bold uppercase tracking-wide text-primary-strong">
                                {{ $post['category'] }}
                            </div>
                        </div>
                        <div class="flex flex-1 flex-col p-6">
                            <div class="mb-3 flex items-center gap-2 text-xs text-neutral-medium">
                                <flux:icon name="calendar" class="size-4 text-primary-strong" />
                                {{ $post['date'] }}
                            </div>
                            <h3 class="mb-3 line-clamp-3 text-lg font-bold text-neutral-strong transition-colors group-hover:text-primary-strong">
                                {{ $post['title'] }}
                            </h3>
                            <div class="mb-6 flex-grow">
                                <p class="line-clamp-3 text-sm text-neutral-medium">
                                    {{ $post['excerpt'] }}
                                </p>
                            </div>
                            <div class="flex items-center text-sm font-medium text-primary-strong group-hover:underline">
                                Ler artigo
                                <flux:icon name="arrow-right" class="ml-2 size-4" />
                            </div>
                        </div>
                    </x-institutional.card>
                </a>
            </x-institutional.reveal>
        @endforeach
    </div>
</x-institutional.section>
