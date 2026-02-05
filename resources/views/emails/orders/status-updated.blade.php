@php
    use App\Domain\Admin\Services\SettingsService;
    use App\Domain\Checkout\Enums\OrderStatus;
    use App\Domain\Shipping\Enums\ShippingCarrier;

    $settings = app(SettingsService::class);
    $storeName = $settings->get('general.store_name') ?: config('app.name');
    $primaryColor = $settings->get('general.primary_color') ?: '#059669';
    $fontStack = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";

    $statusMessage = match ($newStatus) {
        OrderStatus::Processing => 'Seu pagamento foi confirmado e seu pedido esta sendo preparado.',
        OrderStatus::Shipped => 'Seu pedido foi enviado e esta a caminho do endereco de entrega.',
        OrderStatus::Completed => 'Seu pedido foi entregue com sucesso! Esperamos que voce aproveite sua compra.',
        OrderStatus::Cancelled => 'Infelizmente, seu pedido foi cancelado.',
        OrderStatus::Refunded => 'O reembolso do seu pedido foi processado.',
        default => 'O status do seu pedido foi atualizado.',
    };

    $statusColor = match ($newStatus) {
        OrderStatus::Processing => '#0ea5e9',
        OrderStatus::Shipped => '#6366f1',
        OrderStatus::Completed => '#22c55e',
        OrderStatus::Cancelled => '#ef4444',
        OrderStatus::Refunded => '#a855f7',
        default => '#6b7280',
    };

    $statusIcon = match ($newStatus) {
        OrderStatus::Processing => '⏳',
        OrderStatus::Shipped => '🚚',
        OrderStatus::Completed => '✅',
        OrderStatus::Cancelled => '❌',
        OrderStatus::Refunded => '↩️',
        default => '📦',
    };

    // Calculate subject if not provided
    if (!isset($subject)) {
        $orderNumber = $order->order_number;
        $subject = match ($newStatus) {
            OrderStatus::Processing => "Pedido #{$orderNumber} - Pagamento Confirmado - {$storeName}",
            OrderStatus::Shipped => "Pedido #{$orderNumber} foi Enviado - {$storeName}",
            OrderStatus::Completed => "Pedido #{$orderNumber} foi Entregue - {$storeName}",
            OrderStatus::Cancelled => "Pedido #{$orderNumber} foi Cancelado - {$storeName}",
            OrderStatus::Refunded => "Pedido #{$orderNumber} foi Reembolsado - {$storeName}",
            default => "Atualizacao do Pedido #{$orderNumber} - {$storeName}",
        };
    }

    // Calculate preheader if not provided
    if (!isset($preheader)) {
        $orderNumber = $order->order_number;
        $preheader = match ($newStatus) {
            OrderStatus::Processing => "O pagamento do seu pedido #{$orderNumber} foi confirmado",
            OrderStatus::Shipped => "Seu pedido #{$orderNumber} esta a caminho",
            OrderStatus::Completed => "Seu pedido #{$orderNumber} foi entregue com sucesso",
            OrderStatus::Cancelled => "Seu pedido #{$orderNumber} foi cancelado",
            OrderStatus::Refunded => "O reembolso do pedido #{$orderNumber} foi processado",
            default => "O status do seu pedido #{$orderNumber} foi atualizado",
        };
    }
@endphp

<x-emails.layout
    :subject="$subject"
    :preheader="$preheader"
