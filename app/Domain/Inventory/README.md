# Domain: Inventory

## Responsabilidade

O dominio Inventory e responsavel pelo controle de estoque e movimentacoes de produtos.

## Escopo

- **Estoque**: Quantidade disponivel por produto/variante
- **Movimentacoes**: Entradas, saidas e ajustes de estoque
- **Alertas**: Notificacoes de estoque baixo
- **Reservas**: Reserva de estoque durante checkout
- **Historico**: Rastreamento de todas as movimentacoes

## Estrutura Esperada

```
Inventory/
├── Models/
│   ├── Stock.php
│   ├── StockMovement.php
│   └── StockAlert.php
├── Services/
│   ├── StockService.php
│   └── StockAlertService.php
├── Events/
│   ├── StockUpdated.php
│   ├── LowStockAlert.php
│   └── StockReserved.php
└── Actions/
    ├── ReserveStock.php
    ├── ReleaseStock.php
    └── AdjustStock.php
```

## Dependencias

- **Catalog**: Produtos e variantes para controle de estoque

## Dependentes

- **Cart**: Verifica disponibilidade de estoque
- **Checkout**: Reserva e confirma estoque
- **Admin**: Relatorios de estoque
