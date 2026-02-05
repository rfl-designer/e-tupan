<x-admin-layout
    title="Configuracoes"
    :breadcrumbs="[['label' => 'Configuracoes']]"
>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('Configuracoes') }}</flux:heading>
                <flux:subheading>{{ __('Configure os parametros da loja') }}</flux:subheading>
            </div>
        </div>

        <div
            x-data="{ activeTab: 'general' }"
            x-on:settings-saved.window="$flux.toast({ text: 'Configuracoes salvas com sucesso!', variant: 'success' })"
            class="space-y-6"
        >
            <flux:tabs variant="segmented">
                <flux:tab
                    name="general"
                    x-on:click="activeTab = 'general'"
                    x-bind:data-selected="activeTab === 'general'"
                >
                    <flux:icon name="building-storefront" class="size-4" />
                    {{ __('Geral') }}
                </flux:tab>
                <flux:tab
                    name="checkout"
                    x-on:click="activeTab = 'checkout'"
                    x-bind:data-selected="activeTab === 'checkout'"
                >
                    <flux:icon name="shopping-cart" class="size-4" />
                    {{ __('Checkout') }}
                </flux:tab>
                <flux:tab
                    name="stock"
                    x-on:click="activeTab = 'stock'"
                    x-bind:data-selected="activeTab === 'stock'"
                >
                    <flux:icon name="archive-box" class="size-4" />
                    {{ __('Estoque') }}
                </flux:tab>
                <flux:tab
                    name="shipping"
                    x-on:click="activeTab = 'shipping'"
                    x-bind:data-selected="activeTab === 'shipping'"
                >
                    <flux:icon name="truck" class="size-4" />
                    {{ __('Frete') }}
                </flux:tab>
                <flux:tab
                    name="payment"
                    x-on:click="activeTab = 'payment'"
                    x-bind:data-selected="activeTab === 'payment'"
                >
                    <flux:icon name="credit-card" class="size-4" />
                    {{ __('Pagamento') }}
                </flux:tab>
                <flux:tab
                    name="email"
                    x-on:click="activeTab = 'email'"
                    x-bind:data-selected="activeTab === 'email'"
                >
                    <flux:icon name="envelope" class="size-4" />
                    {{ __('Emails') }}
                </flux:tab>
            </flux:tabs>

            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div x-show="activeTab === 'general'" x-cloak>
                    <livewire:admin.settings.general-settings />
                </div>

                <div x-show="activeTab === 'checkout'" x-cloak>
                    <livewire:admin.settings.checkout-settings />
                </div>

                <div x-show="activeTab === 'stock'" x-cloak>
                    <livewire:admin.settings.stock-settings />
                </div>

                <div x-show="activeTab === 'shipping'" x-cloak>
                    <div class="space-y-4">
                        <flux:text class="text-zinc-600 dark:text-zinc-400">
                            {{ __('As configuracoes de frete estao disponiveis em uma pagina dedicada.') }}
                        </flux:text>
                        <flux:button
                            :href="route('admin.shipping.settings')"
                            variant="primary"
                            icon="arrow-right"
                            icon-trailing
                        >
                            {{ __('Ir para Configuracoes de Frete') }}
                        </flux:button>
                    </div>
                </div>

                <div x-show="activeTab === 'payment'" x-cloak>
                    <livewire:admin.settings.payment-settings />
                </div>

                <div x-show="activeTab === 'email'" x-cloak>
                    <livewire:admin.settings.email-settings />
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
