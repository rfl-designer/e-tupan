# PRD - E-commerce White-Label (RGNT)

## Visao Geral

Sistema de e-commerce white-label desenvolvido em Laravel 12 para deploy isolado por cliente. O produto oferece gestao completa de catalogo, pedidos, pagamentos e logistica para lojistas brasileiros.

## Objetivos do Produto

1. Fornecer uma loja virtual completa e pronta para uso
2. Minimizar o tempo de setup para novos clientes
3. Garantir seguranca nas transacoes e dados
4. Oferecer experiencia de compra fluida para os consumidores finais
5. Permitir gestao eficiente do negocio pelo painel administrativo

---

## Features

### F1: Painel Administrativo

#### Feature: F1-01 Autenticacao Admin com 2FA
- **ID**: F1-01
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Sistema de login seguro para administradores com autenticacao em duas etapas obrigatoria.
- **Dependencias**: []

#### Feature: F1-02 CRUD de Administradores
- **ID**: F1-02
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Gerenciamento de usuarios administrativos com roles (master, admin, operator).
- **Dependencias**: [F1-01]

#### Feature: F1-03 Dashboard Administrativo
- **ID**: F1-03
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Visao geral com metricas de vendas, pedidos, estoque e graficos de desempenho.
- **Dependencias**: [F1-01]

#### Feature: F1-04 Logs de Atividade
- **ID**: F1-04
- **Status**: `done`
- **Prioridade**: Media
- **Descricao**: Auditoria de acoes realizadas no sistema por cada administrador.
- **Dependencias**: [F1-02]

#### Feature: F1-05 Sistema de Notificacoes Admin
- **ID**: F1-05
- **Status**: `done`
- **Prioridade**: Media
- **Descricao**: Alertas em tempo real para admins sobre pedidos, estoque baixo e eventos importantes.
- **Dependencias**: [F1-01]

#### Feature: F1-06 Busca Global no Admin
- **ID**: F1-06
- **Status**: `done`
- **Prioridade**: Media
- **Descricao**: Pesquisa unificada no painel para encontrar produtos, pedidos, clientes rapidamente.
- **Dependencias**: [F1-01]

#### Feature: F1-07 Timeout de Sessao Admin
- **ID**: F1-07
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Logout automatico apos periodo de inatividade para seguranca.
- **Dependencias**: [F1-01]

#### Feature: F1-08 Configuracoes da Loja
- **ID**: F1-08
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Pagina de configuracoes para dados da loja, contato, redes sociais, SEO e email.
- **Dependencias**: [F1-01]

#### Feature: F1-09 Responsividade do Painel Admin
- **ID**: F1-09
- **Status**: `done`
- **Prioridade**: Media
- **Descricao**: Suporte completo a desktop, tablet e mobile no painel administrativo.
- **Dependencias**: [F1-01]

---

### F2: Catalogo de Produtos

#### Feature: F2-01 CRUD de Produtos
- **ID**: F2-01
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Gerenciamento completo de produtos com soft delete, duplicacao e acoes em lote.
- **Dependencias**: [F1-01]

#### Feature: F2-02 Variantes de Produtos
- **ID**: F2-02
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Suporte a produtos variaveis com combinacoes de atributos e SKU unico.
- **Dependencias**: [F2-01, F2-04]

#### Feature: F2-03 Categorias Hierarquicas
- **ID**: F2-03
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Sistema de categorias em arvore com reordenacao drag-and-drop.
- **Dependencias**: [F1-01]

#### Feature: F2-04 Atributos de Produtos
- **ID**: F2-04
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Sistema flexivel de atributos (texto, select, cor) para variantes.
- **Dependencias**: [F1-01]

#### Feature: F2-05 Upload de Imagens
- **ID**: F2-05
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Upload multiplo com geracao automatica de 3 tamanhos (large, medium, thumb).
- **Dependencias**: [F2-01]

#### Feature: F2-06 Tags de Produtos
- **ID**: F2-06
- **Status**: `done`
- **Prioridade**: Baixa
- **Descricao**: Sistema de etiquetas para organizacao e filtros de produtos.
- **Dependencias**: [F2-01]

