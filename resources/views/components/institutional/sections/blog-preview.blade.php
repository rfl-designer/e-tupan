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
                <x-institutional.article-card :post="$post" />
            </x-institutional.reveal>
        @endforeach
    </div>
</x-institutional.section>
