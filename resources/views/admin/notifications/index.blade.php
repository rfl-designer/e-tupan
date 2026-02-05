<x-admin-layout
    title="Notificacoes"
    :breadcrumbs="[['label' => 'Notificacoes']]"
>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('Notificacoes') }}</flux:heading>
                <flux:subheading>{{ __('Historico de notificacoes do sistema') }}</flux:subheading>
            </div>
        </div>

        <livewire:admin.notification-list />
    </div>
</x-admin-layout>
