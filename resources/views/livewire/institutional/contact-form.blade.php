@php
    $inputClass = 'h-12 w-full rounded-lg border border-neutral-light px-4 outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary-bg';
    $fieldId = fn (string $field): string => 'contact-' . ($origin ?? 'default') . '-' . $field;
@endphp

<form class="space-y-6" wire:submit.prevent="submit" @if($origin) data-origem="{{ $origin }}" @endif>
    @if($successMessage !== '')
        <div class="rounded-xl border border-primary bg-primary-bg p-4 text-sm text-primary">
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
                class="{{ $inputClass }}"
                placeholder="Seu nome"
            />
            @error('name')
                <p class="text-xs text-secondary">{{ $message }}</p>
            @enderror
        </div>
        <div class="space-y-2">
            <label for="{{ $fieldId('company') }}" class="text-sm font-medium text-neutral-strong">Instituição</label>
            <input
                id="{{ $fieldId('company') }}"
                type="text"
                wire:model="company"
                class="{{ $inputClass }}"
                placeholder="Hospital, Laboratório ou Clínica"
            />
            @error('company')
                <p class="text-xs text-secondary">{{ $message }}</p>
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
                    class="{{ $inputClass }}"
                    placeholder="Ex.: Engenheiro clínico, Coordenador de enfermagem"
                />
                @error('role')
                    <p class="text-xs text-secondary">{{ $message }}</p>
                @enderror
            </div>
            <div class="space-y-2">
                <label for="{{ $fieldId('location') }}" class="text-sm font-medium text-neutral-strong">Cidade/Estado</label>
                <input
                    id="{{ $fieldId('location') }}"
                    type="text"
                    wire:model="location"
                    class="{{ $inputClass }}"
                    placeholder="Recife/PE"
                />
                @error('location')
                    <p class="text-xs text-secondary">{{ $message }}</p>
                @enderror
            </div>
        </div>
    @endif

    <div @class(['grid gap-6 md:grid-cols-2' => $this->hasExtendedFields, 'space-y-2' => ! $this->hasExtendedFields])>
        <div class="space-y-2">
            <label for="{{ $fieldId('email') }}" class="text-sm font-medium text-neutral-strong">{{ $this->hasExtendedFields ? 'E-mail Corporativo' : 'E-mail Profissional' }}</label>
            <input
                id="{{ $fieldId('email') }}"
                type="email"
                wire:model="email"
                class="{{ $inputClass }}"
                placeholder="seu@email.com.br"
            />
            @error('email')
                <p class="text-xs text-secondary">{{ $message }}</p>
            @enderror
        </div>

        @if($this->hasExtendedFields)
            <div class="space-y-2">
                <label for="{{ $fieldId('phone') }}" class="text-sm font-medium text-neutral-strong">WhatsApp</label>
                <input
                    id="{{ $fieldId('phone') }}"
                    type="tel"
                    wire:model="phone"
                    class="{{ $inputClass }}"
                    placeholder="(81) 90000-0000"
                />
                @error('phone')
                    <p class="text-xs text-secondary">{{ $message }}</p>
                @enderror
            </div>
        @endif
    </div>

    <div class="space-y-2">
        <label for="{{ $fieldId('topic') }}" class="text-sm font-medium text-neutral-strong">{{ $this->hasExtendedFields ? 'Tipo de Necessidade' : 'Tipo de Solicitação' }}</label>
        <select
            id="{{ $fieldId('topic') }}"
            wire:model="topic"
            class="{{ $inputClass }} bg-white"
        >
            @foreach($this->topics as $topicOption)
                <option value="{{ $topicOption }}" wire:key="topic-{{ $loop->index }}">{{ $topicOption }}</option>
            @endforeach
        </select>
        @error('topic')
            <p class="text-xs text-secondary">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-2">
        <label for="{{ $fieldId('message') }}" class="text-sm font-medium text-neutral-strong">{{ $this->hasExtendedFields ? 'Mensagem' : 'Detalhes da Solicitação' }}</label>
        <textarea
            id="{{ $fieldId('message') }}"
            wire:model="message"
            class="h-32 w-full resize-none rounded-lg border border-neutral-light p-4 outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary-bg"
            placeholder="Descreva sua necessidade para direcionarmos ao consultor adequado"
        ></textarea>
        @error('message')
            <p class="text-xs text-secondary">{{ $message }}</p>
        @enderror
    </div>

    <button
        type="submit"
        class="inline-flex w-full items-center justify-center rounded-[999px] border border-transparent bg-secondary px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-secondary-hover hover:shadow-md"
        wire:loading.attr="disabled"
        @if($origin) data-cta="{{ $origin }}-form-submit" @endif
    >
        <span wire:loading.remove>{{ $this->hasExtendedFields ? 'Enviar solicitação' : 'Solicitar Contato' }}</span>
        <span wire:loading>Enviando...</span>
    </button>
</form>
