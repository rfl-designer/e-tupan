<div class="space-y-6">
    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-4">
        <flux:select wire:model.live="filterType" placeholder="Todos os tipos">
            <flux:select.option value="">Todos os tipos</flux:select.option>
            @foreach($this->types as $value => $label)
                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="filterStatus" placeholder="Todos">
            <flux:select.option value="">Todos</flux:select.option>
            <flux:select.option value="unread">Nao lidas</flux:select.option>
            <flux:select.option value="read">Lidas</flux:select.option>
        </flux:select>

        <flux:spacer />

        <flux:button wire:click="markAllAsRead" variant="ghost" icon="check">
            Marcar todas como lidas
        </flux:button>
    </div>

    {{-- List --}}
    <div class="space-y-2">
        @forelse($this->notifications as $notification)
            <div
                wire:key="notification-{{ $notification->id }}"
                class="flex items-start gap-4 rounded-lg border p-4 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800 {{ $notification->isUnread() ? 'border-blue-200 bg-blue-50/50 dark:border-blue-800 dark:bg-blue-900/10' : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' }}"
            >
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-{{ $notification->type->color() }}-100 dark:bg-{{ $notification->type->color() }}-900/30">
                    <flux:icon
                        name="{{ $notification->icon ?? $notification->type->icon() }}"
                        class="size-6 text-{{ $notification->type->color() }}-600 dark:text-{{ $notification->type->color() }}-400"
                    />
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-white">
                                {{ $notification->title }}
                            </p>
                            <p class="mt-1 text-zinc-600 dark:text-zinc-400">
                                {{ $notification->message }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            @if($notification->isUnread())
                                <flux:button
                                    wire:click="markAsRead('{{ $notification->id }}')"
                                    variant="ghost"
                                    size="sm"
                                    icon="check"
                                >
                                    Marcar como lida
                                </flux:button>
                            @endif
                            @if($notification->link)
                                <flux:button
                                    :href="$notification->link"
                                    variant="ghost"
                                    size="sm"
                                    icon="arrow-right"
                                >
                                    Ver
                                </flux:button>
                            @endif
                        </div>
                    </div>
                    <div class="mt-2 flex items-center gap-4 text-sm text-zinc-500">
                        <flux:badge size="sm" color="{{ $notification->type->color() }}">
                            {{ $notification->type->label() }}
                        </flux:badge>
                        <span>{{ $notification->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:icon name="bell-slash" class="mx-auto size-12 text-zinc-300 dark:text-zinc-600" />
                <flux:heading size="lg" class="mt-4">Nenhuma notificacao</flux:heading>
                <flux:text class="mt-2 text-zinc-500">
                    Voce nao tem notificacoes no momento
                </flux:text>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($this->notifications->hasPages())
        <div class="flex justify-center">
            {{ $this->notifications->links() }}
        </div>
    @endif
</div>
