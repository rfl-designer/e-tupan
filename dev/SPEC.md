# Especificação - E-commerce White-Label (RGNT)

## Visao Geral

Sistema de e-commerce white-label desenvolvido em Laravel 12 para deploy isolado por cliente, com customizacao visual manual durante o setup. O modelo de negocio baseia-se em cobranca de setup + mensalidade por manutencao/hospedagem.

**Publico-alvo:** Lojistas que precisam de uma loja virtual completa com gestao de produtos, pedidos, pagamentos e logistica.

## Objetivo

Fornecer uma plataforma de e-commerce completa e customizavel que permita aos lojistas:

- Gerenciar catalogo de produtos com variantes e atributos
- Processar pedidos e pagamentos de forma segura
- Controlar estoque com alertas e reservas
- Integrar com gateways de pagamento brasileiros
- Calcular frete e rastrear entregas
- Aplicar cupons e promocoes

## Stack Tecnologico

| Camada | Tecnologia |
|--------|------------|
| **Framework** | Laravel 12.46.0 |
| **PHP** | 8.4.15 |
| **Frontend** | Livewire v3.7.3 + Flux UI 2.10.2 + AlpineJS |
| **Styling** | TailwindCSS 4.1.11 |
| **Auth Admin** | Laravel Fortify 1.33.0 (com 2FA) |
| **Auth API** | Laravel Sanctum |
| **Database** | MySQL 8 / PostgreSQL / SQLite (dev) |
| **Testing** | Pest PHP 4.3.1 |
| **Linting** | PHPStan (Larastan 3.8.1) + Laravel Pint 1.27.0 |

## Arquitetura (Domain-Driven Design)

O projeto segue DDD com a seguinte estrutura de dominios:

```
app/Domain/
├── Admin/           # Painel administrativo, admins, autenticacao 2FA
├── Catalog/         # Produtos, categorias, atributos, variantes, imagens, tags
├── Inventory/       # Estoque, movimentacoes, reservas, alertas
├── Cart/            # Carrinho, sessao, cupons, abandono
├── Checkout/        # Pedidos, pagamentos, webhooks
├── Shipping/        # Frete, rastreamento, integracoes
├── Customer/        # Clientes, enderecos, autenticacao
└── Marketing/       # Cupons, promocoes
```

## Funcionalidades Implementadas

### 1. Gestao de Catalogo (Domain: Catalog)

- **Produtos**: CRUD completo com soft delete, duplicacao, acoes em lote
- **Variantes**: Suporte a produtos simples e variaveis com SKU unico
- **Atributos**: Sistema flexivel de atributos (texto, select, cor) com valores
- **Categorias**: Hierarquia em arvore (nested set) com reordenacao drag-and-drop
- **Imagens**: Upload multiplo com 3 tamanhos (large 800x800, medium 400x400, thumb 150x150)
- **Tags**: Sistema de etiquetas para produtos
- **SEO**: Campos meta_title e meta_description

### 2. Gestao de Estoque (Domain: Inventory)

- **Controle de estoque**: Por produto simples ou por variante
- **Movimentacoes**: Historico completo (entrada, saida, ajuste, reserva)
- **Reservas**: Sistema de reserva temporaria durante checkout
- **Alertas**: Notificacoes de estoque baixo configuravel por produto
- **Dashboard**: Visao geral com estatisticas e graficos

### 3. Carrinho de Compras (Domain: Cart)

- **UUID**: Identificacao unica do carrinho
- **Sessao/Usuario**: Suporte a carrinhos de visitantes e usuarios
- **Merge**: Fusao automatica de carrinhos no login
- **Validacao**: Verificacao de disponibilidade e estoque
- **Abandono**: Deteccao e marcacao de carrinhos abandonados
- **Limpeza**: Jobs para limpeza de carrinhos antigos

### 4. Checkout e Pedidos (Domain: Checkout)

- **Pedidos**: Fluxo completo com UUID e numero sequencial
- **Status**: pending, processing, confirmed, shipped, delivered, cancelled
- **Pagamento**: Integracao com gateways (Mercado Pago implementado)
- **Metodos**: Cartao de credito, PIX, boleto
- **Parcelamento**: Calculo de parcelas via API
- **Webhooks**: Recebimento de notificacoes de pagamento
- **Logs**: Registro detalhado de todas as transacoes

### 5. Logistica e Frete (Domain: Shipping)

- **Cotacao**: Calculo de frete via Melhor Envio
- **Multiplas transportadoras**: Correios, Jadlog, etc.
- **Etiquetas**: Geracao de etiquetas de envio
- **Rastreamento**: Acompanhamento de entregas em tempo real
- **Webhooks**: Atualizacoes automaticas de status
- **Configuracoes**: Origem, handling time, frete gratis

### 6. Clientes (Domain: Customer)

- **Cadastro**: Registro com email, CPF, telefone
- **Enderecos**: Multiplos enderecos com padrao
- **Dashboard**: Area do cliente com pedidos e dados
- **Autenticacao**: Login, registro, 2FA, recuperacao de senha
- **Logs**: Historico de autenticacao