>
    {{-- Status Badge --}}
    <table role="presentation" style="width:100%;border:none;border-spacing:0;margin-bottom:25px;">
        <tr>
            <td style="text-align:center;">
                <span style="display:inline-block;padding:12px 24px;background-color:{{ $statusColor }};color:#ffffff;border-radius:50px;font-family:{{ $fontStack }};font-size:16px;font-weight:700;">
                    {{ $statusIcon }} {{ $newStatus->label() }}
                </span>
            </td>
        </tr>
    </table>

    {{-- Greeting --}}
    <p style="margin:0 0 20px;font-family:{{ $fontStack }};font-size:16px;color:#333333;">
        Ola, {{ $order->customerName }}!
    </p>

    <p style="margin:0 0 25px;font-family:{{ $fontStack }};font-size:16px;color:#333333;">
        {{ $statusMessage }}
    </p>

    {{-- Order Number Highlight --}}
    <table role="presentation" style="width:100%;border:none;border-spacing:0;margin-bottom:25px;">
        <tr>
            <td style="background-color:#f3f4f6;border-radius:8px;padding:20px;text-align:center;">
                <p style="margin:0 0 5px;font-family:{{ $fontStack }};font-size:14px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">
                    Numero do Pedido
                </p>
                <p style="margin:0;font-family:{{ $fontStack }};font-size:28px;font-weight:700;color:{{ $primaryColor }};">
                    {{ $order->order_number }}
                </p>
            </td>
        </tr>
    </table>

    {{-- Order Info Grid --}}
    <table role="presentation" style="width:100%;border:none;border-spacing:0;margin-bottom:25px;">
        <tr>
            {{-- Date --}}
            <td class="stack-column" style="width:50%;padding:15px;background-color:#fafafa;border-radius:8px 0 0 8px;vertical-align:top;">
                <p style="margin:0 0 5px;font-family:{{ $fontStack }};font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">
                    Data do Pedido
                </p>
                <p style="margin:0;font-family:{{ $fontStack }};font-size:16px;font-weight:600;color:#111827;">
                    {{ $order->placed_at->format('d/m/Y') }}
                </p>
            </td>

            {{-- New Status --}}
            <td class="stack-column" style="width:50%;padding:15px;background-color:#fafafa;border-radius:0 8px 8px 0;vertical-align:top;">
                <p style="margin:0 0 5px;font-family:{{ $fontStack }};font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">
                    Status Atual
                </p>
                <p style="margin:0;font-family:{{ $fontStack }};font-size:16px;font-weight:600;color:{{ $statusColor }};">
                    {{ $newStatus->label() }}
                </p>
            </td>
        </tr>
    </table>

    {{-- Items Summary --}}
    <table role="presentation" style="width:100%;border:none;border-spacing:0;margin-bottom:25px;">
        <tr>
            <td>
                <p style="margin:0 0 15px;font-family:{{ $fontStack }};font-size:16px;font-weight:600;color:#111827;">
                    Resumo do Pedido
                </p>

                <table role="presentation" style="width:100%;border:none;border-spacing:0;">
                    @foreach($order->items as $item)
                        <tr>
                            <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;vertical-align:top;">
                                <p style="margin:0;font-family:{{ $fontStack }};font-size:14px;color:#111827;">
                                    {{ $item->product_name }}
                                    @if($item->variant_name)
                                        <span style="color:#6b7280;"> - {{ $item->variant_name }}</span>
                                    @endif
                                </p>
                            </td>
                            <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;text-align:right;vertical-align:top;white-space:nowrap;">
                                <p style="margin:0;font-family:{{ $fontStack }};font-size:14px;color:#6b7280;">
                                    Qtd: {{ $item->quantity }}
                                </p>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>

    {{-- Total --}}
    <table role="presentation" style="width:100%;border:none;border-spacing:0;margin-bottom:25px;">
        <tr>
            <td style="background-color:#fafafa;border-radius:8px;padding:15px;">
                <table role="presentation" style="width:100%;border:none;border-spacing:0;">
                    <tr>
                        <td style="font-family:{{ $fontStack }};font-size:16px;font-weight:600;color:#111827;">
                            Total do Pedido
                        </td>
                        <td style="font-family:{{ $fontStack }};font-size:18px;font-weight:700;color:{{ $primaryColor }};text-align:right;">
                            R$ {{ number_format($order->total / 100, 2, ',', '.') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Shipping Address Summary --}}
    <table role="presentation" style="width:100%;border:none;border-spacing:0;margin-bottom:25px;">
        <tr>
            <td style="padding:15px;background-color:#fafafa;border-radius:8px;">
                <p style="margin:0 0 10px;font-family:{{ $fontStack }};font-size:14px;font-weight:600;color:#111827;">
                    Endereco de Entrega
                </p>
                <p style="margin:0;font-family:{{ $fontStack }};font-size:14px;color:#6b7280;">
                    {{ $order->shipping_city }}/{{ $order->shipping_state }}
                </p>
            </td>
        </tr>
    </table>

    {{-- Cancellation Info Section (for cancelled status) --}}
    @if($newStatus === OrderStatus::Cancelled)
        @php
            $storeEmail = $settings->get('general.store_email');
            $storePhone = $settings->get('general.store_phone');

            // Get refund info from approved payment
            $approvedPayment = $order->relationLoaded('payments')
                ? $order->payments->first(fn($p) => $p->status === \App\Domain\Checkout\Enums\PaymentStatus::Approved || $p->status === \App\Domain\Checkout\Enums\PaymentStatus::Refunded)
                : $order->payments()->whereIn('status', ['approved', 'refunded'])->first();

            $hasRefundInfo = $approvedPayment !== null;
            $refundAmount = $hasRefundInfo ? $approvedPayment->refunded_amount ?? $order->total : $order->total;
            $paymentMethod = $approvedPayment?->method;
        @endphp

        {{-- Cancellation Reason Box --}}
        <table role="presentation" style="width:100%;border:none;border-spacing:0;margin-bottom:25px;">
            <tr>
                <td style="background-color:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:20px;">
                    <p style="margin:0 0 15px;font-family:{{ $fontStack }};font-size:16px;font-weight:600;color:#991b1b;">
                        ⚠️ Informacoes do Cancelamento
                    </p>

                    @if($order->cancellation_reason)
                        <p style="margin:0 0 15px;font-family:{{ $fontStack }};font-size:14px;color:#991b1b;">
                            <strong>Motivo:</strong> {{ $order->cancellation_reason }}
                        </p>
                    @endif

                    {{-- Refund Information --}}
                    @if($hasRefundInfo || $order->isPaid())
                        <table role="presentation" style="width:100%;border:none;border-spacing:0;background-color:#ffffff;border-radius:6px;padding:15px;margin-bottom:15px;">
                            <tr>
                                <td>
                                    <p style="margin:0 0 10px;font-family:{{ $fontStack }};font-size:14px;font-weight:600;color:#111827;">
                                        💰 Informacoes de Reembolso
                                    </p>

                                    <p style="margin:0 0 8px;font-family:{{ $fontStack }};font-size:14px;color:#374151;">
                                        Valor a ser estornado: <strong style="color:#059669;">R$ {{ number_format($refundAmount / 100, 2, ',', '.') }}</strong>
                                    </p>

                                    @if($paymentMethod)
                                        <p style="margin:0 0 8px;font-family:{{ $fontStack }};font-size:14px;color:#374151;">
                                            Metodo de pagamento: <strong>{{ $paymentMethod->label() }}</strong>
                                        </p>

                                        <p style="margin:0;font-family:{{ $fontStack }};font-size:13px;color:#6b7280;">
                                            @if($paymentMethod === \App\Domain\Checkout\Enums\PaymentMethod::CreditCard)
                                                O estorno sera processado em ate 2 faturas, conforme a politica da operadora do cartao.
                                            @elseif($paymentMethod === \App\Domain\Checkout\Enums\PaymentMethod::Pix)
                                                O reembolso sera feito via PIX em ate 5 dias uteis.
                                            @elseif($paymentMethod === \App\Domain\Checkout\Enums\PaymentMethod::BankSlip)
                                                O reembolso sera feito via transferencia bancaria em ate 10 dias uteis apos confirmarmos seus dados.
                                            @else
                                                O reembolso sera processado em ate 10 dias uteis.
                                            @endif
                                        </p>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    @endif

                    {{-- Contact Information --}}
                    <p style="margin:0 0 10px;font-family:{{ $fontStack }};font-size:14px;color:#991b1b;">
                        <strong>Duvidas sobre o cancelamento?</strong>
                    </p>

                    <table role="presentation" style="width:100%;border:none;border-spacing:0;">
                        <tr>
                            @if($storeEmail)
                                <td style="padding-right:15px;">
                                    <a href="mailto:{{ $storeEmail }}" style="display:inline-block;padding:10px 20px;background-color:#991b1b;color:#ffffff;text-decoration:none;border-radius:6px;font-family:{{ $fontStack }};font-size:14px;font-weight:600;">
                                        📧 Enviar Email
                                    </a>
                                </td>
                            @endif
                            @if($storePhone)
                                <td>
                                    <p style="margin:0;font-family:{{ $fontStack }};font-size:14px;color:#374151;">
                                        📞 Ou ligue: <strong>{{ $storePhone }}</strong>
                                    </p>
                                </td>
                            @endif
                        </tr>
                    </table>

                    @if(!$storeEmail && !$storePhone)
                        <p style="margin:0;font-family:{{ $fontStack }};font-size:14px;color:#374151;">
                            Entre em contato conosco para mais informacoes.
                        </p>
                    @endif
                </td>
            </tr>
        </table>
    @endif

    {{-- Tracking Info (for shipped status) --}}
    @php
        // Get shipments with tracking info for multiple volumes support (used in both tracking section and CTA decision)
        $shipments = $order->relationLoaded('shipments')
            ? $order->shipments->filter(fn($s) => $s->tracking_number)
            : $order->shipments()->whereNotNull('tracking_number')->get();
    @endphp

    @if($newStatus === OrderStatus::Shipped)
        @php
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

        @if($order->tracking_number || $shipments->isNotEmpty())
            <table role="presentation" style="width:100%;border:none;border-spacing:0;margin-bottom:25px;">
                <tr>
                    <td style="background-color:#eef2ff;border:1px solid #c7d2fe;border-radius:8px;padding:20px;">
                        <p style="margin:0 0 15px;font-family:{{ $fontStack }};font-size:16px;font-weight:600;color:#4338ca;">
                            📦 Informacoes de Rastreamento
                        </p>

                        @if($order->shipping_carrier)
                            <p style="margin:0 0 8px;font-family:{{ $fontStack }};font-size:14px;color:#4338ca;">
                                Transportadora: <strong>{{ $order->shipping_carrier }}</strong>
                            </p>
                        @endif

                        {{-- Single tracking number (from order) --}}
                        @if($order->tracking_number && $shipments->isEmpty())
                            <p style="margin:0 0 8px;font-family:{{ $fontStack }};font-size:14px;color:#4338ca;">
                                Codigo de Rastreamento: <strong>{{ $order->tracking_number }}</strong>
                            </p>
                        @endif

                        {{-- Multiple volumes (from shipments) --}}
                        @if($shipments->isNotEmpty())
                            @if($shipments->count() > 1)
                                <p style="margin:0 0 8px;font-family:{{ $fontStack }};font-size:14px;color:#4338ca;">
                                    <strong>{{ $shipments->count() }} volumes:</strong>
                                </p>
                            @endif
                            @foreach($shipments as $index => $shipment)
                                <p style="margin:0 0 5px;font-family:{{ $fontStack }};font-size:14px;color:#4338ca;">
                                    @if($shipments->count() > 1)
                                        Volume {{ $index + 1 }}:
                                    @else
                                        Codigo:
                                    @endif
                                    <strong>{{ $shipment->tracking_number }}</strong>
                                    @if($shipment->tracking_url)
                                        - <a href="{{ $shipment->tracking_url }}" style="color:#4338ca;text-decoration:underline;">Rastrear</a>
                                    @endif
                                </p>
                            @endforeach
                        @endif

                        {{-- Estimated delivery --}}
                        @if($order->shipping_days)
                            <p style="margin:10px 0 0;font-family:{{ $fontStack }};font-size:13px;color:#6366f1;">
                                Previsao de entrega: <strong>{{ $order->shipping_days }} {{ $order->shipping_days === 1 ? 'dia util' : 'dias uteis' }}</strong>
                            </p>
                        @endif

                        {{-- Tracking links --}}
                        @if($internalTrackingUrl || $externalTrackingUrl)
                            <table role="presentation" style="width:100%;border:none;border-spacing:0;margin-top:15px;">
                                <tr>
                                    @if($internalTrackingUrl)
                                        <td style="padding-right:10px;">
                                            <a href="{{ $internalTrackingUrl }}" style="display:inline-block;padding:10px 20px;background-color:#4338ca;color:#ffffff;text-decoration:none;border-radius:6px;font-family:{{ $fontStack }};font-size:14px;font-weight:600;">
                                                Rastrear Pedido
                                            </a>
                                        </td>
                                    @endif
                                    @if($externalTrackingUrl)
                                        <td>
                                            <a href="{{ $externalTrackingUrl }}" style="display:inline-block;padding:10px 20px;background-color:transparent;color:#4338ca;text-decoration:none;border:2px solid #4338ca;border-radius:6px;font-family:{{ $fontStack }};font-size:14px;font-weight:600;">
                                                Rastrear na {{ $carrier?->company() ?? 'Transportadora' }}
                                            </a>
                                        </td>
                                    @endif
                                </tr>
                            </table>
                        @endif
                    </td>
                </tr>
            </table>
        @endif
    @endif

    {{-- CTA Button (secondary when tracking is shown as primary CTA) --}}
    @php
        $hasTrackingCta = $newStatus === OrderStatus::Shipped && ($order->tracking_number || (isset($shipments) && $shipments->isNotEmpty()));
    @endphp
    <table role="presentation" style="width:100%;border:none;border-spacing:0;margin-bottom:25px;">
        <tr>
            <td style="text-align:center;">
                @if($hasTrackingCta)
                    {{-- Secondary CTA (outline style) when tracking is primary --}}
                    <a href="{{ $orderUrl }}" class="mobile-button" style="display:inline-block;padding:14px 38px;background-color:transparent;color:{{ $primaryColor }};text-decoration:none;border:2px solid {{ $primaryColor }};border-radius:8px;font-family:{{ $fontStack }};font-size:16px;font-weight:600;">
                        Ver Detalhes do Pedido
                    </a>
                @else
                    {{-- Primary CTA when no tracking buttons --}}
                    <a href="{{ $orderUrl }}" class="mobile-button" style="display:inline-block;padding:16px 40px;background-color:{{ $primaryColor }};color:#ffffff;text-decoration:none;border-radius:8px;font-family:{{ $fontStack }};font-size:18px;font-weight:700;box-shadow:0 4px 6px rgba(0,0,0,0.1);">
                        Ver Detalhes do Pedido
                    </a>
                @endif
            </td>
        </tr>
    </table>

    {{-- Closing --}}
    <p style="margin:0;font-family:{{ $fontStack }};font-size:16px;color:#333333;">
        Obrigado por comprar conosco!
    </p>
</x-emails.layout>
