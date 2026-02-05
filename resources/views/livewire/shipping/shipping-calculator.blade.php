<div class="space-y-4">
    {{-- CEP Input --}}
    <div>
        <flux:heading size="sm" class="mb-3">
            Calcular Frete
        </flux:heading>

        <div class="flex gap-2">
            <div class="flex-1">
                <flux:input
                    wire:model="zipcode"
                    wire:keydown.enter="calculateShipping"
                    placeholder="00000-000"
                    maxlength="9"
                    :disabled="$isCalculating"
                />
            </div>
            <flux:button
                wire:click="calculateShipping"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-50"
                wire:target="calculateShipping"
                variant="filled"
                :disabled="$isCalculating"
            >
                <span wire:loading.remove wire:target="calculateShipping">Calcular</span>
                <span wire:loading wire:target="calculateShipping">
                    <flux:icon name="arrow-path" class="size-4 animate-spin" />
                </span>
            </flux:button>
        </div>

        @error('zipcode')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror

        @if ($errorMessage)
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $errorMessage }}</p>
        @endif

        <a
            href="https://buscacepinter.correios.com.br/app/endereco/index.php"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-2 inline-flex items-center gap-1 text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
        >
            Nao sei meu CEP
            <flux:icon name="arrow-top-right-on-square" class="size-3" />
        </a>
    </div>

    {{-- Shipping Options --}}
    @if ($hasCalculated && count($options) > 0)
        <div class="space-y-2">
            <flux:heading size="xs" class="text-zinc-600 dark:text-zinc-400">
                Opcoes de envio
            </flux:heading>

            <div class="space-y-2">
                @foreach ($options as $option)
                    <button
                        wire:click="selectOption('{{ $option['code'] }}')"
                        wire:key="shipping-option-{{ $option['code'] }}"
                        type="button"
                        @class([
                            'w-full p-3 rounded-lg border text-left transition-all',
                            'border-primary-500 bg-primary-50 dark:bg-primary-900/20' => $selectedOption === $option['code'],
                            'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600' => $selectedOption !== $option['code'],
                        ])
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div @class([
                                    'size-5 rounded-full border-2 flex items-center justify-center',
                                    'border-primary-500' => $selectedOption === $option['code'],
                                    'border-zinc-300 dark:border-zinc-600' => $selectedOption !== $option['code'],
                                ])>
                                    @if ($selectedOption === $option['code'])
                                        <div class="size-2.5 rounded-full bg-primary-500"></div>
                                    @endif
                                </div>
                                <div>
                                    <p class="font-medium text-sm text-zinc-900 dark:text-zinc-100">
                                        {{ $option['name'] }}
                                    </p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $option['delivery_time'] }}
                                    </p>
                                </div>
                            </div>
                            <p class="font-semibold text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $option['formatted_price'] }}
                            </p>
                        </div>
                    </button>
                @endforeach
            </div>

            @if ($selectedOption)
                <button
                    wire:click="clearShipping"
                    type="button"
                    class="mt-2 text-xs text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300"
                >
                    Limpar frete
                </button>
            @endif
        </div>
    @endif

    {{-- Selected Option Summary --}}
    @if ($this->currentOption)
        <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
            <div class="flex items-center gap-2">
                <flux:icon name="check-circle" class="size-5 text-green-600 dark:text-green-400" />
                <div class="flex-1">
                    <p class="text-sm font-medium text-green-800 dark:text-green-300">
                        Frete selecionado: {{ $this->currentOption['name'] }}
                    </p>
                    <p class="text-xs text-green-600 dark:text-green-400">
                        {{ $this->currentOption['delivery_time'] }} - {{ $this->currentOption['formatted_price'] }}
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>
