# Domain: Admin

## Responsabilidade

O dominio Admin e responsavel pelo painel administrativo, dashboard e relatorios.

## Escopo

- **Dashboard**: Metricas e KPIs principais
- **Relatorios**: Vendas, produtos, clientes
- **Gestao**: Interface administrativa
- **Configuracoes**: Parametros da loja
- **Usuarios Admin**: Gestao de administradores

## Estrutura Esperada

```
Admin/
├── Models/
│   ├── AdminUser.php
│   └── Setting.php
├── Services/
│   ├── DashboardService.php
│   ├── ReportService.php
│   └── SettingsService.php
├── Reports/
│   ├── SalesReport.php
│   ├── ProductsReport.php
│   └── CustomersReport.php
├── Actions/
│   ├── GenerateReport.php
│   └── UpdateSettings.php
└── DTOs/
    ├── DashboardMetrics.php
    └── ReportData.php
```

## Dependencias

- **Catalog**: Dados de produtos para relatorios
- **Inventory**: Dados de estoque
- **Checkout**: Dados de pedidos e vendas
- **Customer**: Dados de clientes
- **Shipping**: Dados de envios

## Dependentes

- Nenhum (dominio de consumo)
