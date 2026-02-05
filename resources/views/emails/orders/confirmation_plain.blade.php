@php
    use App\Domain\Admin\Services\SettingsService;

    $settings = app(SettingsService::class);
    $storeName = $settings->get('general.store_name') ?: config('app.name');
    $storeEmail = $settings->get('general.store_email');
    $storePhone = $settings->get('general.store_phone');
    $storeUrl = config('app.url');
@endphp
PEDIDO #{{ $order->order_number }} CONFIRMADO - {{ $storeName }}
{{ str_repeat('=', 60) }}

Ola, {{ $order->customerName }}!

Obrigado pelo seu pedido! Aqui esta o resumo da sua compra:

NUMERO DO PEDIDO: {{ $order->order_number }}
DATA: {{ $order->placed_at->format('d/m/Y') }} as {{ $order->placed_at->format('H:i') }}
STATUS: {{ $order->payment_status->label() }}

{{ str_repeat('-', 60) }}
ITENS DO PEDIDO
{{ str_repeat('-', 60) }}
@foreach($order->items as $item)
* {{ $item->product_name }}
@if($item->variant_name)
  Variante: {{ $item->variant_name }}
@endif
@if($item->display_sku)
  SKU: {{ $item->display_sku }}
@endif
  Qtd: {{ $item->quantity }} x R$ {{ number_format($item->unit_price / 100, 2, ',', '.') }}
  Subtotal: R$ {{ number_format($item->subtotal / 100, 2, ',', '.') }}

@endforeach
{{ str_repeat('-', 60) }}
RESUMO FINANCEIRO
{{ str_repeat('-', 60) }}
Subtotal: R$ {{ number_format($order->subtotal / 100, 2, ',', '.') }}
Frete: R$ {{ number_format($order->shipping_cost / 100, 2, ',', '.') }}
@if($order->discount > 0)
Desconto: -R$ {{ number_format($order->discount / 100, 2, ',', '.') }}@if($order->coupon_code) ({{ $order->coupon_code }})@endif

@endif
TOTAL: R$ {{ number_format($order->total / 100, 2, ',', '.') }}
@if($payment)

Forma de Pagamento: {{ $payment->method->label() }}
@if($payment->isCreditCard() && $payment->installments > 1)
Parcelamento: {{ $payment->installments }}x de R$ {{ number_format($payment->installment_amount_in_reais, 2, ',', '.') }}
@endif
@if($payment->isCreditCard() && $payment->card_display)
Cartao: {{ $payment->card_display }}
@endif
@endif

{{ str_repeat('-', 60) }}
ENDERECO DE ENTREGA
{{ str_repeat('-', 60) }}
{{ $order->shipping_recipient_name }}
{{ $order->shipping_street }}, {{ $order->shipping_number }}@if($order->shipping_complement) - {{ $order->shipping_complement }}@endif

{{ $order->shipping_neighborhood }}
{{ $order->shipping_city }}/{{ $order->shipping_state }}
CEP: {{ $order->shipping_zipcode }}
@if($order->shipping_method || $order->shipping_carrier)

Metodo de Envio: {{ $order->shipping_method ?: $order->shipping_carrier }}
@endif
@if($order->shipping_days)
Prazo de Entrega: {{ $order->shipping_days }} {{ $order->shipping_days === 1 ? 'dia util' : 'dias uteis' }}
@endif
Valor do Frete: R$ {{ number_format($order->shipping_cost / 100, 2, ',', '.') }}
@if($order->isPendingPayment() && $payment)
@if($payment->method->value === 'pix')

{{ str_repeat('-', 60) }}
PAGAMENTO VIA PIX
{{ str_repeat('-', 60) }}
Use o codigo Pix abaixo para realizar o pagamento:
@if($payment->pix_code)

{{ $payment->pix_code }}
@endif
@if($payment->expires_at)

Valido ate: {{ $payment->expires_at->format('d/m/Y H:i') }}
@endif
@elseif($payment->method->value === 'bank_slip')

{{ str_repeat('-', 60) }}
PAGAMENTO VIA BOLETO
{{ str_repeat('-', 60) }}
Use a linha digitavel abaixo para pagar o boleto:
@if($payment->bank_slip_barcode)

{{ $payment->bank_slip_barcode }}
@endif
@if($payment->bank_slip_url)

Link para download: {{ $payment->bank_slip_url }}
@endif
@if($payment->expires_at)

Vencimento: {{ $payment->expires_at->format('d/m/Y') }}
@endif
@endif
@endif

{{ str_repeat('=', 60) }}
ACOMPANHE SEU PEDIDO:
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
