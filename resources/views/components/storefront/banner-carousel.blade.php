@props([
    'banners' => [],
])

@if (count($banners) > 0)
    <section
        x-data="bannerCarousel({{ count($banners) }})"
        x-init="init()"
        class="relative"
    >
        <div
            class="relative overflow-hidden"
            x-on:mouseenter="pause()"
            x-on:mouseleave="resume()"
            x-on:touchstart.passive="handleTouchStart($event)"
            x-on:touchend.passive="handleTouchEnd($event)"
        >
            @foreach ($banners as $index => $banner)
                <div
                    x-show="activeIndex === {{ $index }}"
                    x-transition.opacity.duration.700ms
                    x-cloak
                    class="relative"
                >
                    @if ($banner['link'])
                        <a
                            href="{{ $banner['link'] }}"
                            @if ($banner['is_external'])
                                target="_blank" rel="noopener noreferrer"
                            @endif
                            class="block cursor-pointer"
                        >
                    @endif

                    <div class="relative w-full overflow-hidden rounded-none lg:rounded-2xl">
                        <div class="aspect-[16/9] sm:aspect-[2/1] lg:aspect-[192/50] lg:max-h-[500px]">
                            <picture>
                                <source
                                    media="(max-width: 1023px)"
                                    srcset="{{ $banner['mobile']['medium'] }} 768w, {{ $banner['mobile']['large'] }} 1024w"
                                    sizes="100vw"
                                >
                                <source
                                    media="(min-width: 1024px)"
                                    srcset="{{ $banner['desktop']['medium'] }} 1024w, {{ $banner['desktop']['large'] }} 1920w"
                                    sizes="100vw"
                                >
                                <img
                                    src="{{ $banner['desktop']['large'] }}"
                                    alt="{{ $banner['alt'] }}"
                                    class="size-full object-cover"
                                    @if ($index === 0)
                                        loading="eager"
                                        fetchpriority="high"
                                    @else
                                        loading="lazy"
                                    @endif
                                >
                            </picture>
                        </div>
                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-black/30 via-black/10 to-transparent"></div>
                    </div>

                    @if ($banner['link'])
                        </a>
                    @endif

                    @if ($banner['link'] === null)
                        <div class="absolute inset-0 cursor-default"></div>
                    @endif
                </div>
            @endforeach

            {{-- Navigation Arrows --}}
            @if (count($banners) > 1)
                <flux:button
                    variant="ghost"
                    type="button"
                    class="absolute left-3 top-1/2 -translate-y-1/2 bg-black/40 text-white hover:bg-black/60"
                    x-on:click="prev()"
                    aria-label="{{ __('Banner anterior') }}"
                >
                    <flux:icon name="chevron-left" class="size-5" />
                </flux:button>

                <flux:button
                    variant="ghost"
                    type="button"
                    class="absolute right-3 top-1/2 -translate-y-1/2 bg-black/40 text-white hover:bg-black/60"
                    x-on:click="next()"
                    aria-label="{{ __('Proximo banner') }}"
                >
                    <flux:icon name="chevron-right" class="size-5" />
                </flux:button>
            @endif
        </div>

        {{-- Dots --}}
        @if (count($banners) > 1)
            <div class="mt-4 flex items-center justify-center gap-2">
                @foreach ($banners as $index => $banner)
                    <button
                        type="button"
                        class="size-2 rounded-full transition-all"
                        :class="activeIndex === {{ $index }} ? 'bg-zinc-900 dark:bg-white w-6' : 'bg-zinc-400/50 dark:bg-zinc-600'"
                        x-on:click="goTo({{ $index }})"
                        aria-label="{{ __('Ir para o banner') }} {{ $index + 1 }}"
                    ></button>
                @endforeach
            </div>
        @endif
    </section>

    <script>
        function bannerCarousel(total) {
            return {
                activeIndex: 0,
                total,
                interval: null,
                paused: false,
                touchStartX: null,
                touchEndX: null,

                init() {
                    if (this.total <= 1) return;

                    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                        return;
                    }

                    this.startAutoplay();
                },

                startAutoplay() {
                    this.interval = setInterval(() => {
                        if (!this.paused) {
                            this.next();
                        }
                    }, 5000);
                },

                pause() {
                    this.paused = true;
                },

                resume() {
                    this.paused = false;
                },

                next() {
                    this.activeIndex = (this.activeIndex + 1) % this.total;
                },

                prev() {
                    this.activeIndex = (this.activeIndex - 1 + this.total) % this.total;
                },

                goTo(index) {
                    this.activeIndex = index;
                },

                handleTouchStart(event) {
                    this.pause();
                    this.touchStartX = event.changedTouches[0].screenX;
                },

                handleTouchEnd(event) {
                    this.touchEndX = event.changedTouches[0].screenX;
                    const delta = this.touchStartX - this.touchEndX;

                    if (Math.abs(delta) > 50) {
                        if (delta > 0) {
                            this.next();
                        } else {
                            this.prev();
                        }
                    }

                    this.resume();
                },
            };
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
@endif
