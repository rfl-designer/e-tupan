<div>
    <flux:heading size="lg" class="mb-6">
        Identificacao
    </flux:heading>

    @if ($existingAccount && !$showLoginForm)
        <flux:callout variant="info" class="mb-6">
            <p>Ja existe uma conta com este e-mail.</p>
            <div class="mt-2">
                <flux:button variant="ghost" size="sm" wire:click="toggleLoginForm">
                    Entrar na minha conta
                </flux:button>
            </div>
        </flux:callout>
    @endif

    @if ($showLoginForm)
        {{-- Login Form --}}
        <div class="mb-6 p-6 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
            <flux:heading size="md" class="mb-4">
                Entrar na sua conta
            </flux:heading>

            <form wire:submit="login" class="space-y-4">
                <flux:input
                    wire:model="loginEmail"
                    label="E-mail"
                    type="email"
                    placeholder="seu@email.com"
                    :error="$errors->first('loginEmail')"
                />

                <flux:input
                    wire:model="loginPassword"
                    label="Senha"
                    type="password"
                    placeholder="Sua senha"
                    :error="$errors->first('loginPassword')"
                />

                <div class="flex items-center justify-between gap-4">
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="login">Entrar</span>
                        <span wire:loading wire:target="login">Entrando...</span>
                    </flux:button>

                    <flux:button type="button" variant="ghost" wire:click="toggleLoginForm">
                        Continuar sem conta
                    </flux:button>
                </div>
            </form>
        </div>
    @else
        {{-- Guest Form --}}
        <form wire:submit="continueAsGuest" class="space-y-6">
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <flux:input
                        wire:model.blur="email"
                        label="E-mail *"
                        type="email"
                        placeholder="seu@email.com"
                        :error="$errors->first('email')"
                        description="Enviaremos a confirmacao do pedido para este e-mail"
                    />
                </div>

                <div class="sm:col-span-2">
                    <flux:input
                        wire:model="name"
                        label="Nome completo *"
                        type="text"
                        placeholder="Seu nome completo"
                        :error="$errors->first('name')"
                    />
                </div>

                <div>
                    <flux:input
                        wire:model.live.debounce.500ms="cpf"
                        label="CPF *"
                        type="text"
                        placeholder="000.000.000-00"
                        maxlength="14"
                        :error="$errors->first('cpf')"
                        description="Necessario para emissao da nota fiscal"
                    />
                </div>

                <div>
                    <flux:input
                        wire:model.live.debounce.500ms="phone"
                        label="Telefone"
                        type="tel"
                        placeholder="(00) 00000-0000"
                        maxlength="15"
                        :error="$errors->first('phone')"
                        description="Para contato sobre a entrega"
                    />
                </div>
            </div>

            <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Ja tem uma conta?
                        <button type="button" wire:click="toggleLoginForm" class="text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 font-medium">
                            Faca login
                        </button>
                    </p>

                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="continueAsGuest">Continuar</span>
                        <span wire:loading wire:target="continueAsGuest">Processando...</span>
                        <flux:icon name="arrow-right" class="size-4 ml-2" wire:loading.remove wire:target="continueAsGuest" />
                    </flux:button>
                </div>
            </div>
        </form>
    @endif
</div>
