@php
    $focusClass = 'transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong focus-visible:ring-offset-2 focus-visible:border-primary-strong';
    $inputClass = fn (string $field): string => 'h-12 w-full rounded-lg border px-4 ' . $focusClass . ($errors->has($field) ? ' border-secondary' : ' border-[#7b8da3]');
    $ariaAttributes = fn (string $field): string => $errors->has($field) ? 'aria-invalid="true" aria-describedby="' . 'contact-' . ($origin ?? 'default') . '-' . $field . '-error"' : '';
    $fieldId = fn (string $field): string => 'contact-' . ($origin ?? 'default') . '-' . $field;
@endphp

<form class="space-y-6" wire:submit.prevent="submit" @if($origin) data-origem="{{ $origin }}" @endif>
    @if($successMessage !== '')
        <div class="rounded-xl border border-primary-strong bg-primary-bg p-4 text-sm text-primary-strong" role="status">
            {{ $successMessage }}
        </div>
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div class="space-y-2">
            <label for="{{ $fieldId('name') }}" class="text-sm font-medium text-neutral-strong">Nome Completo</label>
            <input
                id="{{ $fieldId('name') }}"
                type="text"
                wire:model="name"
                class="{{ $inputClass('name') }}"
                {!! $ariaAttributes('name') !!}
                placeholder="Seu nome"
            />
            @error('name')
                <p id="{{ $fieldId('name') }}-error" class="text-xs text-secondary">{{ $message }}</p>
            @enderror
        </div>
        <div class="space-y-2">
            <label for="{{ $fieldId('company') }}" class="text-sm font-medium text-neutral-strong">Instituição</label>
            <input
                id="{{ $fieldId('company') }}"
                type="text"
                wire:model="company"
                class="{{ $inputClass('company') }}"
                {!! $ariaAttributes('company') !!}
                placeholder="Hospital, laboratório…"
            />
            @error('company')
                <p id="{{ $fieldId('company') }}-error" class="text-xs text-secondary">{{ $message }}</p>
            @enderror
        </div>
    </div>

    @if($this->hasExtendedFields)
        <div class="grid gap-6 md:grid-cols-2">
            <div class="space-y-2">
                <label for="{{ $fieldId('role') }}" class="text-sm font-medium text-neutral-strong">Cargo</label>
                <input
                    id="{{ $fieldId('role') }}"
                    type="text"
                    wire:model="role"
                    class="{{ $inputClass('role') }}"
                    {!! $ariaAttributes('role') !!}
                    placeholder="Ex.: Engenheiro clínico, Coordenador de enfermagem"
                />
                @error('role')
                    <p id="{{ $fieldId('role') }}-error" class="text-xs text-secondary">{{ $message }}</p>
                @enderror
            </div>
            <div class="space-y-2">
                <label for="{{ $fieldId('location') }}" class="text-sm font-medium text-neutral-strong">Cidade/Estado</label>
                <input
                    id="{{ $fieldId('location') }}"
                    type="text"
                    wire:model="location"
                    class="{{ $inputClass('location') }}"
                    {!! $ariaAttributes('location') !!}
                    placeholder="Recife/PE"
                />
                @error('location')
                    <p id="{{ $fieldId('location') }}-error" class="text-xs text-secondary">{{ $message }}</p>
                @enderror
            </div>
        </div>
    @endif

    <div @class(['grid gap-6 md:grid-cols-2' => $this->hasExtendedFields, 'space-y-2' => ! $this->hasExtendedFields])>
        <div class="space-y-2">
            <label for="{{ $fieldId('email') }}" class="text-sm font-medium text-neutral-strong">E-mail Corporativo</label>
            <input
                id="{{ $fieldId('email') }}"
                type="email"
                wire:model="email"
                class="{{ $inputClass('email') }}"
                {!! $ariaAttributes('email') !!}
                placeholder="seu@email.com.br"
            />
            @error('email')
                <p id="{{ $fieldId('email') }}-error" class="text-xs text-secondary">{{ $message }}</p>
            @enderror
        </div>

        @if($this->hasExtendedFields)
            <div class="space-y-2">
                <label for="{{ $fieldId('phone') }}" class="text-sm font-medium text-neutral-strong">WhatsApp</label>
                <input
                    id="{{ $fieldId('phone') }}"
                    type="tel"
                    wire:model="phone"
                    class="{{ $inputClass('phone') }}"
                    {!! $ariaAttributes('phone') !!}
                    placeholder="(81) 90000-0000"
                />
                @error('phone')
                    <p id="{{ $fieldId('phone') }}-error" class="text-xs text-secondary">{{ $message }}</p>
                @enderror
            </div>
        @endif
    </div>

    <div class="space-y-2">
        <label for="{{ $fieldId('topic') }}" class="text-sm font-medium text-neutral-strong">Tipo de Necessidade</label>
        <select
            id="{{ $fieldId('topic') }}"
            wire:model="topic"
            class="{{ $inputClass('topic') }} bg-white text-sm sm:text-base"
            {!! $ariaAttributes('topic') !!}
        >
            @foreach($this->topics as $topicOption)
                <option value="{{ $topicOption }}" wire:key="topic-{{ $loop->index }}">{{ $topicOption }}</option>
            @endforeach
        </select>
        @error('topic')
            <p id="{{ $fieldId('topic') }}-error" class="text-xs text-secondary">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-2">
        <label for="{{ $fieldId('message') }}" class="text-sm font-medium text-neutral-strong">Mensagem</label>
        <textarea
            id="{{ $fieldId('message') }}"
            wire:model="message"
            class="h-32 w-full resize-none rounded-lg border p-4 {{ $focusClass }} {{ $errors->has('message') ? 'border-secondary' : 'border-[#7b8da3]' }}"
            {!! $ariaAttributes('message') !!}
            placeholder="Descreva sua necessidade para direcionarmos ao consultor adequado"
        ></textarea>
        @error('message')
            <p id="{{ $fieldId('message') }}-error" class="text-xs text-secondary">{{ $message }}</p>
        @enderror
    </div>

    <button
        type="submit"
        class="inline-flex w-full items-center justify-center rounded-[999px] border border-transparent bg-primary-strong px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-strong-hover hover:shadow-md"
        wire:loading.attr="disabled"
        @if($origin) data-cta="{{ $origin }}-form-submit" @endif
    >
        <span wire:loading.remove>Enviar solicitação</span>
        <span wire:loading>Enviando...</span>
    </button>
</form>