### 7. Marketing (Domain: Marketing)

- **Cupons**: Desconto fixo ou percentual
- **Limites**: Por uso total e por usuario
- **Validade**: Data de inicio e expiracao
- **Minimo**: Valor minimo do pedido
- **Maximo**: Desconto maximo (percentual)

### 8. Painel Administrativo (Domain: Admin)

- **Dashboard**: Visao geral com metricas e graficos
- **Admins**: CRUD de administradores com roles (master, admin, operator)
- **2FA obrigatorio**: Autenticacao em duas etapas
- **Logs de atividade**: Auditoria de acoes no sistema
- **Notificacoes**: Sistema de alertas para admins
- **Busca global**: Pesquisa unificada no painel
- **Sessoes**: Timeout automatico por inatividade
- **Responsivo**: Suporte a desktop, tablet e mobile

## Integrações

### Implementadas

| Integracao | Servico | Status |
|------------|---------|--------|
| **Pagamentos** | Mercado Pago | Implementado |
| **Frete** | Melhor Envio | Implementado |
| **Imagens** | Storage local (S3-ready) | Implementado |

### Planejadas (MVP)

| Integracao | Servicos |
|------------|----------|
| **Pagamentos** | PagSeguro, Stripe |
| **Storage** | Amazon S3, DigitalOcean Spaces |
| **Email** | SMTP, Mailgun, SendGrid |

## Modelos de Dados

### Principais Entidades

| Entidade | Tabela | Descricao |
|----------|--------|-----------|
| Product | products | Produtos do catalogo |
| ProductVariant | product_variants | Variantes de produtos |
| Category | categories | Categorias hierarquicas |
| Attribute | attributes | Atributos de produtos |
| Cart | carts | Carrinhos de compras |
| Order | orders | Pedidos |
| Payment | payments | Pagamentos |
| Shipment | shipments | Envios |
| User | users | Clientes |
| Admin | admins | Administradores |
| Coupon | coupons | Cupons de desconto |

### Convencoes de Dados

- **Precos**: Sempre em centavos (inteiros) para evitar problemas de precisao
- **IDs**: UUID para entidades principais (cart, order, payment, shipment, coupon)
- **Datas**: Campos *_at para timestamps de eventos
- **Soft Delete**: deleted_at em products, orders, admins, coupons

## Rotas Principais

### Storefront (Publico)

| Rota | Descricao |
|------|-----------|
| `/` | Homepage |
| `/carrinho` | Carrinho de compras |
| `/checkout` | Processo de checkout |
| `/checkout/sucesso/{order}` | Confirmacao de pedido |
| `/minha-conta` | Dashboard do cliente |
| `/minha-conta/enderecos` | Gestao de enderecos |
| `/rastreio` | Rastreamento de pedidos |

### Admin

| Rota | Descricao |
|------|-----------|
| `/admin` | Dashboard |
| `/admin/products` | Gestao de produtos |
| `/admin/categories` | Gestao de categorias |
| `/admin/attributes` | Gestao de atributos |
| `/admin/orders` | Gestao de pedidos |
| `/admin/customers` | Gestao de clientes |
| `/admin/inventory` | Gestao de estoque |
| `/admin/coupons` | Gestao de cupons |
| `/admin/shipping` | Gestao de envios |
| `/admin/settings` | Configuracoes |

## Escopo

### Incluido no MVP

- Catalogo completo de produtos com variantes
- Carrinho de compras com validacao de estoque
- Checkout com pagamento via Mercado Pago (cartao, PIX, boleto)
- Calculo de frete via Melhor Envio
- Rastreamento de entregas
- Cupons de desconto
- Painel administrativo completo
- Area do cliente
- Autenticacao 2FA para admins

### Nao Incluido no MVP

- Sistema de avaliacoes/reviews de produtos
- Lista de desejos (wishlist)
- Comparacao de produtos
- Sistema de pontos/fidelidade
- Multi-idioma
- Multi-moeda
- Marketplace (multi-vendor)
- App mobile nativo
- Chat ao vivo
- Integracao com ERP

## Requisitos Tecnicos

### Ambiente de Desenvolvimento

- PHP >= 8.4
- Composer 2.x
- Node.js >= 18.x
- MySQL 8 ou PostgreSQL 14+

### Padroes de Codigo

- Strict types habilitado
- Typed properties e return types obrigatorios
- Constructor promotion quando possivel
- Early returns (sem else)
- Form Requests para validacao
- Query Scopes para consultas reutilizaveis
- Eager loading para evitar N+1

### Testes

- Pest PHP para testes
- Feature tests para fluxos completos
- Unit tests para logica isolada
- Factories para geracao de dados
- Helper `actingAsAdminWith2FA()` para testes admin

## Proximos Passos

1. Executar `/laraflow:prd` para criar o PRD com as features detalhadas
2. Priorizar features pendentes do backlog
3. Definir roadmap de desenvolvimento
