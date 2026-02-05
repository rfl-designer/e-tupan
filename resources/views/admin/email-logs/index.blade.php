<x-admin-layout
    title="Logs de Email"
    :breadcrumbs="[['label' => 'Logs de Email']]"
>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('Logs de Email') }}</flux:heading>
                <flux:subheading>{{ __('Historico de emails enviados pelo sistema') }}</flux:subheading>
            </div>
        </div>

        <livewire:admin.settings.email-log-list />
    </div>
</x-admin-layout>
