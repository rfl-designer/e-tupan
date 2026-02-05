<x-layouts.public title="Rastrear Pedido">
    <div class="max-w-2xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-8">
            <flux:icon name="truck" class="size-16 mx-auto mb-4 text-blue-500" />
            <flux:heading size="xl">Rastrear Pedido</flux:heading>
            <flux:subheading>Digite o codigo de rastreamento para acompanhar seu pedido</flux:subheading>
        </div>

        <div class="bg-neutral-900 rounded-lg border border-neutral-800 p-6">
            <form action="{{ route('tracking.search') }}" method="POST">
                @csrf
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <flux:input
                            name="code"
                            type="text"
                            placeholder="Ex: ABC123456789BR"
                            required
                            value="{{ old('code') }}"
                        />
                        @error('code')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <flux:button type="submit" variant="primary">
                        <flux:icon name="magnifying-glass" class="size-4 mr-1" />
                        Rastrear
                    </flux:button>
                </div>
            </form>
        </div>

        <div class="mt-8 text-center text-sm text-neutral-500">
            <p>Voce pode encontrar o codigo de rastreamento no email de confirmacao de envio</p>
        </div>
    </div>
</x-layouts.public>
