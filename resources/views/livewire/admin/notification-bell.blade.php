<div wire:poll.5s>
    <flux:dropdown align="end" position="bottom">
        <flux:button variant="ghost" square class="relative">
            <flux:icon name="bell" class="size-5" />
            @if($this->unreadCount > 0)
                <span class="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-xs font-medium text-white">
                    {{ $this->unreadCount > 99 ? '99+' : $this->unreadCount }}
                </span>
            @endif
        </flux:button>

        <flux:menu class="w-80">
            <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <span class="font-medium text-zinc-900 dark:text-white">Notificacoes</span>
                @if($this->unreadCount > 0)
                    <flux:button
                        wire:click="markAllAsRead"
                        variant="ghost"
                        size="sm"
                    >
                        Marcar todas como lidas
                    </flux:button>
                @endif
            </div>

            <div class="max-h-96 overflow-y-auto">
                @forelse($this->notifications as $notification)
                    <flux:menu.item
                        wire:key="notification-{{ $notification->id }}"
                        wire:click="markAsRead('{{ $notification->id }}')"
                        :href="$notification->link"
                        class="{{ $notification->isUnread() ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}"
                    >
                        <div class="flex gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-{{ $notification->type->color() }}-100 dark:bg-{{ $notification->type->color() }}-900/30">
                                <flux:icon
                                    name="{{ $notification->icon ?? $notification->type->icon() }}"
                                    class="size-5 text-{{ $notification->type->color() }}-600 dark:text-{{ $notification->type->color() }}-400"
                                />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-zinc-900 dark:text-white">
                                    {{ $notification->title }}
                                </p>
                                <p class="truncate text-sm text-zinc-500">
                                    {{ $notification->message }}
                                </p>
                                <p class="mt-1 text-xs text-zinc-400">
                                    {{ $notification->created_at->diffForHumans() }}
                                </p>
                            </div>
                            @if($notification->isUnread())
                                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-blue-500"></span>
                            @endif
                        </div>
                    </flux:menu.item>
                @empty
                    <div class="px-4 py-8 text-center">
                        <flux:icon name="bell-slash" class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
                        <p class="mt-2 text-sm text-zinc-500">Nenhuma notificacao</p>
                    </div>
                @endforelse
            </div>

            @if($this->notifications->isNotEmpty())
                <div class="border-t border-zinc-200 px-4 py-2 dark:border-zinc-700">
                    <flux:button
                        :href="route('admin.notifications.index')"
                        variant="ghost"
                        class="w-full"
                    >
                        Ver todas as notificacoes
                    </flux:button>
                </div>
            @endif
        </flux:menu>
    </flux:dropdown>
</div>
