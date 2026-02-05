<x-admin-layout
    title="Ajuda"
    :breadcrumbs="[['label' => 'Ajuda']]"
>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('Central de Ajuda') }}</flux:heading>
                <flux:subheading>{{ __('Encontre respostas para suas duvidas') }}</flux:subheading>
            </div>
        </div>

        {{-- Contact Card --}}
        <div class="rounded-xl border border-zinc-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 dark:border-zinc-700 dark:from-blue-900/20 dark:to-indigo-900/20">
            <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <flux:heading size="lg">Precisa de suporte?</flux:heading>
                    <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                        Nossa equipe esta disponivel para ajudar com qualquer duvida ou problema.
                    </flux:text>
                </div>
                <div class="flex gap-3">
                    <flux:button
                        href="mailto:suporte@exemplo.com"
                        variant="primary"
                        icon="envelope"
                    >
                        Enviar Email
                    </flux:button>
                </div>
            </div>
        </div>

        {{-- Quick Links --}}
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([
                ['icon' => 'cube', 'title' => 'Produtos', 'desc' => 'Cadastro e gestao de produtos', 'url' => route('admin.products.index')],
                ['icon' => 'shopping-bag', 'title' => 'Pedidos', 'desc' => 'Acompanhe suas vendas', 'url' => route('admin.orders.index')],
                ['icon' => 'users', 'title' => 'Clientes', 'desc' => 'Base de clientes', 'url' => route('admin.customers.index')],
                ['icon' => 'cog-6-tooth', 'title' => 'Configuracoes', 'desc' => 'Ajustes da loja', 'url' => route('admin.settings.index')],
            ] as $link)
                <a
                    href="{{ $link['url'] }}"
                    class="group rounded-xl border border-zinc-200 bg-white p-5 transition-all hover:border-blue-300 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-blue-600"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 transition-colors group-hover:bg-blue-200 dark:bg-blue-900/30">
                        <flux:icon name="{{ $link['icon'] }}" class="size-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <flux:heading size="base" class="mt-3">{{ $link['title'] }}</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-500">{{ $link['desc'] }}</flux:text>
                </a>
            @endforeach
        </div>

        {{-- FAQ --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <flux:heading size="lg">Perguntas Frequentes</flux:heading>
            </div>
            <div x-data="{ openFaq: null }" class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach([
                    [
                        'q' => 'Como cadastrar um novo produto?',
                        'a' => 'Acesse o menu Produtos, clique em "Novo Produto" e preencha as informacoes necessarias como nome, preco, descricao e imagens. Voce pode criar produtos simples ou com variantes (tamanhos, cores, etc).'
                    ],
                    [
                        'q' => 'Como processar um pedido?',
                        'a' => 'Na lista de pedidos, clique no pedido desejado para ver os detalhes. Apos confirmar o pagamento, voce pode gerar etiqueta de envio, marcar como enviado e acompanhar a entrega.'
                    ],
                    [
                        'q' => 'Como configurar os metodos de pagamento?',
                        'a' => 'Acesse Configuracoes > Pagamento para selecionar o gateway ativo e o ambiente (sandbox ou producao). As credenciais de API devem ser configuradas no arquivo .env por seguranca.'
                    ],
                    [
                        'q' => 'Como configurar o frete?',
                        'a' => 'Acesse Configuracoes > Frete ou o menu Envios > Configuracoes. La voce pode configurar as transportadoras disponiveis, valores de frete gratis e endereco de origem.'
                    ],
                    [
                        'q' => 'Como criar cupons de desconto?',
                        'a' => 'Acesse o menu Cupons e clique em "Novo Cupom". Defina o codigo, tipo de desconto (percentual ou valor fixo), limite de uso e periodo de validade.'
                    ],
                    [
                        'q' => 'Como exportar relatorios?',
                        'a' => 'Em diversas telas como Pedidos, Clientes e Logs, existe um botao "Exportar CSV" que permite baixar os dados filtrados em formato de planilha.'
                    ],
                ] as $index => $faq)
                    <div class="px-6 py-4">
                        <button
                            type="button"
                            @click="openFaq = openFaq === {{ $index }} ? null : {{ $index }}"
                            class="flex w-full items-center justify-between gap-4 text-left"
                        >
                            <span class="font-medium text-zinc-900 dark:text-white">
                                {{ $faq['q'] }}
                            </span>
                            <flux:icon
                                name="chevron-down"
                                class="size-5 shrink-0 text-zinc-400 transition-transform"
                                x-bind:class="{ 'rotate-180': openFaq === {{ $index }} }"
                            />
                        </button>
                        <div
                            x-show="openFaq === {{ $index }}"
                            x-collapse
                            x-cloak
                            class="mt-3 text-zinc-600 dark:text-zinc-400"
                        >
                            {{ $faq['a'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Shortcuts --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">Atalhos de Teclado</flux:heading>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    ['keys' => ['Ctrl', 'K'], 'desc' => 'Abrir busca global'],
                    ['keys' => ['Esc'], 'desc' => 'Fechar modal/dropdown'],
                ] as $shortcut)
                    <div class="flex items-center gap-3">
                        <div class="flex gap-1">
                            @foreach($shortcut['keys'] as $key)
                                <kbd class="rounded bg-zinc-100 px-2 py-1 text-sm font-medium text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">
                                    {{ $key }}
                                </kbd>
                                @if(!$loop->last)
                                    <span class="text-zinc-400">+</span>
                                @endif
                            @endforeach
                        </div>
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $shortcut['desc'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-admin-layout>
