<form class="space-y-6" wire:submit.prevent="submit">
    @if($successMessage !== '')
        <div class="rounded-xl border border-primary bg-primary-bg p-4 text-sm text-primary">
            {{ $successMessage }}
        </div>
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div class="space-y-2">
            <label class="text-sm font-medium text-neutral-strong">Nome Completo</label>
            <input
                type="text"
                wire:model="name"
                class="h-12 w-full rounded-lg border border-neutral-light px-4 outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary-bg"
                placeholder="Seu nome"
            />
            @error('name')
                <p class="text-xs text-secondary">{{ $message }}</p>
            @enderror
        </div>
        <div class="space-y-2">
            <label class="text-sm font-medium text-neutral-strong">Instituição</label>
            <input
                type="text"
                wire:model="company"
                class="h-12 w-full rounded-lg border border-neutral-light px-4 outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary-bg"
                placeholder="Hospital, Laboratório ou Clínica"
            />
            @error('company')
                <p class="text-xs text-secondary">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="space-y-2">
        <label class="text-sm font-medium text-neutral-strong">E-mail Profissional</label>
        <input
            type="email"
            wire:model="email"
            class="h-12 w-full rounded-lg border border-neutral-light px-4 outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary-bg"
            placeholder="seu@email.com.br"
        />
        @error('email')
            <p class="text-xs text-secondary">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-2">
        <label class="text-sm font-medium text-neutral-strong">Tipo de Solicitação</label>
        <select
            wire:model="topic"
            class="h-12 w-full rounded-lg border border-neutral-light bg-white px-4 outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary-bg"
        >
            <option>Consultoria Técnica em Produtos</option>
            <option>Engenharia Clínica (EquipaHosp)</option>
            <option>Cotação para Licitação</option>
            <option>Parceria Comercial</option>
            <option>Outros Assuntos</option>
        </select>
        @error('topic')
            <p class="text-xs text-secondary">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-2">
        <label class="text-sm font-medium text-neutral-strong">Detalhes da Solicitação</label>
        <textarea
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
    >
        <span wire:loading.remove>Solicitar Contato</span>
        <span wire:loading>Enviando...</span>
    </button>
</form>