#### Feature: F2-07 SEO de Produtos
- **ID**: F2-07
- **Status**: `done`
- **Prioridade**: Media
- **Descricao**: Campos meta_title e meta_description para otimizacao de busca.
- **Dependencias**: [F2-01]

#### Feature: F2-08 Listagem de Produtos (Storefront)
- **ID**: F2-08
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Pagina de listagem de produtos com filtros por categoria, preco, atributos e ordenacao.
- **Dependencias**: [F2-01, F2-03]

#### Feature: F2-09 Pagina de Produto (Storefront)
- **ID**: F2-09
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Pagina de detalhe do produto com galeria, variantes, descricao e botao comprar.
- **Dependencias**: [F2-01, F2-02, F2-05]

#### Feature: F2-10 Busca de Produtos (Storefront)
- **ID**: F2-10
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Campo de busca com autocomplete e resultados relevantes.
- **Dependencias**: [F2-01]

---

### F3: Gestao de Estoque

#### Feature: F3-01 Controle de Estoque
- **ID**: F3-01
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Gestao de quantidade em estoque por produto ou variante.
- **Dependencias**: [F2-01, F2-02]

#### Feature: F3-02 Movimentacoes de Estoque
- **ID**: F3-02
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Historico completo de entradas, saidas, ajustes e reservas.
- **Dependencias**: [F3-01]

#### Feature: F3-03 Reservas de Estoque
- **ID**: F3-03
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Sistema de reserva temporaria durante checkout para evitar overselling.
- **Dependencias**: [F3-01]

#### Feature: F3-04 Alertas de Estoque Baixo
- **ID**: F3-04
- **Status**: `done`
- **Prioridade**: Media
- **Descricao**: Notificacoes quando estoque atinge threshold configuravel por produto.
- **Dependencias**: [F3-01, F1-05]

#### Feature: F3-05 Dashboard de Estoque
- **ID**: F3-05
- **Status**: `done`
- **Prioridade**: Media
- **Descricao**: Visao geral com estatisticas, produtos com estoque baixo e graficos.
- **Dependencias**: [F3-01, F3-02]

---

### F4: Carrinho de Compras

#### Feature: F4-01 Carrinho de Compras
- **ID**: F4-01
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Sistema de carrinho com UUID, suporte a visitantes e usuarios logados.
- **Dependencias**: [F2-01]

#### Feature: F4-02 Adicionar ao Carrinho
- **ID**: F4-02
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Componente para adicionar produtos com selecao de variante e quantidade.
- **Dependencias**: [F4-01, F2-02]

#### Feature: F4-03 Mini Carrinho
- **ID**: F4-03
- **Status**: `done`
- **Prioridade**: Media
- **Descricao**: Resumo do carrinho no header com contador e preview de itens.
- **Dependencias**: [F4-01]

#### Feature: F4-04 Pagina do Carrinho
- **ID**: F4-04
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Pagina completa para visualizar, editar e remover itens do carrinho.
- **Dependencias**: [F4-01]

#### Feature: F4-05 Merge de Carrinhos
- **ID**: F4-05
- **Status**: `done`
- **Prioridade**: Media
- **Descricao**: Fusao automatica de carrinhos de visitante com usuario ao fazer login.
- **Dependencias**: [F4-01]

#### Feature: F4-06 Validacao de Carrinho
- **ID**: F4-06
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Verificacao de disponibilidade e estoque ao acessar carrinho/checkout.
- **Dependencias**: [F4-01, F3-01]

#### Feature: F4-07 Carrinhos Abandonados
- **ID**: F4-07
- **Status**: `done`
- **Prioridade**: Media
- **Descricao**: Deteccao e marcacao de carrinhos abandonados com listagem no admin.
- **Dependencias**: [F4-01]

---

### F5: Checkout e Pedidos

#### Feature: F5-01 Fluxo de Checkout
- **ID**: F5-01
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Processo de checkout em etapas: identificacao, endereco, frete, pagamento.
- **Dependencias**: [F4-01, F6-01]

