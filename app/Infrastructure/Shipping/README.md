# Infrastructure: Shipping

## Responsabilidade

A camada Infrastructure/Shipping e responsavel pela integracao com servicos de logistica externos.

## Escopo

- **Melhor Envio**: Integracao com API do Melhor Envio
- **Cotacao**: Calculo de frete com multiplas transportadoras
- **Etiquetas**: Geracao de etiquetas de envio
- **Rastreamento**: Consulta de status de entrega
- **Webhooks**: Atualizacoes de status

## Estrutura Esperada

```
Shipping/
├── Contracts/
│   └── ShippingProviderInterface.php
├── Providers/
│   └── MelhorEnvio/
│       ├── MelhorEnvioProvider.php
│       ├── MelhorEnvioWebhook.php
│       ├── Client/
│       │   └── MelhorEnvioClient.php
│       └── DTOs/
│           ├── QuoteRequest.php
│           └── QuoteResponse.php
├── Services/
│   └── ShippingProviderFactory.php
├── Events/
│   ├── ShipmentTracked.php
│   └── DeliveryStatusUpdated.php
└── DTOs/
    ├── ShippingQuoteRequest.php
    ├── ShippingQuoteResponse.php
    ├── LabelRequest.php
    └── TrackingResponse.php
```

## Padrao de Implementacao

Todos os provedores devem implementar `ShippingProviderInterface`:

```php
interface ShippingProviderInterface
{
    public function quote(ShippingQuoteRequest $request): array;
    public function createShipment(ShipmentRequest $request): ShipmentResponse;
    public function generateLabel(string $shipmentId): LabelResponse;
    public function track(string $trackingCode): TrackingResponse;
    public function handleWebhook(array $payload): void;
}
```

## Dependencias Externas

- API REST do Melhor Envio (via HTTP client)

## Consumidores

- **Domain/Shipping**: Usa provedores para calcular frete e rastrear
- **Domain/Checkout**: Cotacao de frete no checkout
