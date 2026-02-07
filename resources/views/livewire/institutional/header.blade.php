@php
    $divisions = config('institutional.divisions');
    $isDarkHeroPage = request()->routeIs('about', 'contact', 'blog.*', 'solutions.show');
@endphp

<header
    x-data="{
        isOpen: false,
        isScrolled: false,
        mobileSolutionsOpen: false,
        isDarkHero: @js($isDarkHeroPage),
        get useLightText() {
            return this.isDarkHero && !this.isScrolled && !this.isOpen;
        },
    }"
    x-init="window.addEventListener('scroll', () => { isScrolled = window.scrollY > 10; });"
    x-bind:class="isScrolled || isOpen ? 'bg-white shadow-md py-3' : 'bg-white/90 backdrop-blur-sm md:bg-transparent py-5'"
    class="fixed z-50 w-full transition-all duration-300"
>
    <div class="container mx-auto flex max-w-7xl items-center justify-between px-6">
        <a href="{{ route('institutional.home') }}" class="group relative z-50 flex items-center gap-2">
            <img
                src="{{ asset('images/logo-tupan.png') }}"
                alt="Logo TUPAN"
                class="h-10 w-auto"
            />
            <span class="sr-only">TUPAN</span>
        </a>

        <nav class="hidden items-center gap-8 md:flex">
            <a
                href="{{ route('institutional.home') }}"
                class="text-sm font-medium transition-colors"
                x-bind:class="useLightText ? 'text-white hover:text-primary-light' : 'text-neutral-strong hover:text-primary'"
            >
                Início
            </a>
            <a
                href="{{ route('about') }}"
                class="text-sm font-medium transition-colors"
                x-bind:class="useLightText ? 'text-white hover:text-primary-light' : 'text-neutral-strong hover:text-primary'"
            >
                Quem Somos
            </a>

            <div class="group static flex h-full items-center">
                <a
                    href="{{ route('institutional.home') }}#solucoes"
                    class="flex cursor-pointer items-center gap-1 py-4 text-sm font-medium transition-colors"
                    x-bind:class="useLightText ? 'text-white hover:text-primary-light' : 'text-neutral-strong hover:text-primary'"
                >
                    Soluções
                    <flux:icon name="chevron-down" class="size-3.5 transition-transform duration-200 group-hover:rotate-180" x-bind:class="useLightText ? 'text-white group-hover:text-primary-light' : ''" />
                </a>

                <div class="invisible absolute left-0 top-[100%] w-full translate-y-2 border-t border-neutral-border bg-white opacity-0 shadow-[0_4px_16px_rgba(28,37,65,0.12)] transition-all duration-200 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100">
                    <div class="container mx-auto max-w-7xl px-6 py-12">
                        <div class="grid grid-cols-4 gap-8">
                            <div class="col-span-1">
                                <h6 class="mb-4 text-xs font-bold uppercase tracking-wider text-neutral-medium">Nossas Divisões Especializadas</h6>
                                <p class="mb-6 text-sm text-neutral-light">
                                    Conhecimento técnico aplicado em cada segmento. Do laboratório ao centro cirúrgico, intermediamos segurança, técnica e cuidado.
                                </p>
                                <a href="{{ route('institutional.home') }}#solucoes" class="inline-flex items-center rounded-[999px] border-2 border-primary px-4 py-2 text-xs font-semibold text-primary transition-colors hover:bg-primary-bg hover:text-primary-hover">
                                    Conhecer todas as soluções
                                </a>
                            </div>
                            <div class="col-span-3 grid grid-cols-3 gap-6">
                                @foreach(collect($divisions)->where('id', '!=', 'equipahosp') as $division)
                                    <a
                                        href="{{ route('solutions.show', $division['id']) }}"
                                        class="group/item flex items-start gap-4 rounded-xl p-4 transition-colors hover:bg-bg-light"
                                    >
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-bg-light text-primary transition-all group-hover/item:bg-white group-hover/item:shadow-sm">
                                            <flux:icon name="sparkles" class="size-5" />
                                        </div>
                                        <div>
                                            <h5 class="font-semibold text-neutral-strong transition-colors group-hover/item:text-primary">{{ $division['title'] }}</h5>
                                            <p class="mt-1 line-clamp-2 text-xs text-neutral-medium">{{ $division['description'] }}</p>
                                        </div>
                                    </a>
                                @endforeach
                                <a
                                    href="{{ route('solutions.show', 'equipahosp') }}"
                                    class="group/item flex items-start gap-4 rounded-xl bg-neutral-strong p-4 text-white transition-colors hover:bg-[#263354]"
                                >
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-primary-light">
                                        <flux:icon name="wrench" class="size-5" />
                                    </div>
                                    <div>
                                        <h5 class="font-semibold text-white">EquipaHosp</h5>
                                        <p class="mt-1 text-xs text-neutral-light">Engenharia Clínica e Assistência Técnica Especializada</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <a
                href="{{ route('blog.index') }}"
                class="text-sm font-medium transition-colors"
                x-bind:class="useLightText ? 'text-white hover:text-primary-light' : 'text-neutral-strong hover:text-primary'"
            >
                Conhecimento
            </a>

            <a href="{{ route('contact') }}" class="rounded-[999px] border border-transparent bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-hover hover:shadow-md">
                Fale com um Consultor
            </a>
        </nav>

        <button class="relative z-50 md:hidden" x-bind:class="useLightText ? 'text-white' : 'text-neutral-strong hover:text-primary'" x-on:click="isOpen = !isOpen">
            <flux:icon x-show="!isOpen" name="bars-3" class="size-7" />
            <flux:icon x-show="isOpen" name="x-mark" class="size-7 text-neutral-strong" />
        </button>
    </div>

    <div
        x-show="isOpen"
        x-transition
        class="absolute left-0 top-0 flex h-screen w-full animate-in flex-col gap-2 overflow-y-auto bg-white p-6 pt-24 shadow-lg md:hidden"
    >
        <a href="{{ route('institutional.home') }}" class="border-b border-neutral-light/20 py-3 text-lg font-medium text-neutral-strong hover:text-primary">
            Início
        </a>
        <a href="{{ route('about') }}" class="border-b border-neutral-light/20 py-3 text-lg font-medium text-neutral-strong hover:text-primary">
            Quem Somos
        </a>

        <div class="border-b border-neutral-light/20">
            <button class="flex w-full items-center justify-between py-3 text-lg font-medium text-neutral-strong hover:text-primary" x-on:click="mobileSolutionsOpen = !mobileSolutionsOpen">
                Soluções
                <flux:icon name="chevron-down" class="size-5 transition-transform duration-200" x-bind:class="mobileSolutionsOpen ? 'rotate-180' : ''" />
            </button>
            <div x-show="mobileSolutionsOpen" x-transition class="mb-2 space-y-3 rounded-lg bg-bg-light/50 pb-4 pl-4">
                @foreach(collect($divisions)->where('id', '!=', 'equipahosp') as $division)
                    <a href="{{ route('solutions.show', $division['id']) }}" class="flex items-center gap-3 py-2 text-sm text-neutral-medium hover:text-primary">
                        <flux:icon name="sparkles" class="size-4" />
                        {{ $division['title'] }}
                    </a>
                @endforeach
                <a href="{{ route('solutions.show', 'equipahosp') }}" class="flex items-center gap-3 py-2 text-sm font-semibold text-primary">
                    <flux:icon name="wrench" class="size-4" />
                    EquipaHosp
                </a>
            </div>
        </div>

        <a href="{{ route('blog.index') }}" class="border-b border-neutral-light/20 py-3 text-lg font-medium text-neutral-strong hover:text-primary">
            Conhecimento
        </a>

        <div class="mt-4">
            <a href="{{ route('contact') }}" class="inline-flex w-full items-center justify-center rounded-[999px] border border-transparent bg-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-hover hover:shadow-md">
                Fale com um Consultor
            </a>
        </div>
    </div>
</header>
