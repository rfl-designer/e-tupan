@php
    use App\Domain\Admin\Services\SettingsService;

    $settings = app(SettingsService::class);
    $storeName = $settings->get('general.store_name') ?: config('app.name');
    $storeEmail = $settings->get('general.store_email');
    $storePhone = $settings->get('general.store_phone');
    $storeAddress = $settings->get('general.store_address');
@endphp

<footer class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        <div class="grid grid-cols-2 gap-6 sm:gap-8 md:grid-cols-4">
            {{-- About --}}
            <div class="col-span-2 space-y-3 sm:col-span-1 sm:space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-900 dark:text-white">
                    {{ $storeName }}
                </h3>
                <p class="text-xs text-zinc-600 sm:text-sm dark:text-zinc-400">
                    Sua loja online de confianca. Produtos de qualidade com os melhores precos.
                </p>
            </div>

            {{-- Links --}}
            <div class="space-y-3 sm:space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-900 dark:text-white">
                    Links Uteis
                </h3>
                <ul class="space-y-2">
                    <li>
                        <a href="{{ route('home') }}" class="text-xs text-zinc-600 hover:text-zinc-900 sm:text-sm dark:text-zinc-400 dark:hover:text-white">
                            Inicio
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('cart.index') }}" class="text-xs text-zinc-600 hover:text-zinc-900 sm:text-sm dark:text-zinc-400 dark:hover:text-white">
                            Carrinho
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('tracking.index') }}" class="text-xs text-zinc-600 hover:text-zinc-900 sm:text-sm dark:text-zinc-400 dark:hover:text-white">
                            Rastrear Pedido
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Customer Service --}}
            <div class="space-y-3 sm:space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-900 dark:text-white">
                    Atendimento
                </h3>
                <ul class="space-y-2">
                    <li>
                        <a href="#" class="text-xs text-zinc-600 hover:text-zinc-900 sm:text-sm dark:text-zinc-400 dark:hover:text-white">
                            Central de Ajuda
                        </a>
                    </li>
                    <li>
                        <a href="#" class="text-xs text-zinc-600 hover:text-zinc-900 sm:text-sm dark:text-zinc-400 dark:hover:text-white">
                            Trocas e Devolucoes
                        </a>
                    </li>
                    <li>
                        <a href="#" class="text-xs text-zinc-600 hover:text-zinc-900 sm:text-sm dark:text-zinc-400 dark:hover:text-white">
                            Politica de Privacidade
                        </a>
                    </li>
                    <li>
                        <a href="#" class="text-xs text-zinc-600 hover:text-zinc-900 sm:text-sm dark:text-zinc-400 dark:hover:text-white">
                            Termos de Uso
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Contact --}}
            <div class="space-y-3 sm:space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-900 dark:text-white">
                    Contato
                </h3>
                <ul class="space-y-2">
                    @if($storeEmail)
                        <li class="flex items-center gap-2 text-xs text-zinc-600 sm:text-sm dark:text-zinc-400">
                            <flux:icon name="envelope" class="size-3.5 shrink-0 sm:size-4" />
                            <a href="mailto:{{ $storeEmail }}" class="truncate hover:text-zinc-900 dark:hover:text-white">
                                {{ $storeEmail }}
                            </a>
                        </li>
                    @endif
                    @if($storePhone)
                        <li class="flex items-center gap-2 text-xs text-zinc-600 sm:text-sm dark:text-zinc-400">
                            <flux:icon name="phone" class="size-3.5 shrink-0 sm:size-4" />
                            <a href="tel:{{ preg_replace('/\D/', '', $storePhone) }}" class="hover:text-zinc-900 dark:hover:text-white">
                                {{ $storePhone }}
                            </a>
                        </li>
                    @endif
                    @if($storeAddress)
                        <li class="flex items-start gap-2 text-xs text-zinc-600 sm:text-sm dark:text-zinc-400">
                            <flux:icon name="map-pin" class="size-3.5 shrink-0 sm:size-4" />
                            <span>{{ $storeAddress }}</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        {{-- Bottom Bar --}}
        <div class="mt-8 border-t border-zinc-200 pt-6 sm:mt-12 sm:pt-8 dark:border-zinc-700">
            <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <p class="text-xs text-zinc-500 sm:text-sm dark:text-zinc-400">
                    &copy; {{ date('Y') }} {{ $storeName }}. Todos os direitos reservados.
                </p>
                <div class="flex items-center gap-4">
                    {{-- Payment Icons --}}
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] text-zinc-500 sm:text-xs dark:text-zinc-400">Formas de pagamento:</span>
                        <div class="flex items-center gap-1">
                            <flux:icon name="credit-card" class="size-4 text-zinc-400 sm:size-5" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
