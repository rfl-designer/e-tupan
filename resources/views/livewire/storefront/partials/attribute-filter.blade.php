@if($this->availableAttributes->isNotEmpty())
    @foreach($this->availableAttributes as $attribute)
        @if($attribute->values->isNotEmpty())
            @php
                $attributeValues = $atributos[$attribute->slug] ?? [];
                $attributeValues = is_array($attributeValues) ? $attributeValues : [];
                $hasSelectedValues = count($attributeValues) > 0;
            @endphp
            <div class="space-y-4" wire:key="attribute-filter-{{ $attribute->id }}">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $attribute->name }}</h3>
                    @if($hasSelectedValues)
                        <button wire:click="clearAttributeFilter('{{ $attribute->slug }}')" class="text-xs text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                            Limpar
                        </button>
                    @endif
                </div>

                <div class="space-y-2">
                    @if($attribute->isColor())
                        {{-- Color Swatches --}}
                        <div class="flex flex-wrap gap-2">
                            @foreach($attribute->values as $value)
                                @php
                                    $isSelected = in_array($value->id, $attributeValues);
                                @endphp
                                <button
                                    wire:key="attr-value-{{ $value->id }}"
                                    wire:click="$set('atributos.{{ $attribute->slug }}', {{ $isSelected ? json_encode(array_values(array_diff($attributeValues, [$value->id]))) : json_encode(array_merge($attributeValues, [$value->id])) }})"
                                    class="group relative flex size-8 items-center justify-center rounded-full border-2 transition-all {{ $isSelected ? 'border-zinc-900 dark:border-white' : 'border-transparent hover:border-zinc-300 dark:hover:border-zinc-600' }}"
                                    title="{{ $value->value }}"
                                >
                                    <span
                                        class="size-6 rounded-full ring-1 ring-zinc-200 dark:ring-zinc-700"
                                        style="background-color: {{ $value->color_hex }}"
                                    ></span>
                                    @if($isSelected)
                                        <span class="absolute inset-0 flex items-center justify-center">
                                            <flux:icon name="check" class="size-4 {{ $this->getContrastColor($value->color_hex) === 'dark' ? 'text-zinc-900' : 'text-white' }}" />
                                        </span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @else
                        {{-- Regular Attribute Values (Checkboxes) --}}
                        <div class="space-y-1">
                            @foreach($attribute->values as $value)
                                @php
                                    $isSelected = in_array($value->id, $attributeValues);
                                    $newValues = $isSelected
                                        ? array_values(array_diff($attributeValues, [$value->id]))
                                        : array_merge($attributeValues, [$value->id]);
                                @endphp
                                <button
                                    type="button"
                                    wire:key="attr-value-{{ $value->id }}"
                                    wire:click="$set('atributos.{{ $attribute->slug }}', {{ json_encode($newValues) }})"
                                    class="flex w-full cursor-pointer items-center gap-2 rounded-lg px-3 py-2 text-left transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50"
                                >
                                    <span class="flex size-4 items-center justify-center rounded border {{ $isSelected ? 'border-zinc-900 bg-zinc-900 dark:border-white dark:bg-white' : 'border-zinc-300 dark:border-zinc-600' }}">
                                        @if($isSelected)
                                            <flux:icon name="check" class="size-3 text-white dark:text-zinc-900" />
                                        @endif
                                    </span>
                                    <span class="text-sm {{ $isSelected ? 'font-medium text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-400' }}">
                                        {{ $value->value }}
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            @if(!$loop->last)
                <flux:separator />
            @endif
        @endif
    @endforeach
@endif