#### Feature: F5-02 Checkout como Visitante
- **ID**: F5-02
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Permitir compra sem necessidade de criar conta.
- **Dependencias**: [F5-01]

#### Feature: F5-03 Aplicar Cupom no Checkout
- **ID**: F5-03
- **Status**: `done`
- **Prioridade**: Media
- **Descricao**: Campo para aplicar cupom de desconto durante o checkout.
- **Dependencias**: [F5-01, F8-01]

#### Feature: F5-04 Selecao de Frete
- **ID**: F5-04
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Cotacao e selecao de metodo de envio no checkout.
- **Dependencias**: [F5-01, F7-01]

#### Feature: F5-05 Pagamento com Cartao
- **ID**: F5-05
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Integracao com Mercado Pago para pagamento com cartao de credito.
- **Dependencias**: [F5-01]

#### Feature: F5-06 Pagamento com PIX
- **ID**: F5-06
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Geracao de QR Code PIX com exibicao e copia do codigo.
- **Dependencias**: [F5-01]

#### Feature: F5-07 Pagamento com Boleto
- **ID**: F5-07
- **Status**: `done`
- **Prioridade**: Media
- **Descricao**: Geracao de boleto bancario com linha digitavel e PDF.
- **Dependencias**: [F5-01]

#### Feature: F5-08 Parcelamento
- **ID**: F5-08
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Calculo e exibicao de opcoes de parcelamento no cartao.
- **Dependencias**: [F5-05]

#### Feature: F5-09 Webhooks de Pagamento
- **ID**: F5-09
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Recebimento e processamento de notificacoes de status de pagamento.
- **Dependencias**: [F5-05, F5-06, F5-07]

#### Feature: F5-10 Logs de Pagamento
- **ID**: F5-10
- **Status**: `done`
- **Prioridade**: Media
- **Descricao**: Registro detalhado de todas as transacoes para debugging e auditoria.
- **Dependencias**: [F5-05]

#### Feature: F5-11 Pagina de Sucesso
- **ID**: F5-11
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Confirmacao de pedido com resumo, numero do pedido e proximos passos.
- **Dependencias**: [F5-01]

#### Feature: F5-12 Gestao de Pedidos (Admin)
- **ID**: F5-12
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Listagem e gestao de pedidos com filtros por status, data e cliente.
- **Dependencias**: [F1-01, F5-01]

#### Feature: F5-13 Detalhes do Pedido (Admin)
- **ID**: F5-13
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Visualizacao completa do pedido com itens, pagamento, envio e historico.
- **Dependencias**: [F5-12]

#### Feature: F5-14 Acoes de Pedido (Admin)
- **ID**: F5-14
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Alterar status, adicionar notas, cancelar pedido, gerar etiqueta.
- **Dependencias**: [F5-13]

#### Feature: F5-15 Notas de Pedido
- **ID**: F5-15
- **Status**: `done`
- **Prioridade**: Media
- **Descricao**: Sistema de anotacoes internas e visiveis ao cliente no pedido.
- **Dependencias**: [F5-13]

#### Feature: F5-16 Email de Confirmacao de Pedido
- **ID**: F5-16
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Envio automatico de email com confirmacao e detalhes do pedido.
- **Dependencias**: [F5-11]

#### Feature: F5-17 Email de Atualizacao de Status
- **ID**: F5-17
- **Status**: `done`
- **Prioridade**: Media
- **Descricao**: Notificacao por email quando status do pedido muda.
- **Dependencias**: [F5-14]

---

### F6: Clientes

#### Feature: F6-01 Cadastro de Cliente
- **ID**: F6-01
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Registro de clientes com email, senha, CPF e telefone.
- **Dependencias**: []

#### Feature: F6-02 Login de Cliente
- **ID**: F6-02
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Autenticacao de clientes com email e senha.
- **Dependencias**: [F6-01]

#### Feature: F6-03 Recuperacao de Senha
- **ID**: F6-03
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Fluxo de reset de senha via email.
- **Dependencias**: [F6-01]

#### Feature: F6-04 Dashboard do Cliente
- **ID**: F6-04
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Area do cliente com resumo de pedidos e dados da conta.
- **Dependencias**: [F6-02]

