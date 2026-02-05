<div>
    <form wire:submit="save" class="space-y-6">
        {{-- Remetente --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <flux:input
                wire:model="sender_name"
                label="Nome do remetente"
                placeholder="Minha Loja"
                description="Nome que aparece como remetente dos emails"
                required
            />

                <flux:input
                    wire:model="sender_email"
                    type="email"
                    label="Email do remetente"
                    placeholder="noreply@minhaloja.com"
                    description="Endereco de email usado para enviar mensagens"
                    required
                />
            </div>
        </div>

        <flux:separator />

        {{-- Provedor de Email --}}
        <div class="space-y-4">
            <flux:heading size="lg">Provedor de Email</flux:heading>
            <flux:text class="text-zinc-500">
                Selecione o servico que sera usado para enviar emails transacionais.
            </flux:text>

            <flux:select
                wire:model.live="driver"
                label="Provedor"
                description="Driver de email a ser utilizado"
            >
                @foreach($drivers as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        {{-- Campos SMTP --}}
        @if($driver === 'smtp')
            <flux:separator />
            <div class="space-y-4">
                <flux:heading size="lg">Configuracao SMTP</flux:heading>
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <flux:input
                        wire:model="smtp_host"
                        label="Host SMTP"
                        placeholder="smtp.exemplo.com"
                        description="Endereco do servidor SMTP"
                        required
                    />

                    <flux:input
                        wire:model="smtp_port"
                        label="Porta"
                        placeholder="587"
                        description="Porta do servidor (geralmente 587 ou 465)"
                        type="number"
                        min="1"
                        max="65535"
                        required
                    />

                    <flux:input
                        wire:model="smtp_username"
                        label="Usuario"
                        placeholder="usuario@exemplo.com"
                        description="Usuario para autenticacao"
                    />

                    <flux:input
                        wire:model="smtp_password"
                        type="password"
                        label="Senha"
                        placeholder="********"
                        description="Senha para autenticacao"
                    />

                    <flux:select
                        wire:model="smtp_encryption"
                        label="Criptografia"
                        description="Tipo de criptografia da conexao"
                    >
                        @foreach($encryptionOptions as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        @endif

        {{-- Campos Mailgun --}}
        @if($driver === 'mailgun')
            <flux:separator />
            <div class="space-y-4">
                <flux:heading size="lg">Configuracao Mailgun</flux:heading>
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <flux:input
                        wire:model="mailgun_domain"
                        label="Dominio"
                        placeholder="mg.exemplo.com"
                        description="Dominio configurado no Mailgun"
                        required
                    />

                    <flux:input
                        wire:model="mailgun_secret"
                        type="password"
                        label="Secret Key"
                        placeholder="key-xxxxxxxxxxxxxxxx"
                        description="Chave de API do Mailgun"
                        required
                    />

                    <flux:select
                        wire:model="mailgun_endpoint"
                        label="Endpoint"
                        description="Regiao do servidor Mailgun"
                    >
                        @foreach($mailgunEndpoints as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        @endif

        {{-- Campos Amazon SES --}}
        @if($driver === 'ses')
            <flux:separator />
            <div class="space-y-4">
                <flux:heading size="lg">Configuracao Amazon SES</flux:heading>
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <flux:input
                        wire:model="ses_key"
                        type="password"
                        label="Access Key"
                        placeholder="AKIAXXXXXXXXXXXXXXXX"
                        description="AWS Access Key ID"
                        required
                    />

                    <flux:input
                        wire:model="ses_secret"
                        type="password"
                        label="Secret Key"
                        placeholder="********"
                        description="AWS Secret Access Key"
                        required
                    />

                    <flux:select
                        wire:model="ses_region"
                        label="Regiao"
                        description="Regiao AWS do SES"
                    >
                        @foreach($sesRegions as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        @endif

        {{-- Campos Postmark --}}
        @if($driver === 'postmark')
            <flux:separator />
            <div class="space-y-4">
                <flux:heading size="lg">Configuracao Postmark</flux:heading>
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <flux:input
                        wire:model="postmark_token"
                        type="password"
                        label="Token"
                        placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                        description="Server API Token do Postmark"
                        required
                    />
                </div>
            </div>
        @endif

        {{-- Campos Resend --}}
        @if($driver === 'resend')
            <flux:separator />
            <div class="space-y-4">
                <flux:heading size="lg">Configuracao Resend</flux:heading>
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <flux:input
                        wire:model="resend_key"
                        type="password"
                        label="API Key"
                        placeholder="re_xxxxxxxxxx"
                        description="Chave de API do Resend"
                        required
                    />
                </div>
            </div>
        @endif

        {{-- Log (desenvolvimento) --}}
        @if($driver === 'log')
            <flux:callout variant="warning" icon="information-circle">
                <flux:callout.heading>Modo Desenvolvimento</flux:callout.heading>
                <flux:callout.text>
                    Os emails serao gravados no log da aplicacao em vez de enviados. Configure um provedor de email para producao.
                </flux:callout.text>
            </flux:callout>
        @endif

        <flux:separator />

        {{-- Testar Configuracao --}}
        <div class="space-y-4">
            <flux:heading size="lg">Testar Configuracao</flux:heading>
            <flux:text class="text-zinc-500">
                Envie um email de teste para verificar se a configuracao esta funcionando corretamente.
            </flux:text>

            <flux:modal.trigger name="test-email-modal">
                <flux:button variant="outline" icon="paper-airplane">
                    Enviar Email de Teste
                </flux:button>
            </flux:modal.trigger>
        </div>

        <flux:separator />

        {{-- Notificacoes de Pedidos --}}
        <div class="space-y-4">
            <flux:heading size="lg">Notificacoes de Pedidos</flux:heading>
            <flux:text class="text-zinc-500">
                Selecione quais mudancas de status devem enviar notificacao por email ao cliente.
            </flux:text>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:checkbox
                        wire:model="notify_status_processing"
                        label="Pagamento Confirmado"
                        description="Enviar email quando pagamento for aprovado"
                    />
                </div>

                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:checkbox
                        wire:model="notify_status_shipped"
                        label="Pedido Enviado"
                        description="Enviar email com codigo de rastreio"
                    />
                </div>

                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:checkbox
                        wire:model="notify_status_completed"
                        label="Pedido Entregue"
                        description="Enviar email quando entrega for confirmada"
                    />
                </div>

                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:checkbox
                        wire:model="notify_status_cancelled"
                        label="Pedido Cancelado"
                        description="Enviar email em caso de cancelamento"
                    />
                </div>

                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:checkbox
                        wire:model="notify_status_refunded"
                        label="Pedido Reembolsado"
                        description="Enviar email quando reembolso for processado"
                    />
                </div>
            </div>
        </div>

        <flux:separator />

        {{-- Templates de Email --}}
        <div class="space-y-4">
            <flux:heading size="lg">Templates de Email</flux:heading>
            <flux:text class="text-zinc-500">
                Os templates de email sao gerenciados pelo sistema e nao podem ser editados por esta interface.
            </flux:text>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    ['icon' => 'shopping-bag', 'title' => 'Confirmacao de Pedido', 'desc' => 'Enviado apos finalizar compra'],
                    ['icon' => 'credit-card', 'title' => 'Pagamento Aprovado', 'desc' => 'Enviado apos confirmacao'],
                    ['icon' => 'truck', 'title' => 'Pedido Enviado', 'desc' => 'Enviado com codigo de rastreio'],
                    ['icon' => 'check-circle', 'title' => 'Pedido Entregue', 'desc' => 'Enviado apos entrega'],
                    ['icon' => 'x-circle', 'title' => 'Pedido Cancelado', 'desc' => 'Enviado em caso de cancelamento'],
                    ['icon' => 'key', 'title' => 'Reset de Senha', 'desc' => 'Enviado para recuperacao'],
                ] as $template)
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="flex items-start gap-3">
                            <flux:icon name="{{ $template['icon'] }}" class="size-5 text-zinc-400" />
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $template['title'] }}</p>
                                <p class="text-sm text-zinc-500">{{ $template['desc'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <flux:button
                type="button"
                variant="ghost"
                icon="paper-airplane"
                wire:click="openTestEmailModal"
            >
                Enviar email de teste
            </flux:button>

            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Salvar Alteracoes</span>
                <span wire:loading wire:target="save">Salvando...</span>
            </flux:button>
        </div>
    </form>

    {{-- Test Email Modal --}}
    <flux:modal wire:model="showTestEmailModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Enviar Email de Teste</flux:heading>
                <flux:text class="mt-1 text-zinc-500">
                    Informe o email de destino para enviar um email de teste.
                </flux:text>
            </div>

            <flux:input
                wire:model="testEmail"
                type="email"
                label="Email de destino"
                placeholder="seuemail@exemplo.com"
                required
            />

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="closeTestEmailModal">
                    Cancelar
                </flux:button>
                <flux:button
                    variant="primary"
                    wire:click="sendTestEmail"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="sendTestEmail">Enviar</span>
                    <span wire:loading wire:target="sendTestEmail">Enviando...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
