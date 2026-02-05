# Infrastructure: Payment

## Responsabilidade

A camada Infrastructure/Payment e responsavel pela integracao com gateways de pagamento externos.

## Escopo

- **Gateways**: Abstracoes para diferentes provedores
- **Mercado Pago**: Integracao com API do Mercado Pago
- **PagSeguro**: Integracao com API do PagSeguro (futuro)
- **Stripe**: Integracao com API do Stripe (futuro)
- **Webhooks**: Processamento de callbacks dos gateways

## Estrutura Esperada

```
Payment/
├── Contracts/
│   └── PaymentGatewayInterface.php
├── Gateways/
│   ├── MercadoPago/
│   │   ├── MercadoPagoGateway.php
│   │   ├── MercadoPagoWebhook.php
│   │   └── DTOs/
│   ├── PagSeguro/
│   │   └── PagSeguroGateway.php
│   └── Stripe/
│       └── StripeGateway.php
├── Services/
│   └── PaymentGatewayFactory.php
├── Events/
│   ├── PaymentReceived.php
│   ├── PaymentFailed.php
│   └── RefundProcessed.php
└── DTOs/
    ├── PaymentRequest.php
    ├── PaymentResponse.php
    └── RefundRequest.php
```

## Padrao de Implementacao

Todos os gateways devem implementar `PaymentGatewayInterface`:

```php
interface PaymentGatewayInterface
{
    public function createPayment(PaymentRequest $request): PaymentResponse;
    public function getPayment(string $paymentId): PaymentResponse;
    public function refund(RefundRequest $request): RefundResponse;
    public function handleWebhook(array $payload): void;
}
```

## Dependencias Externas

- `mercadopago/dx-php`: SDK oficial do Mercado Pago
- Outros SDKs conforme necessidade

## Consumidores

- **Domain/Checkout**: Usa gateways para processar pagamentos
