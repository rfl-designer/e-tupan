# Domain: Shipping

## Responsabilidade

O dominio Shipping e responsavel pelo calculo de frete, rastreamento e integracao com transportadoras.

## Escopo

- **Calculo de Frete**: Cotacao de frete por CEP
- **Metodos de Envio**: PAC, SEDEX, transportadoras
- **Rastreamento**: Acompanhamento de entregas
- **Etiquetas**: Geracao de etiquetas de envio
- **Prazos**: Estimativa de entrega

## Estrutura Esperada

```
Shipping/
├── Models/
│   ├── ShippingMethod.php
│   ├── ShippingZone.php
│   └── Tracking.php
├── Services/
│   ├── ShippingCalculator.php
│   ├── TrackingService.php
│   └── LabelService.php
├── Events/
│   ├── ShipmentCreated.php
│   ├── ShipmentDispatched.php
│   └── ShipmentDelivered.php
├── Actions/
│   ├── CalculateShipping.php
│   ├── CreateShipment.php
│   └── GenerateLabel.php
└── DTOs/
    ├── ShippingQuote.php
    └── TrackingInfo.php
```

## Dependencias

- **Customer**: Endereco de entrega
- **Checkout**: Pedido para envio
- **Infrastructure/Shipping**: Integracao com Melhor Envio

## Dependentes

- **Checkout**: Selecao de frete no checkout
- **Admin**: Gestao de envios
