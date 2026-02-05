# Domain: Cart

## Responsabilidade

O dominio Cart e responsavel pela gestao do carrinho de compras dos clientes.

## Escopo

- **Carrinho**: Adicionar, remover e atualizar itens
- **Sessao**: Carrinho para usuarios nao autenticados
- **Merge**: Unificacao de carrinho ao fazer login
- **Calculos**: Subtotal, descontos e totais
- **Cupons**: Aplicacao de cupons de desconto

## Estrutura Esperada

```
Cart/
├── Models/
│   ├── Cart.php
│   └── CartItem.php
├── Services/
│   ├── CartService.php
│   └── CartMergeService.php
├── Events/
│   ├── ItemAddedToCart.php
│   ├── ItemRemovedFromCart.php
│   └── CartMerged.php
└── Actions/
    ├── AddToCart.php
    ├── RemoveFromCart.php
    ├── UpdateCartItem.php
    └── MergeCarts.php
```

## Dependencias

- **Catalog**: Produtos para adicionar ao carrinho
- **Inventory**: Verificacao de disponibilidade
- **Customer**: Usuario dono do carrinho

## Dependentes

- **Checkout**: Usa carrinho para criar pedido
