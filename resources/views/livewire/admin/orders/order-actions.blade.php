<div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
    <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
        <flux:heading size="lg">{{ __('Acoes') }}</flux:heading>
    </div>
    <div class="space-y-4 p-4">
        {{-- Status Update --}}
        @if (count($availableStatuses) > 0)
            <div>
                <p class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Atualizar Status') }}</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($availableStatuses as $value => $label)
                        <flux:button
                            wire:click="updateStatus('{{ $value }}')"
                            variant="outline"
                            size="sm"
                        >
                            {{ $label }}
                        </flux:button>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Tracking Number --}}
        @if ($order->status === App\Domain\Checkout\Enums\OrderStatus::Processing && $order->isPaid())
            <div>
                <p class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Codigo de Rastreio') }}</p>
                <div class="flex gap-2">
                    <flux:input
                        wire:model="trackingNumber"
                        placeholder="BR123456789XX"
                        class="flex-1"
                    />
                    <flux:button
                        wire:click="markAsShipped"
                        variant="primary"
                        size="sm"
                    >
                        {{ __('Enviar') }}
                    </flux:button>
                </div>
                @error('trackingNumber')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endif

        {{-- Current Tracking --}}
        @if ($order->tracking_number)
            <div class="rounded-lg bg-sky-50 p-3 dark:bg-sky-900/20">
                <p class="text-sm font-medium text-sky-800 dark:text-sky-300">{{ __('Rastreio Atual') }}</p>
                <p class="mt-1 font-mono text-sky-900 dark:text-sky-200">{{ $order->tracking_number }}</p>
            </div>
        @endif

        {{-- Cancel Order --}}
        @if ($order->canBeCancelled())
            <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:button
                    wire:click="cancelOrder"
                    wire:confirm="{{ __('Tem certeza que deseja cancelar este pedido?') }}"
                    variant="danger"
                    size="sm"
                    class="w-full"
                >
                    <flux:icon name="x-circle" class="size-4" />
                    {{ __('Cancelar Pedido') }}
                </flux:button>
            </div>
        @endif

        {{-- Refund Order --}}
        @if ($order->canBeRefunded())
            <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:button
                    wire:click="refundOrder"
                    wire:confirm="{{ __('Tem certeza que deseja reembolsar este pedido?') }}"
                    variant="filled"
                    size="sm"
                    class="w-full"
                >
                    <flux:icon name="arrow-uturn-left" class="size-4" />
                    {{ __('Reembolsar Pedido') }}
                </flux:button>
            </div>
        @endif

        {{-- Shipped Order Message --}}
        @if ($order->status === App\Domain\Checkout\Enums\OrderStatus::Shipped)
            <div class="rounded-lg bg-indigo-50 p-3 dark:bg-indigo-900/20">
                <p class="text-sm font-medium text-indigo-800 dark:text-indigo-300">{{ __('Pedido Enviado') }}</p>
                @if ($order->shipped_at)
                    <p class="mt-1 text-sm text-indigo-600 dark:text-indigo-400">
                        {{ $order->shipped_at->format('d/m/Y H:i') }}
                    </p>
                @endif
            </div>
        @endif

        {{-- Cancelled Order Message --}}
        @if ($order->status === App\Domain\Checkout\Enums\OrderStatus::Cancelled)
            <div class="rounded-lg bg-red-50 p-3 dark:bg-red-900/20">
                <p class="text-sm font-medium text-red-800 dark:text-red-300">{{ __('Pedido Cancelado') }}</p>
                @if ($order->cancelled_at)
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">
                        {{ $order->cancelled_at->format('d/m/Y H:i') }}
                    </p>
                @endif
            </div>
        @endif

        {{-- Refunded Order Message --}}
        @if ($order->status === App\Domain\Checkout\Enums\OrderStatus::Refunded)
            <div class="rounded-lg bg-purple-50 p-3 dark:bg-purple-900/20">
                <p class="text-sm font-medium text-purple-800 dark:text-purple-300">{{ __('Pedido Reembolsado') }}</p>
            </div>
        @endif

        {{-- Completed Order Message --}}
        @if ($order->status === App\Domain\Checkout\Enums\OrderStatus::Completed)
            <div class="rounded-lg bg-lime-50 p-3 dark:bg-lime-900/20">
                <p class="text-sm font-medium text-lime-800 dark:text-lime-300">{{ __('Pedido Entregue') }}</p>
                @if ($order->delivered_at)
                    <p class="mt-1 text-sm text-lime-600 dark:text-lime-400">
                        {{ $order->delivered_at->format('d/m/Y H:i') }}
                    </p>
                @endif
            </div>
        @endif
    </div>
</div>
