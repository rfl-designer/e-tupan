@php
    $testimonials = config('institutional.testimonials');
@endphp

<x-institutional.section variant="cream">
    <div class="mx-auto mb-16 max-w-3xl text-center">
        <x-institutional.reveal>
            <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Parcerias de Longo Prazo</span>
            <h2 class="text-3xl font-medium text-neutral-strong md:text-4xl">Quem Conhece, Confia</h2>
        </x-institutional.reveal>
    </div>

    <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
        @foreach($testimonials as $index => $testimonial)
            <x-institutional.reveal :delay="$index * 100">
                <x-institutional.card variant="standard" class="relative h-full pt-12">
                    <div class="absolute left-6 top-6 text-primary/20">
                        <flux:icon name="chat-bubble-left" class="size-12" />
                    </div>
                    <p class="mb-8 italic leading-relaxed text-neutral-medium">"{{ $testimonial['content'] }}"</p>
                    <div class="mt-auto border-t border-neutral-border pt-4">
                        <p class="font-bold text-neutral-strong">{{ $testimonial['author'] }}</p>
                        <p class="mt-1 text-xs font-bold uppercase text-primary">{{ $testimonial['role'] }}</p>
                        <p class="text-xs text-neutral-light">{{ $testimonial['institution'] }}</p>
                    </div>
                </x-institutional.card>
            </x-institutional.reveal>
        @endforeach
    </div>
</x-institutional.section>
