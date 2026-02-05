# Domain: Checkout

## Responsabilidade

O dominio Checkout e responsavel pelo processo de finalizacao de compra e gestao de pedidos.

## Escopo

- **Pedidos**: Criacao e gestao de pedidos
- **Pagamento**: Processamento via gateways
- **Confirmacao**: Emails e notificacoes
- **Status**: Fluxo de status do pedido
- **Historico**: Historico de pedidos do cliente

## Estrutura Esperada

```
Checkout/
├── Models/
│   ├── Order.php
│   ├── OrderItem.php
│   ├── OrderStatus.php
│   └── Payment.php
├── Services/
│   ├── CheckoutService.php
│   ├── OrderService.php
│   └── PaymentService.php
├── Events/
│   ├── OrderCreated.php
│   ├── OrderPaid.php
│   ├── OrderShipped.php
│   └── OrderCompleted.php
├── Actions/
│   ├── CreateOrder.php
│   ├── ProcessPayment.php
│   └── UpdateOrderStatus.php
└── Enums/
    ├── OrderStatus.php
    └── PaymentStatus.php
```

## Dependencias

- **Cart**: Itens do carrinho para criar pedido
- **Catalog**: Informacoes dos produtos
- **Inventory**: Reserva e confirmacao de estoque
- **Customer**: Dados do cliente e endereco
- **Shipping**: Calculo e selecao de frete
- **Infrastructure/Payment**: Gateways de pagamento

## Dependentes

- **Admin**: Gestao de pedidos
- **Customer**: Historico de pedidos
