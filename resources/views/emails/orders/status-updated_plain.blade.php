@php
    use App\Domain\Admin\Services\SettingsService;
    use App\Domain\Checkout\Enums\OrderStatus;
    use App\Domain\Shipping\Enums\ShippingCarrier;

    $settings = app(SettingsService::class);
    $storeName = $settings->get('general.store_name') ?: config('app.name');
    $storeEmail = $settings->get('general.store_email');
    $storePhone = $settings->get('general.store_phone');
    $storeUrl = config('app.url');

    $statusMessage = match ($newStatus) {
        OrderStatus::Processing => 'Seu pagamento foi confirmado e seu pedido esta sendo preparado.',
        OrderStatus::Shipped => 'Seu pedido foi enviado e esta a caminho do endereco de entrega.',
        OrderStatus::Completed => 'Seu pedido foi entregue com sucesso! Esperamos que voce aproveite sua compra.',
        OrderStatus::Cancelled => 'Infelizmente, seu pedido foi cancelado.',
        OrderStatus::Refunded => 'O reembolso do seu pedido foi processado.',
        default => 'O status do seu pedido foi atualizado.',
    };

    // Get shipments with tracking info for multiple volumes support
    $shipments = $order->relationLoaded('shipments')
        ? $order->shipments->filter(fn($s) => $s->tracking_number)
        : $order->shipments()->whereNotNull('tracking_number')->get();

    // Get carrier for tracking URL
    $carrier = $order->shipping_carrier
        ? ShippingCarrier::tryFromName($order->shipping_carrier)
        : null;

    // Build internal tracking URL
    $internalTrackingUrl = $order->tracking_number
        ? url('/rastreio/' . $order->tracking_number)
        : null;

    // Build external tracking URL from carrier
    $externalTrackingUrl = ($carrier && $order->tracking_number)
        ? $carrier->getTrackingUrl($order->tracking_number)
        : null;
@endphp
ATUALIZACAO DO PEDIDO #{{ $order->order_number }} - {{ $storeName }}
{{ str_repeat('=', 60) }}

*** NOVO STATUS: {{ strtoupper($newStatus->label()) }} ***

Ola, {{ $order->customerName }}!

{{ $statusMessage }}

{{ str_repeat('-', 60) }}
INFORMACOES DO PEDIDO
{{ str_repeat('-', 60) }}
Numero do Pedido: {{ $order->order_number }}
Data do Pedido: {{ $order->placed_at->format('d/m/Y') }}
Status Anterior: {{ $oldStatus->label() }}
Status Atual: {{ $newStatus->label() }}

{{ str_repeat('-', 60) }}
RESUMO DOS ITENS
{{ str_repeat('-', 60) }}
@foreach($order->items as $item)
* {{ $item->product_name }}@if($item->variant_name) - {{ $item->variant_name }}@endif

  Quantidade: {{ $item->quantity }}
@endforeach

Total do Pedido: R$ {{ number_format($order->total / 100, 2, ',', '.') }}

{{ str_repeat('-', 60) }}
ENDERECO DE ENTREGA
{{ str_repeat('-', 60) }}
{{ $order->shipping_city }}/{{ $order->shipping_state }}
@if($newStatus === OrderStatus::Cancelled)
@php
    // Get refund info from approved payment
    $approvedPayment = $order->relationLoaded('payments')
        ? $order->payments->first(fn($p) => $p->status === \App\Domain\Checkout\Enums\PaymentStatus::Approved || $p->status === \App\Domain\Checkout\Enums\PaymentStatus::Refunded)
        : $order->payments()->whereIn('status', ['approved', 'refunded'])->first();

    $hasRefundInfo = $approvedPayment !== null;
    $refundAmount = $hasRefundInfo ? $approvedPayment->refunded_amount ?? $order->total : $order->total;
    $paymentMethod = $approvedPayment?->method;
@endphp

{{ str_repeat('-', 60) }}
INFORMACOES DO CANCELAMENTO
{{ str_repeat('-', 60) }}
@if($order->cancellation_reason)
Motivo: {{ $order->cancellation_reason }}
@endif
@if($hasRefundInfo || $order->isPaid())

INFORMACOES DE REEMBOLSO:
Valor a ser estornado: R$ {{ number_format($refundAmount / 100, 2, ',', '.') }}
@if($paymentMethod)
Metodo de pagamento: {{ $paymentMethod->label() }}
@if($paymentMethod === \App\Domain\Checkout\Enums\PaymentMethod::CreditCard)
Prazo: O estorno sera processado em ate 2 faturas, conforme a politica da operadora do cartao.
@elseif($paymentMethod === \App\Domain\Checkout\Enums\PaymentMethod::Pix)
Prazo: O reembolso sera feito via PIX em ate 5 dias uteis.
@elseif($paymentMethod === \App\Domain\Checkout\Enums\PaymentMethod::BankSlip)
Prazo: O reembolso sera feito via transferencia bancaria em ate 10 dias uteis apos confirmarmos seus dados.
@else
Prazo: O reembolso sera processado em ate 10 dias uteis.
@endif
@endif
@endif

DUVIDAS SOBRE O CANCELAMENTO?
@if($storeEmail)
Email: {{ $storeEmail }}
@endif
@if($storePhone)
Telefone: {{ $storePhone }}
@endif
@if(!$storeEmail && !$storePhone)
Entre em contato conosco para mais informacoes.
@endif
@endif
@if($newStatus === OrderStatus::Shipped && ($order->tracking_number || $shipments->isNotEmpty()))

{{ str_repeat('-', 60) }}
INFORMACOES DE RASTREAMENTO
{{ str_repeat('-', 60) }}
@if($order->shipping_carrier)
Transportadora: {{ $order->shipping_carrier }}
@endif
@if($order->tracking_number && $shipments->isEmpty())
Codigo de Rastreamento: {{ $order->tracking_number }}
@endif
@if($shipments->isNotEmpty())
@if($shipments->count() > 1)
{{ $shipments->count() }} volumes:
@endif
@foreach($shipments as $index => $shipment)
@if($shipments->count() > 1)
- Volume {{ $index + 1 }}: {{ $shipment->tracking_number }}@if($shipment->tracking_url) ({{ $shipment->tracking_url }})@endif

@else
Codigo de Rastreamento: {{ $shipment->tracking_number }}
@if($shipment->tracking_url)
Link de Rastreamento: {{ $shipment->tracking_url }}
@endif
@endif
@endforeach
@endif
@if($order->shipping_days)
Previsao de entrega: {{ $order->shipping_days }} {{ $order->shipping_days === 1 ? 'dia util' : 'dias uteis' }}
@endif
@if($internalTrackingUrl)

RASTREAR SEU PEDIDO:
{{ $internalTrackingUrl }}
@endif
@if($externalTrackingUrl)

RASTREAR NA {{ strtoupper($carrier?->company() ?? 'TRANSPORTADORA') }}:
{{ $externalTrackingUrl }}
@endif
@endif

{{ str_repeat('=', 60) }}
VER DETALHES DO PEDIDO:
{{ $orderUrl }}
{{ str_repeat('=', 60) }}

Obrigado por comprar conosco!

---
{{ $storeName }}
@if($storeEmail)
Email: {{ $storeEmail }}
@endif
@if($storePhone)
Tel: {{ $storePhone }}
@endif
{{ $storeUrl }}

(c) {{ date('Y') }} {{ $storeName }}. Todos os direitos reservados.
