<x-admin-layout
    title="Log de Atividades"
    :breadcrumbs="[['label' => 'Logs de Atividades']]"
>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('Log de Atividades') }}</flux:heading>
                <flux:subheading>{{ __('Historico de acoes administrativas') }}</flux:subheading>
            </div>
        </div>

        <livewire:admin.activity-log-list />
    </div>
</x-admin-layout>
