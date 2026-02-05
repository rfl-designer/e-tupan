<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.admin-head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        {{-- Sidebar: collapsible on mobile and tablet (below lg breakpoint) --}}
        <flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 lg:w-64">
            <flux:sidebar.header>
                <flux:sidebar.brand
                    :href="route('admin.dashboard')"
                    logo="{{ asset('img/logo.png') }}"
                    logo:dark="{{ asset('img/logo-dark.png') }}"
                    :name="config('app.name')"
                    class="px-2"
                />
                <flux:sidebar.collapse class="touch-target lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Principal')" class="grid">
                    <flux:sidebar.item
                        icon="home"
                        :href="route('admin.dashboard')"
                        :current="request()->routeIs('admin.dashboard')"
                    >
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="shopping-bag"
                        :href="route('admin.orders.index')"
                        :current="request()->routeIs('admin.orders.*')"
                        :badge="$pendingOrdersCount ?? null"
                    >
                        {{ __('Pedidos') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Catalogo')" class="grid">
                    <flux:sidebar.item
                        icon="cube"
                        :href="route('admin.products.index')"
                        :current="request()->routeIs('admin.products.*')"
                    >
                        {{ __('Produtos') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="folder"
                        :href="route('admin.categories.index')"
                        :current="request()->routeIs('admin.categories.*')"
                    >
                        {{ __('Categorias') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="tag"
                        :href="route('admin.attributes.index')"
                        :current="request()->routeIs('admin.attributes.*')"
                    >
                        {{ __('Atributos') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Gestao')" class="grid">
                    <flux:sidebar.item
                        icon="users"
                        :href="route('admin.customers.index')"
                        :current="request()->routeIs('admin.customers.*')"
                    >
                        {{ __('Clientes') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="archive-box"
                        :href="route('admin.inventory.index')"
                        :current="request()->routeIs('admin.inventory.*')"
                    >
                        {{ __('Estoque') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="truck"
                        :href="route('admin.shipping.index')"
                        :current="request()->routeIs('admin.shipping.*')"
                    >
                        {{ __('Envios') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Marketing')" class="grid">
                    <flux:sidebar.item
                        icon="ticket"
                        :href="route('admin.coupons.index')"
                        :current="request()->routeIs('admin.coupons.*')"
                    >
                        {{ __('Cupons') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="photo"
                        :href="route('admin.banners.index')"
                        :current="request()->routeIs('admin.banners.*')"
                    >
                        {{ __('Banners') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="shopping-cart"
                        :href="route('admin.carts.abandoned')"
                        :current="request()->routeIs('admin.carts.*')"
                    >
                        {{ __('Carrinhos Abandonados') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item
                    icon="cog-6-tooth"
                    :href="route('admin.settings.index')"
                    :current="request()->routeIs('admin.settings.*')"
                >
                    {{ __('Configuracoes') }}
                </flux:sidebar.item>

                <flux:sidebar.item
                    icon="clipboard-document-list"
                    :href="route('admin.activity-logs.index')"
                    :current="request()->routeIs('admin.activity-logs.*')"
                >
                    {{ __('Logs') }}
                </flux:sidebar.item>

                <flux:sidebar.item
                    icon="question-mark-circle"
                    :href="route('admin.help')"
                    :current="request()->routeIs('admin.help')"
                >
                    {{ __('Ajuda') }}
                </flux:sidebar.item>

                @if(auth('admin')->user()->isMaster())
                    <flux:sidebar.item
                        icon="user-group"
                        :href="route('admin.administrators.index')"
                        :current="request()->routeIs('admin.administrators.*')"
                    >
                        {{ __('Administradores') }}
                    </flux:sidebar.item>
                @endif
            </flux:sidebar.nav>

            <flux:dropdown position="top" align="start" class="max-lg:hidden">
                <flux:sidebar.profile
                    :name="auth('admin')->user()->name"
                    :initials="auth('admin')->user()->initials()"
                />
                <flux:menu>
                    <div class="p-2 text-sm">
                        <div class="font-medium">{{ auth('admin')->user()->name }}</div>
                        <div class="text-zinc-500 dark:text-zinc-400">{{ auth('admin')->user()->email }}</div>
                    </div>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('admin.logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                        >
                            {{ __('Sair') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        {{-- Desktop Header with Search and Notifications (lg and up) --}}
        <flux:header class="hidden border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 lg:flex">
            <flux:spacer />

            <div class="flex items-center gap-4">
                <livewire:admin.global-search />
                <livewire:admin.notification-bell />
            </div>
        </flux:header>

        {{-- Mobile/Tablet Header (below lg) --}}
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="touch-target lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            {{-- Search visible on tablet (md and up, below lg) --}}
            <div class="hidden md:block md:max-w-xs md:flex-1 lg:hidden">
                <livewire:admin.global-search />
            </div>

            <div class="flex items-center gap-2 md:gap-3">
                <livewire:admin.notification-bell />

                <flux:dropdown position="top" align="end">
                    <flux:profile
                        class="touch-target"
                        :initials="auth('admin')->user()->initials()"
                        icon-trailing="chevron-down"
                    />

                    <flux:menu>
                        <div class="p-2 text-sm">
                            <div class="font-medium">{{ auth('admin')->user()->name }}</div>
                            <div class="text-zinc-500 dark:text-zinc-400">{{ auth('admin')->user()->email }}</div>
                        </div>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('admin.logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item
                                as="button"
                                type="submit"
                                icon="arrow-right-start-on-rectangle"
                                class="w-full cursor-pointer touch-target"
                            >
                                {{ __('Sair') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </flux:header>

        <flux:main>
            {{-- Breadcrumb (scrollable on mobile/tablet) --}}
            @if(isset($breadcrumbs) && count($breadcrumbs) > 0)
                <div class="mb-6 overflow-x-auto scrollbar-hide scroll-touch">
                    <flux:breadcrumbs class="whitespace-nowrap">
                        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" class="touch-target" />
                        @foreach($breadcrumbs as $crumb)
                            @if($loop->last)
                                <flux:breadcrumbs.item class="touch-target">{{ $crumb['label'] }}</flux:breadcrumbs.item>
                            @else
                                <flux:breadcrumbs.item :href="$crumb['url'] ?? '#'" class="touch-target">{{ $crumb['label'] }}</flux:breadcrumbs.item>
                            @endif
                        @endforeach
                    </flux:breadcrumbs>
                </div>
            @endif

            {{-- Page Content --}}
            {{ $slot }}
        </flux:main>

        @fluxScripts
    </body>
</html>
