<x-layouts.public>
    <div class="min-h-screen bg-zinc-50 dark:bg-zinc-900 py-8 px-4">
        <livewire:checkout.order-confirmation :order="$order" :isGuest="$isGuest" />
    </div>
</x-layouts.public>
