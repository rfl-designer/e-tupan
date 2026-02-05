<div class="space-y-6">
    {{-- Search --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="max-w-md">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                placeholder="Buscar clientes..."
                icon="magnifying-glass"
            />
        </div>
    </div>

    {{-- Customers Table --}}
    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300">
                            <button wire:click="sortBy('name')" class="flex items-center gap-1 hover:text-zinc-900 dark:hover:text-white">
                                {{ __('Nome') }}
                                @if ($sortField === 'name')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Email') }}
                        </th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Pedidos') }}
                        </th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Total Gasto') }}
                        </th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300">
                            <button wire:click="sortBy('created_at')" class="flex items-center gap-1 hover:text-zinc-900 dark:hover:text-white">
                                {{ __('Cliente Desde') }}
                                @if ($sortField === 'created_at')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Acoes') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($customers as $customer)
                        <tr wire:key="customer-{{ $customer->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex size-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                                        <span class="text-sm font-medium text-zinc-600 dark:text-zinc-300">
                                            {{ strtoupper(substr($customer->name, 0, 2)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.customers.show', $customer) }}" class="font-medium text-zinc-900 hover:text-sky-600 dark:text-white dark:hover:text-sky-400">
                                            {{ $customer->name }}
                                        </a>
                                        @if ($customer->phone)
                                            <p class="text-xs text-zinc-500">{{ $customer->phone }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                {{ $customer->email }}
                            </td>
                            <td class="px-4 py-3 text-zinc-900 dark:text-white">
                                {{ $customer->orders_count }}
                            </td>
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                                R$ {{ number_format(($customer->orders_sum_total ?? 0) / 100, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                {{ $customer->created_at->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <flux:button href="{{ route('admin.customers.show', $customer) }}" variant="ghost" size="xs">
                                    <flux:icon name="eye" class="size-4" />
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <flux:icon name="users" class="size-12 text-zinc-300 dark:text-zinc-600" />
                                    <p class="mt-4 text-zinc-500">{{ __('Nenhum cliente encontrado') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($customers->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</div>