#### Feature: F6-05 Gestao de Enderecos
- **ID**: F6-05
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: CRUD de enderecos do cliente com marcacao de padrao.
- **Dependencias**: [F6-04]

#### Feature: F6-06 Historico de Pedidos
- **ID**: F6-06
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Listagem de pedidos do cliente com status e detalhes.
- **Dependencias**: [F6-04, F5-01]

#### Feature: F6-07 Detalhes do Pedido (Cliente)
- **ID**: F6-07
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Visualizacao do pedido com itens, rastreamento e status.
- **Dependencias**: [F6-06]

#### Feature: F6-08 Gestao de Clientes (Admin)
- **ID**: F6-08
- **Status**: `done`
- **Prioridade**: Media
- **Descricao**: Listagem de clientes com busca, filtros e visualizacao de detalhes.
- **Dependencias**: [F1-01, F6-01]

#### Feature: F6-09 Logs de Autenticacao
- **ID**: F6-09
- **Status**: `done`
- **Prioridade**: Baixa
- **Descricao**: Historico de logins, tentativas falhas e lockouts.
- **Dependencias**: [F6-02]

---

### F7: Logistica e Frete

#### Feature: F7-01 Cotacao de Frete
- **ID**: F7-01
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Calculo de frete via Melhor Envio com multiplas transportadoras.
- **Dependencias**: []

#### Feature: F7-02 Configuracoes de Origem
- **ID**: F7-02
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Configuracao do endereco de origem para calculo de frete.
- **Dependencias**: [F7-01]

#### Feature: F7-03 Frete Gratis
- **ID**: F7-03
- **Status**: `done`
- **Prioridade**: Media
- **Descricao**: Configuracao de frete gratis acima de valor minimo ou por regiao.
- **Dependencias**: [F7-01]

#### Feature: F7-04 Handling Time
- **ID**: F7-04
- **Status**: `done`
- **Prioridade**: Baixa
- **Descricao**: Configuracao de tempo de preparacao somado ao prazo de entrega.
- **Dependencias**: [F7-01]

#### Feature: F7-05 Geracao de Etiquetas
- **ID**: F7-05
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Criacao de etiquetas de envio via Melhor Envio.
- **Dependencias**: [F7-01, F5-01]

#### Feature: F7-06 Rastreamento de Entregas
- **ID**: F7-06
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Acompanhamento de entregas em tempo real com atualizacoes.
- **Dependencias**: [F7-05]

#### Feature: F7-07 Webhooks de Rastreamento
- **ID**: F7-07
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Recebimento automatico de atualizacoes de status de entrega.
- **Dependencias**: [F7-06]

#### Feature: F7-08 Pagina de Rastreamento Publica
- **ID**: F7-08
- **Status**: `done`
- **Prioridade**: Media
- **Descricao**: Pagina para clientes rastrearem pedidos com numero do pedido ou codigo.
- **Dependencias**: [F7-06]

#### Feature: F7-09 Gestao de Envios (Admin)
- **ID**: F7-09
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Listagem e gestao de envios com status e acoes.
- **Dependencias**: [F1-01, F7-05]

---

### F8: Marketing

#### Feature: F8-01 CRUD de Cupons
- **ID**: F8-01
- **Status**: `done`
- **Prioridade**: Media
- **Descricao**: Criacao de cupons com desconto fixo ou percentual.
- **Dependencias**: [F1-01]

#### Feature: F8-02 Regras de Cupom
- **ID**: F8-02
- **Status**: `done`
- **Prioridade**: Media
- **Descricao**: Limites por uso total, por usuario, valor minimo e maximo de desconto.
- **Dependencias**: [F8-01]

#### Feature: F8-03 Validade de Cupom
- **ID**: F8-03
- **Status**: `done`
- **Prioridade**: Media
- **Descricao**: Data de inicio e expiracao para cupons.
- **Dependencias**: [F8-01]

#### Feature: F8-04 Produtos em Promocao
- **ID**: F8-04
- **Status**: `done`
- **Prioridade**: Media
- **Descricao**: Preco promocional com data de inicio e fim.
- **Dependencias**: [F2-01]

