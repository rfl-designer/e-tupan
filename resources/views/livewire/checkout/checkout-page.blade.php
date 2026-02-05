<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    <div class="max-w-7xl mx-auto px-4 py-8">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <a href="{{ route('cart.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200">
                <flux:icon name="arrow-left" class="size-4" />
                Voltar ao carrinho
            </a>
            <flux:heading size="xl">
                Checkout
            </flux:heading>
            <div class="w-32"></div>
        </div>

        {{-- Validation Alerts --}}
        @if (!empty($validationAlerts))
            <div class="mb-6 space-y-2">
                @foreach ($validationAlerts as $alert)
                    <flux:callout variant="warning" dismissible wire:key="alert-{{ $loop->index }}">
                        {{ $alert }}
                    </flux:callout>
                @endforeach
            </div>
        @endif

        <div class="lg:grid lg:grid-cols-12 lg:gap-8">
            {{-- Main Content --}}
            <div class="lg:col-span-8">
                {{-- Progress Steps --}}
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        @php
                            $steps = [
                                'identification' => ['label' => 'Identificacao', 'icon' => 'user'],
                                'address' => ['label' => 'Endereco', 'icon' => 'map-pin'],
                                'shipping' => ['label' => 'Entrega', 'icon' => 'truck'],
                                'payment' => ['label' => 'Pagamento', 'icon' => 'credit-card'],
                                'review' => ['label' => 'Revisao', 'icon' => 'clipboard-document-check'],
                            ];
                            $stepKeys = array_keys($steps);
                            $currentIndex = array_search($currentStep, $stepKeys);
                        @endphp

                        @foreach ($steps as $key => $step)
                            @php
                                $stepIndex = array_search($key, $stepKeys);
                                $isActive = $key === $currentStep;
                                $isCompleted = $stepIndex < $currentIndex;
                                $isClickable = $isCompleted || ($isAuthenticated && $key !== 'identification');
                            @endphp
                            <div class="flex flex-col items-center flex-1 {{ $loop->last ? '' : 'relative' }}">
                                <button
                                    wire:click="goToStep('{{ $key }}')"
                                    @if(!$isClickable) disabled @endif
                                    class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-colors {{ $isClickable ? 'cursor-pointer' : 'cursor-not-allowed' }}
                                        @if ($isActive)
                                            border-primary-600 bg-primary-600 text-white
                                        @elseif ($isCompleted)
                                            border-primary-600 bg-primary-600 text-white
                                        @else
                                            border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-400 dark:text-zinc-500
                                        @endif"
                                >
                                    @if ($isCompleted)
                                        <flux:icon name="check" class="size-5" />
                                    @else
                                        <flux:icon name="{{ $step['icon'] }}" class="size-5" />
                                    @endif
                                </button>
                                <span class="mt-2 text-xs font-medium {{ $isActive ? 'text-primary-600 dark:text-primary-400' : 'text-zinc-500 dark:text-zinc-400' }}">
                                    {{ $step['label'] }}
                                </span>

                                @if (!$loop->last)
                                    <div class="absolute top-5 left-1/2 w-full h-0.5 -translate-y-1/2 {{ $isCompleted ? 'bg-primary-600' : 'bg-zinc-200 dark:bg-zinc-700' }}" style="left: calc(50% + 20px); width: calc(100% - 40px);"></div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Step Content --}}
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                    @switch($currentStep)
                        @case('identification')
                            @if ($isAuthenticated)
                                {{-- Logged in user - show summary and proceed --}}
                                <div class="text-center py-8">
                                    <flux:icon name="user-circle" class="mx-auto h-16 w-16 text-primary-600" />
                                    <flux:heading size="lg" class="mt-4">
                                        Ola, {{ Auth::user()->name }}!
                                    </flux:heading>
                                    <flux:text class="mt-2">
                                        Voce esta logado como {{ Auth::user()->email }}
                                    </flux:text>
                                    <div class="mt-6">
                                        <flux:button variant="primary" wire:click="goToStep('address')">
                                            Continuar para endereco
                                        </flux:button>
                                    </div>
                                </div>
                            @else
                                <livewire:checkout.checkout-identification :guest-data="$guestData" />
                            @endif
                            @break

                        @case('address')
                            <livewire:checkout.checkout-address :address-data="$addressData" :is-authenticated="$isAuthenticated" />
                            @break

                        @case('shipping')
                            <livewire:checkout.checkout-shipping :zipcode="$addressData['shipping_zipcode']" :shipping-data="$shippingData" />
                            @break

                        @case('payment')
                            <livewire:checkout.checkout-payment :payment-method="$paymentMethod" :total="$this->total" />
                            @break

                        @case('review')
                            <livewire:checkout.checkout-review
                                :guest-data="$guestData"
                                :address-data="$addressData"
                                :shipping-data="$shippingData"
                                :payment-method="$paymentMethod"
                                :is-authenticated="$isAuthenticated"
                            />
                            @break
                    @endswitch
                </div>
            </div>

            {{-- Order Summary Sidebar --}}
            <div class="lg:col-span-4 mt-8 lg:mt-0">
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 sticky top-4">
                    <flux:heading size="lg" class="mb-6">
                        Resumo do Pedido
                    </flux:heading>

                    {{-- Cart Items --}}
                    <div class="space-y-4 max-h-64 overflow-y-auto">
                        @foreach ($this->cartItems as $item)
                            <div wire:key="summary-item-{{ $item->id }}" class="flex gap-3">
                                @php
                                    $image = $item->product->images->first();
                                @endphp
                                <div class="w-16 h-16 bg-zinc-100 dark:bg-zinc-700 rounded-lg overflow-hidden flex-shrink-0">
                                    @if ($image)
                                        <img src="{{ Storage::url($image->path) }}" alt="{{ $item->product->name }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-zinc-400">
                                            <flux:icon name="photo" class="size-6" />
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate">
                                        {{ $item->product->name }}
                                    </p>
                                    @if ($item->variant)
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $item->variant->attributeValues->pluck('value')->join(' / ') }}
                                        </p>
                                    @endif
                                    <div class="flex items-center justify-between mt-1">
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">Qtd: {{ $item->quantity }}</span>
                                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            R$ {{ number_format($item->getSubtotal() / 100, 2, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Divider --}}
                    <div class="my-6 border-t border-zinc-200 dark:border-zinc-700"></div>

                    {{-- Totals --}}
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-600 dark:text-zinc-400">Subtotal ({{ $this->itemCount }} {{ $this->itemCount === 1 ? 'item' : 'itens' }})</span>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                R$ {{ number_format($this->subtotal / 100, 2, ',', '.') }}
                            </span>
                        </div>

                        @if ($this->discount > 0)
                            <div class="flex justify-between text-sm text-green-600 dark:text-green-400">
                                <span>Desconto</span>
                                <span>- R$ {{ number_format($this->discount / 100, 2, ',', '.') }}</span>
                            </div>
                        @endif

                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-600 dark:text-zinc-400">Frete</span>
                            @if ($this->shippingCost > 0)
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                    R$ {{ number_format($this->shippingCost / 100, 2, ',', '.') }}
                                </span>
                            @elseif ($shippingData['shipping_method'])
                                <span class="font-medium text-green-600 dark:text-green-400">Gratis</span>
                            @else
                                <span class="text-zinc-500 dark:text-zinc-400">A calcular</span>
                            @endif
                        </div>
                    </div>

                    {{-- Total --}}
                    <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                        <div class="flex justify-between items-center">
                            <span class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Total</span>
                            <span class="text-xl font-bold text-zinc-900 dark:text-zinc-100">
                                R$ {{ number_format($this->total / 100, 2, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    {{-- Security badges --}}
                    <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center justify-center gap-4 text-xs text-zinc-500 dark:text-zinc-400">
                            <div class="flex items-center gap-1">
                                <flux:icon name="lock-closed" class="size-4" />
                                <span>Compra segura</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <flux:icon name="shield-check" class="size-4" />
                                <span>Dados protegidos</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
