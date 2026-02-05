# Domain: Customer

## Responsabilidade

O dominio Customer e responsavel pela gestao de clientes, enderecos e dados pessoais.

## Escopo

- **Clientes**: Cadastro e gestao de clientes
- **Enderecos**: Multiplos enderecos por cliente
- **Perfil**: Dados pessoais e preferencias
- **Historico**: Pedidos e atividades do cliente
- **Wishlist**: Lista de desejos (futuro)

## Estrutura Esperada

```
Customer/
├── Models/
│   ├── Customer.php
│   ├── Address.php
│   └── Wishlist.php
├── Services/
│   ├── CustomerService.php
│   └── AddressService.php
├── Events/
│   ├── CustomerRegistered.php
│   └── AddressUpdated.php
├── Actions/
│   ├── CreateCustomer.php
│   ├── UpdateCustomer.php
│   └── AddAddress.php
└── DTOs/
    └── CustomerData.php
```

## Dependencias

- Nenhuma dependencia de outros dominios

## Dependentes

- **Cart**: Associacao de carrinho ao cliente
- **Checkout**: Dados do cliente no pedido
- **Shipping**: Endereco de entrega
- **Admin**: Gestao de clientes
