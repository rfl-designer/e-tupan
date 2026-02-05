<div class="space-y-4">
    <flux:heading size="sm" class="mb-3">
        Cupom de Desconto
    </flux:heading>

    @if ($this->hasCoupon)
        {{-- Applied Coupon Display --}}
        <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-start gap-2">
                    <flux:icon name="check-circle" class="size-5 text-green-600 dark:text-green-400 mt-0.5 shrink-0" />
                    <div>
                        <p class="text-sm font-medium text-green-800 dark:text-green-300">
                            Cupom aplicado: {{ $this->appliedCoupon->code }}
                        </p>
                        <p class="text-xs text-green-600 dark:text-green-400">
                            @if ($this->appliedCoupon->type->value === 'percentage')
                                {{ $this->appliedCoupon->value }}% de desconto
                            @elseif ($this->appliedCoupon->type->value === 'fixed')
                                R$ {{ number_format($this->appliedCoupon->value / 100, 2, ',', '.') }} de desconto
                            @else
                                Frete gratis
                            @endif
                        </p>
                        <p class="text-sm font-semibold text-green-800 dark:text-green-200 mt-1">
                            -R$ {{ number_format($this->discountAmount / 100, 2, ',', '.') }}
                        </p>
                    </div>
                </div>
                <button
                    wire:click="removeCoupon"
                    type="button"
                    class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200"
                    title="Remover cupom"
                >
                    <flux:icon name="x-mark" class="size-5" />
                </button>
            </div>
        </div>
    @else
        {{-- Coupon Input Form --}}
        <div class="flex gap-2">
            <div class="flex-1">
                <flux:input
                    wire:model="couponCode"
                    wire:keydown.enter="applyCoupon"
                    placeholder="Digite seu cupom"
                    :disabled="$isApplying"
                />
            </div>
            <flux:button
                wire:click="applyCoupon"
                wire:loading.attr="disabled"
                wire:target="applyCoupon"
                variant="filled"
                :disabled="$isApplying"
            >
                <span wire:loading.remove wire:target="applyCoupon">Aplicar</span>
                <span wire:loading wire:target="applyCoupon">
                    <flux:icon name="arrow-path" class="size-4 animate-spin" />
                </span>
            </flux:button>
        </div>
    @endif

    {{-- Error Message --}}
    @if ($errorMessage)
        <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
            <div class="flex items-center gap-2">
                <flux:icon name="exclamation-circle" class="size-5 text-red-600 dark:text-red-400 shrink-0" />
                <p class="text-sm text-red-700 dark:text-red-300">{{ $errorMessage }}</p>
            </div>
        </div>
    @endif

    {{-- Success Message --}}
    @if ($successMessage)
        <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
            <div class="flex items-center gap-2">
                <flux:icon name="check-circle" class="size-5 text-green-600 dark:text-green-400 shrink-0" />
                <p class="text-sm text-green-700 dark:text-green-300">{{ $successMessage }}</p>
            </div>
        </div>
    @endif
</div>
