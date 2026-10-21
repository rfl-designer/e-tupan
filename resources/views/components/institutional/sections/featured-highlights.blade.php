@php
    $highlights = config('institutional.featured_highlights');
@endphp

<x-institutional.section variant="light">
    <div class="mb-12 flex flex-col items-end justify-between gap-6 md:flex-row">
        <x-institutional.reveal>
            <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Soluções em Destaque</span>
            <h2 class="text-3xl font-medium text-neutral-strong md:text-4xl">Tecnologia com Respaldo Técnico</h2>
        </x-institutional.reveal>
        <x-institutional.reveal :delay="100">
            <a href="{{ route('contact') }}" class="inline-flex items-center rounded-[999px] px-3 py-2 text-primary-strong transition-colors hover:bg-primary-bg">
                Consultar catálogo técnico
                <flux:icon name="arrow-right" class="ml-2 size-4" />
            </a>
        </x-institutional.reveal>
    </div>

    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($highlights as $index => $item)
            <x-institutional.reveal :delay="$index * 100">
                <div class="group relative aspect-[4/5] cursor-pointer overflow-hidden rounded-2xl bg-neutral-strong">
                    @php
                        $isRemote = str_starts_with($item['image'], 'http');
                        $imageSrcset = $isRemote
                            ? preg_replace('/w=\d+/', 'w=400', $item['image']) . ' 400w, ' . $item['image'] . ' 800w'
                            : null;
                    @endphp
                    <img src="{{ $isRemote ? $item['image'] : asset($item['image']) }}" @if($imageSrcset) srcset="{{ $imageSrcset }}" sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw" @endif width="400" height="500" alt="{{ $item['title'] }}" loading="lazy" decoding="async" class="h-full w-full object-cover transition-transform duration-700 motion-reduce:transition-none motion-safe:group-hover:scale-110" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-90"></div>

                    <div class="absolute left-4 top-4">
                        <span class="rounded-full bg-primary-strong px-3 py-1 text-xs font-bold uppercase tracking-wider text-white">
                            {{ $item['category'] }}
                        </span>
                    </div>

                    <div class="absolute bottom-0 left-0 w-full p-6">
                        <h3 class="mb-2 text-xl font-bold leading-tight text-white transition-colors group-hover:text-primary-light">
                            {{ $item['title'] }}
                        </h3>
                        <div class="h-1 w-12 rounded-full bg-primary-strong transition-all duration-300 group-hover:w-24"></div>
                    </div>
                </div>
            </x-institutional.reveal>
        @endforeach
    </div>
</x-institutional.section>
