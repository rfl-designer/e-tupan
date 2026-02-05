@php
    use App\Domain\Admin\Services\SettingsService;
    use App\Domain\Catalog\Models\Category;

    $settings = app(SettingsService::class);
    $storeName = $settings->get('general.store_name') ?: config('app.name');
    $storeLogo = $settings->get('general.store_logo');
    $categories = Category::query()->active()->root()->with('children')->orderBy('position')->get();
@endphp

<header class="sticky top-0 z-50 border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-14 items-center justify-between sm:h-16">
            {{-- Logo --}}
            <div class="flex shrink-0 items-center">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    @if($storeLogo)
                        <img
                            src="{{ Storage::url($storeLogo) }}"
                            alt="{{ $storeName }}"
                            class="h-7 w-auto sm:h-8"
                        />
                    @else
                        <span class="text-lg font-semibold text-zinc-900 sm:text-xl dark:text-white">
                            {{ $storeName }}
                        </span>
                    @endif
                </a>
            </div>

            {{-- Desktop Navigation --}}
            <nav class="hidden lg:flex lg:items-center lg:gap-6">
                @foreach($categories as $category)
                    @if($category->children->count() > 0)
                        <flux:dropdown>
                            <flux:button variant="ghost" class="text-zinc-700 dark:text-zinc-300">
                                {{ $category->name }}
                                <flux:icon name="chevron-down" class="ml-1 size-4" />
                            </flux:button>
                            <flux:menu>
                                <flux:menu.item href="{{ route('home') }}?categoria={{ $category->slug }}">
                                    Ver tudo em {{ $category->name }}
                                </flux:menu.item>
                                <flux:separator />
                                @foreach($category->children as $child)
                                    <flux:menu.item href="{{ route('home') }}?categoria={{ $child->slug }}">
                                        {{ $child->name }}
                                    </flux:menu.item>
                                @endforeach
                            </flux:menu>
                        </flux:dropdown>
                    @else
                        <a
                            href="{{ route('home') }}?categoria={{ $category->slug }}"
                            class="text-sm font-medium text-zinc-700 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white"
                        >
                            {{ $category->name }}
                        </a>
                    @endif
                @endforeach
            </nav>

            {{-- Right Actions --}}
            <div class="flex items-center gap-1 sm:gap-2">
                {{-- Search Box --}}
                <livewire:storefront.search-box />

                {{-- User Account --}}
                @auth
                    <flux:dropdown>
                        <flux:button variant="ghost" size="sm" icon="user" aria-label="Minha conta" />
                        <flux:menu>
                            <flux:menu.item href="{{ route('customer.addresses') }}" icon="map-pin">
                                Meus enderecos
                            </flux:menu.item>
                            <flux:separator />
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle">
                                    Sair
                                </flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                @else
                    <flux:button variant="ghost" size="sm" icon="user" href="{{ route('login') }}" aria-label="Entrar" />
                @endauth

                {{-- Mini Cart --}}
                <livewire:cart.mini-cart />

                {{-- Mobile Menu Button --}}
                <flux:button
                    variant="ghost"
                    size="sm"
                    icon="bars-3"
                    class="lg:hidden"
                    x-data
                    x-on:click="$dispatch('open-mobile-menu')"
                    aria-label="Menu"
                />
            </div>
        </div>
    </div>

    {{-- Mobile Menu --}}
    <div
        x-data="{ open: false }"
        x-on:open-mobile-menu.window="open = true"
        x-show="open"
        x-cloak
        class="lg:hidden"
    >
        <div
            x-show="open"
            x-on:click="open = false"
            class="fixed inset-0 z-40 bg-black/50"
        ></div>

        <div
            x-show="open"
            x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-300"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 z-50 w-full max-w-sm bg-white dark:bg-zinc-900"
        >
            <div class="flex h-14 items-center justify-between border-b border-zinc-200 px-4 sm:h-16 dark:border-zinc-700">
                <span class="text-lg font-semibold text-zinc-900 dark:text-white">Menu</span>
                <flux:button variant="ghost" size="sm" icon="x-mark" x-on:click="open = false" aria-label="Fechar" />
            </div>

            <nav class="space-y-1 overflow-y-auto p-4" style="max-height: calc(100vh - 4rem);">
                @foreach($categories as $category)
                    <div x-data="{ expanded: false }">
                        @if($category->children->count() > 0)
                            <button
                                x-on:click="expanded = !expanded"
                                class="flex w-full items-center justify-between rounded-lg px-3 py-3 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
                            >
                                {{ $category->name }}
                                <flux:icon name="chevron-down" class="size-4 transition-transform" x-bind:class="expanded && 'rotate-180'" />
                            </button>
                            <div x-show="expanded" x-collapse class="ml-4 space-y-1">
                                <a
                                    href="{{ route('home') }}?categoria={{ $category->slug }}"
                                    class="block rounded-lg px-3 py-2 text-sm text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
                                >
                                    Ver tudo
                                </a>
                                @foreach($category->children as $child)
                                    <a
                                        href="{{ route('home') }}?categoria={{ $child->slug }}"
                                        class="block rounded-lg px-3 py-2 text-sm text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
                                    >
                                        {{ $child->name }}
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <a
                                href="{{ route('home') }}?categoria={{ $category->slug }}"
                                class="block rounded-lg px-3 py-3 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
                            >
                                {{ $category->name }}
                            </a>
                        @endif
                    </div>
                @endforeach

                {{-- Mobile Auth Links --}}
                <div class="mt-6 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                    @auth
                        <a
                            href="{{ route('customer.addresses') }}"
                            class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
                        >
                            <flux:icon name="map-pin" class="size-5" />
                            Meus enderecos
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="mt-1">
                            @csrf
                            <button
                                type="submit"
                                class="flex w-full items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
                            >
                                <flux:icon name="arrow-right-start-on-rectangle" class="size-5" />
                                Sair
                            </button>
                        </form>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
                        >
                            <flux:icon name="user" class="size-5" />
                            Entrar / Cadastrar
                        </a>
                    @endauth
                </div>
            </nav>
        </div>
    </div>
</header>
