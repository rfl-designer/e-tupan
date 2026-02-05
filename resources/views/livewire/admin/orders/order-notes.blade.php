<div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
    <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
        <flux:heading size="lg">{{ __('Notas Internas') }}</flux:heading>
    </div>
    <div class="p-4">
        {{-- Add Note Form --}}
        <form wire:submit="addNote" class="space-y-3">
            <flux:textarea
                wire:model="note"
                placeholder="{{ __('Adicionar uma nota sobre este pedido...') }}"
                rows="3"
            />
            @error('note')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div class="flex items-center justify-between">
                <flux:checkbox
                    wire:model="isCustomerVisible"
                    label="{{ __('Visivel para o cliente') }}"
                />

                <flux:button type="submit" variant="primary" size="sm">
                    <flux:icon name="plus" class="size-4" />
                    {{ __('Adicionar') }}
                </flux:button>
            </div>
        </form>

        {{-- Notes List --}}
        @if ($notes->count() > 0)
            <div class="mt-4 space-y-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                @foreach ($notes as $note)
                    <div wire:key="note-{{ $note->id }}" class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1">
                                <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $note->note }}</p>
                            </div>
                            <flux:button
                                wire:click="deleteNote({{ $note->id }})"
                                wire:confirm="{{ __('Tem certeza que deseja excluir esta nota?') }}"
                                variant="ghost"
                                size="xs"
                            >
                                <flux:icon name="trash" class="size-4 text-zinc-400 hover:text-red-500" />
                            </flux:button>
                        </div>
                        <div class="mt-2 flex items-center gap-3 text-xs text-zinc-500">
                            <span>{{ $note->admin?->name ?? __('Sistema') }}</span>
                            <span>{{ $note->created_at->format('d/m/Y H:i') }}</span>
                            @if ($note->is_customer_visible)
                                <flux:badge color="sky" size="sm">
                                    {{ __('Visivel') }}
                                </flux:badge>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="mt-4 border-t border-zinc-200 pt-4 text-center text-sm text-zinc-500 dark:border-zinc-700">
                {{ __('Nenhuma nota adicionada') }}
            </div>
        @endif
    </div>
</div>