#### Feature: F8-05 Banner Promocional
- **ID**: F8-05
- **Status**: `done`
- **Prioridade**: Baixa
- **Descricao**: Gestao de banners na homepage com link e periodo de exibicao.
- **Dependencias**: [F1-01]

---

### F9: Storefront

#### Feature: F9-01 Homepage
- **ID**: F9-01
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Pagina inicial com banner, produtos em destaque, categorias e promocoes.
- **Dependencias**: [F2-01, F2-03]

#### Feature: F9-02 Header/Menu
- **ID**: F9-02
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Header com logo, menu de categorias, busca, conta e mini carrinho.
- **Dependencias**: [F2-03, F4-03]

#### Feature: F9-03 Footer
- **ID**: F9-03
- **Status**: `done`
- **Prioridade**: Media
- **Descricao**: Footer com links institucionais, contato, redes sociais e selos.
- **Dependencias**: []

#### Feature: F9-04 Paginas Institucionais
- **ID**: F9-04
- **Status**: `todo`
- **Prioridade**: Baixa
- **Descricao**: Paginas estaticas: Sobre, Contato, Politica de Privacidade, Termos.
- **Dependencias**: []

#### Feature: F9-05 Responsividade Storefront
- **ID**: F9-05
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Layout responsivo para desktop, tablet e mobile.
- **Dependencias**: [F9-01, F9-02]

---

### F10: Integracoes Futuras

#### Feature: F10-01 Integracao PagSeguro
- **ID**: F10-01
- **Status**: `todo`
- **Prioridade**: Media
- **Descricao**: Gateway de pagamento alternativo ao Mercado Pago.
- **Dependencias**: [F5-05]

#### Feature: F10-02 Integracao Stripe
- **ID**: F10-02
- **Status**: `todo`
- **Prioridade**: Baixa
- **Descricao**: Gateway de pagamento internacional.
- **Dependencias**: [F5-05]

#### Feature: F10-03 Storage S3
- **ID**: F10-03
- **Status**: `todo`
- **Prioridade**: Media
- **Descricao**: Armazenamento de imagens em Amazon S3 ou compativel.
- **Dependencias**: [F2-05]

#### Feature: F10-04 Envio de Emails Transacionais
- **ID**: F10-04
- **Status**: `done`
- **Prioridade**: Alta
- **Descricao**: Configuracao de SMTP/Mailgun/SendGrid para emails do sistema.
- **Dependencias**: []

---

## Metricas de Sucesso

- **Taxa de conversao**: Percentual de visitantes que completam uma compra
- **Abandono de carrinho**: Percentual de carrinhos abandonados antes do checkout
- **Tempo de checkout**: Duracao media do processo de compra
- **Uptime**: Disponibilidade do sistema (meta: 99.9%)
- **Tempo de resposta**: Latencia media das paginas (meta: < 2s)
- **NPS do lojista**: Satisfacao dos clientes com o painel administrativo

---

## Cronograma Sugerido

| Fase | Features | Foco |
|------|----------|------|
| **MVP Core** | F9-01, F9-02, F9-03, F9-05, F2-08, F2-09, F2-10 | Storefront basico |
| **MVP Completo** | F6-06, F6-07, F5-16, F5-17, F10-04 | Emails e area do cliente |
| **V1.1** | F8-05, F9-04, F1-08, F10-03 | Melhorias e storage |
| **V1.2** | F10-01, F10-02 | Gateways alternativos |

---

## Resumo de Status

| Status | Quantidade |
|--------|------------|
| `done` | 74 features |
| `in-progress` | 0 features |
| `todo` | 6 features |

---

## Features Pendentes (TODO)

| ID | Nome | Prioridade |
|----|------|------------|
| F8-05 | Banner Promocional | Baixa |
| F9-04 | Paginas Institucionais | Baixa |
| F10-01 | Integracao PagSeguro | Media |
| F10-02 | Integracao Stripe | Baixa |
| F10-03 | Storage S3 | Media |
