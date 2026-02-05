<div>
    {{-- Banner Carousel --}}
    @if (count($banners) > 0)
        <section class="pt-6 sm:pt-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-storefront.banner-carousel :banners="$banners" />
            </div>
        </section>
    @endif

    {{-- Categories Section --}}
    @if($categories->isNotEmpty())
        <section class="py-10 sm:py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-zinc-900 sm:text-2xl dark:text-white">
                            Categorias
                        </h2>
                        <p class="mt-1 text-xs text-zinc-600 sm:text-sm dark:text-zinc-400">
                            Navegue por nossas categorias
                        </p>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-3 sm:mt-8 sm:grid-cols-3 sm:gap-4 md:grid-cols-4 lg:grid-cols-6">
                    @foreach($categories as $category)
                        <x-storefront.category-card :category="$category" wire:key="cat-{{ $category->id }}" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Sale Products Section --}}
    @if($saleProducts->isNotEmpty())
        <section class="bg-red-50 py-10 sm:py-16 dark:bg-red-950/20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-zinc-900 sm:text-2xl dark:text-white">
                            <flux:icon name="fire" class="inline size-5 text-red-500 sm:size-7" />
                            Ofertas Especiais
                        </h2>
                        <p class="mt-1 text-xs text-zinc-600 sm:text-sm dark:text-zinc-400">
                            Aproveite os melhores descontos
                        </p>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-3 sm:mt-8 sm:grid-cols-3 sm:gap-6 lg:grid-cols-4">
                    @foreach($saleProducts as $product)
                        <x-storefront.product-card :product="$product" wire:key="sale-{{ $product->id }}" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Featured Products Section --}}
    <section id="produtos" class="py-10 sm:py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-zinc-900 sm:text-2xl dark:text-white">
                        Produtos em Destaque
                    </h2>
                    <p class="mt-1 text-xs text-zinc-600 sm:text-sm dark:text-zinc-400">
                        Selecao especial para voce
                    </p>
                </div>
            </div>

            @if($featuredProducts->isNotEmpty())
                <div class="mt-6 grid grid-cols-2 gap-3 sm:mt-8 sm:grid-cols-3 sm:gap-6 lg:grid-cols-4">
                    @foreach($featuredProducts as $product)
                        <x-storefront.product-card :product="$product" wire:key="feat-{{ $product->id }}" />
                    @endforeach
                </div>
            @else
                <div class="mt-6 rounded-lg border border-zinc-200 bg-zinc-50 p-8 text-center sm:mt-8 sm:p-12 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:icon name="shopping-bag" class="mx-auto size-10 text-zinc-400 sm:size-12" />
                    <h3 class="mt-4 text-base font-medium text-zinc-900 sm:text-lg dark:text-white">
                        Nenhum produto disponivel
                    </h3>
                    <p class="mt-2 text-xs text-zinc-600 sm:text-sm dark:text-zinc-400">
                        Em breve teremos novidades para voce!
                    </p>
                </div>
            @endif
        </div>
    </section>

    {{-- New Products Section --}}
    @if($newProducts->isNotEmpty())
        <section class="bg-zinc-50 py-10 sm:py-16 dark:bg-zinc-800/50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-zinc-900 sm:text-2xl dark:text-white">
                            Novidades
                        </h2>
                        <p class="mt-1 text-xs text-zinc-600 sm:text-sm dark:text-zinc-400">
                            Produtos recem adicionados
                        </p>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-3 sm:mt-8 sm:grid-cols-3 sm:gap-6 lg:grid-cols-4">
                    @foreach($newProducts as $product)
                        <x-storefront.product-card :product="$product" wire:key="new-{{ $product->id }}" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Newsletter Section --}}
    <section class="py-10 sm:py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-xl bg-zinc-900 px-4 py-8 text-center sm:rounded-2xl sm:px-6 sm:py-12 md:px-12 dark:bg-zinc-800">
                <h2 class="text-xl font-bold text-white sm:text-2xl">
                    Fique por dentro das novidades
                </h2>
                <p class="mx-auto mt-3 max-w-xl text-sm text-zinc-300 sm:mt-4 sm:text-base">
                    Cadastre-se para receber ofertas exclusivas e ser o primeiro a saber sobre novos produtos.
                </p>
                <form class="mx-auto mt-6 flex max-w-md flex-col gap-3 sm:mt-8 sm:flex-row" action="#" method="POST">
                    <flux:input
                        type="email"
                        placeholder="Seu melhor email"
                        class="flex-1"
                        required
                    />
                    <flux:button variant="primary" type="submit" class="w-full sm:w-auto">
                        Cadastrar
                    </flux:button>
                </form>
            </div>
        </div>
    </section>

    {{-- Features Section --}}
    <section class="border-t border-zinc-200 py-8 sm:py-12 dark:border-zinc-700">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-4 sm:gap-8 md:grid-cols-4 lg:grid-cols-4">
                <div class="flex flex-col items-center gap-2 text-center sm:flex-row sm:items-start sm:gap-4 sm:text-left">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-zinc-100 sm:size-12 dark:bg-zinc-800">
                        <flux:icon name="truck" class="size-5 text-zinc-600 sm:size-6 dark:text-zinc-400" />
                    </div>
                    <div>
                        <h3 class="text-xs font-medium text-zinc-900 sm:text-base dark:text-white">Frete Gratis</h3>
                        <p class="mt-0.5 text-[10px] text-zinc-600 sm:text-sm dark:text-zinc-400">Em compras acima de R$ 199</p>
                    </div>
                </div>

                <div class="flex flex-col items-center gap-2 text-center sm:flex-row sm:items-start sm:gap-4 sm:text-left">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-zinc-100 sm:size-12 dark:bg-zinc-800">
                        <flux:icon name="shield-check" class="size-5 text-zinc-600 sm:size-6 dark:text-zinc-400" />
                    </div>
                    <div>
                        <h3 class="text-xs font-medium text-zinc-900 sm:text-base dark:text-white">Compra Segura</h3>
                        <p class="mt-0.5 text-[10px] text-zinc-600 sm:text-sm dark:text-zinc-400">Pagamento 100% protegido</p>
                    </div>
                </div>

                <div class="flex flex-col items-center gap-2 text-center sm:flex-row sm:items-start sm:gap-4 sm:text-left">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-zinc-100 sm:size-12 dark:bg-zinc-800">
                        <flux:icon name="arrow-path" class="size-5 text-zinc-600 sm:size-6 dark:text-zinc-400" />
                    </div>
                    <div>
                        <h3 class="text-xs font-medium text-zinc-900 sm:text-base dark:text-white">Troca Facil</h3>
                        <p class="mt-0.5 text-[10px] text-zinc-600 sm:text-sm dark:text-zinc-400">Ate 30 dias para trocar</p>
                    </div>
                </div>

                <div class="flex flex-col items-center gap-2 text-center sm:flex-row sm:items-start sm:gap-4 sm:text-left">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-zinc-100 sm:size-12 dark:bg-zinc-800">
                        <flux:icon name="credit-card" class="size-5 text-zinc-600 sm:size-6 dark:text-zinc-400" />
                    </div>
                    <div>
                        <h3 class="text-xs font-medium text-zinc-900 sm:text-base dark:text-white">Parcele em 12x</h3>
                        <p class="mt-0.5 text-[10px] text-zinc-600 sm:text-sm dark:text-zinc-400">Sem juros no cartao</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
