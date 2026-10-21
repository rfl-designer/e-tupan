@props([
    'post',
    'sizes' => '(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw',
])

<a href="{{ route('blog.show', $post['id']) }}" class="group block h-full">
    <x-institutional.card class="flex h-full flex-col overflow-hidden border-transparent hover:shadow-[0_4px_16px_rgba(28,37,65,0.12)]" padding="none" variant="standard">
        <div class="relative h-48 overflow-hidden">
            <img src="{{ $post['image'] }}" srcset="{{ preg_replace('/w=\d+/', 'w=480', $post['image']) }} 480w, {{ $post['image'] }} 800w" sizes="{{ $sizes }}" width="800" height="192" loading="lazy" decoding="async" alt="" class="h-full w-full object-cover transition-transform duration-500 motion-reduce:transition-none group-hover:scale-105 motion-reduce:group-hover:scale-100" />
            <div class="absolute left-4 top-4 whitespace-nowrap rounded-full bg-white/90 px-3 py-1 text-xs font-bold uppercase tracking-wide text-primary-strong">
                {{ $post['category'] }}
            </div>
        </div>
        <div class="flex flex-1 flex-col p-6">
            <div class="mb-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-neutral-medium">
                <div class="flex items-center gap-1">
                    <flux:icon name="calendar" class="size-4 text-primary-strong" />
                    {{ $post['date'] }}
                </div>
                @if(! empty($post['read_time']))
                    <div class="flex items-center gap-1">
                        <flux:icon name="clock" class="size-4 text-primary-strong" />
                        {{ $post['read_time'] }}
                    </div>
                @endif
            </div>
            <h3 class="mb-3 line-clamp-3 text-xl font-bold text-neutral-strong transition-colors group-hover:text-primary-strong">
                {{ $post['title'] }}
            </h3>
            <p class="mb-6 line-clamp-3 flex-grow text-sm text-neutral-medium">
                {{ $post['excerpt'] }}
            </p>
            <div class="flex items-center text-sm font-medium text-primary-strong group-hover:underline">
                Ler artigo
                <flux:icon name="arrow-right" class="ml-2 size-4" />
            </div>
        </div>
    </x-institutional.card>
</a>
