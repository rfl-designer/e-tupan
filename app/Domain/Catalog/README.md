# Domain: Catalog

## Responsabilidade

O dominio Catalog e responsavel por toda a gestao do catalogo de produtos da loja.

## Escopo

- **Produtos**: Criacao, edicao, listagem e remocao de produtos
- **Categorias**: Hierarquia de categorias e subcategorias
- **Atributos**: Atributos customizaveis (cor, tamanho, material, etc.)
- **Variantes**: Variacoes de produtos (SKUs diferentes)
- **Imagens**: Upload e gestao de imagens de produtos
- **Precos**: Precos base, promocionais e por quantidade

## Estrutura Esperada

```
Catalog/
├── Models/
│   ├── Product.php
│   ├── Category.php
│   ├── Attribute.php
│   ├── AttributeValue.php
│   ├── ProductVariant.php
│   └── ProductImage.php
├── Services/
│   ├── ProductService.php
│   └── CategoryService.php
├── Repositories/
│   ├── ProductRepository.php
│   └── CategoryRepository.php
├── Events/
│   ├── ProductCreated.php
│   └── ProductUpdated.php
└── Actions/
    ├── CreateProduct.php
    └── UpdateProduct.php
```

## Dependencias

- Nenhuma dependencia de outros dominios

## Dependentes

- **Cart**: Usa produtos para adicionar ao carrinho
- **Inventory**: Gerencia estoque dos produtos
- **Checkout**: Usa produtos para criar pedidos
