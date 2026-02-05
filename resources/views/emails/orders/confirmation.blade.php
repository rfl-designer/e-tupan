@php
    use App\Domain\Admin\Services\SettingsService;

    $settings = app(SettingsService::class);
    $storeName = $settings->get('general.store_name') ?: config('app.name');
    $primaryColor = $settings->get('general.primary_color') ?: '#059669';
    $fontStack = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
@endphp

<x-emails.layout
    :subject="'Pedido #' . $order->order_number . ' confirmado - ' . $storeName"
    :preheader="'Seu pedido #' . $order->order_number . ' foi recebido com sucesso'"
>
    {{-- Greeting --}}
    <p style="margin:0 0 20px;font-family:{{ $fontStack }};font-size:16px;color:#333333;">
        Ola, {{ $order->customerName }}!
    </p>

    <p style="margin:0 0 25px;font-family:{{ $fontStack }};font-size:16px;color:#333333;">
        Obrigado pelo seu pedido! Aqui esta o resumo da sua compra:
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
            {{-- Date/Time --}}
            <td class="stack-column" style="width:50%;padding:15px;background-color:#fafafa;border-radius:8px 0 0 8px;vertical-align:top;">
                <p style="margin:0 0 5px;font-family:{{ $fontStack }};font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">
                    Data do Pedido
                </p>
                <p style="margin:0;font-family:{{ $fontStack }};font-size:16px;font-weight:600;color:#111827;">
                    {{ $order->placed_at->format('d/m/Y') }}
                </p>
                <p style="margin:5px 0 0;font-family:{{ $fontStack }};font-size:14px;color:#6b7280;">
                    {{ $order->placed_at->format('H:i') }}
                </p>
            </td>

            {{-- Status --}}
            <td class="stack-column" style="width:50%;padding:15px;background-color:#fafafa;border-radius:0 8px 8px 0;vertical-align:top;">
                <p style="margin:0 0 5px;font-family:{{ $fontStack }};font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">
                    Status do Pagamento
                </p>
                <p style="margin:0;font-family:{{ $fontStack }};font-size:16px;font-weight:600;color:#111827;">
                    {{ $order->payment_status->label() }}
                </p>
            </td>
        </tr>
    </table>

    {{-- Items Section --}}
    <table role="presentation" style="width:100%;border:none;border-spacing:0;margin-bottom:25px;">
        <tr>
            <td>
                <p style="margin:0 0 15px;font-family:{{ $fontStack }};font-size:16px;font-weight:600;color:#111827;">
                    Itens do Pedido
                </p>

                <table role="presentation" style="width:100%;border:none;border-spacing:0;">
                    @foreach($order->items as $item)
                        <tr>
                            <td style="padding:12px 0;border-bottom:1px solid #e5e7eb;vertical-align:top;">
                                {{-- Product Name --}}
                                <p style="margin:0;font-family:{{ $fontStack }};font-size:15px;font-weight:600;color:#111827;">
                                    {{ $item->product_name }}
                                </p>

                                {{-- Variant Name (if exists) --}}
                                @if($item->variant_name)
                                    <p style="margin:4px 0 0;font-family:{{ $fontStack }};font-size:13px;color:#6b7280;">
                                        {{ $item->variant_name }}
                                    </p>
                                @endif

                                {{-- SKU --}}
                                @if($item->display_sku)
                                    <p style="margin:4px 0 0;font-family:{{ $fontStack }};font-size:12px;color:#9ca3af;">
                                        SKU: {{ $item->display_sku }}
                                    </p>
                                @endif

                                {{-- Quantity and Unit Price --}}
                                <p style="margin:8px 0 0;font-family:{{ $fontStack }};font-size:13px;color:#6b7280;">
                                    Qtd: {{ $item->quantity }} x R$ {{ number_format($item->unit_price / 100, 2, ',', '.') }}
                                </p>
                            </td>
                            <td style="padding:12px 0;border-bottom:1px solid #e5e7eb;text-align:right;vertical-align:top;white-space:nowrap;">
                                {{-- Subtotal --}}
                                <p style="margin:0;font-family:{{ $fontStack }};font-size:15px;font-weight:600;color:#111827;">
                                    R$ {{ number_format($item->subtotal / 100, 2, ',', '.') }}
                                </p>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>

    {{-- Totals Section --}}
    <table role="presentation" style="width:100%;border:none;border-spacing:0;margin-bottom:25px;">
        <tr>
            <td style="background-color:#fafafa;border-radius:8px;padding:20px;">
                <table role="presentation" style="width:100%;border:none;border-spacing:0;">
                    <tr>
                        <td style="padding:5px 0;font-family:{{ $fontStack }};font-size:14px;color:#6b7280;">
                            Subtotal
                        </td>
                        <td style="padding:5px 0;font-family:{{ $fontStack }};font-size:14px;color:#111827;text-align:right;">
                            R$ {{ number_format($order->subtotal / 100, 2, ',', '.') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:5px 0;font-family:{{ $fontStack }};font-size:14px;color:#6b7280;">
                            Frete
                        </td>
                        <td style="padding:5px 0;font-family:{{ $fontStack }};font-size:14px;color:#111827;text-align:right;">
                            R$ {{ number_format($order->shipping_cost / 100, 2, ',', '.') }}
                        </td>
                    </tr>
                    @if($order->discount > 0)
                        <tr>
                            <td style="padding:5px 0;font-family:{{ $fontStack }};font-size:14px;color:#059669;">
                                Desconto
                                @if($order->coupon_code)
                                    ({{ $order->coupon_code }})
                                @endif
                            </td>
                            <td style="padding:5px 0;font-family:{{ $fontStack }};font-size:14px;color:#059669;text-align:right;">
                                -R$ {{ number_format($order->discount / 100, 2, ',', '.') }}
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td colspan="2" style="padding:10px 0 5px;">
                            <hr style="border:none;border-top:1px solid #e5e7eb;margin:0;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:5px 0;font-family:{{ $fontStack }};font-size:18px;font-weight:700;color:#111827;">
                            Total
                        </td>
                        <td style="padding:5px 0;font-family:{{ $fontStack }};font-size:18px;font-weight:700;color:{{ $primaryColor }};text-align:right;">
                            R$ {{ number_format($order->total / 100, 2, ',', '.') }}
                        </td>
                    </tr>
                    @if($payment)
                        <tr>
                            <td colspan="2" style="padding:10px 0 5px;">
                                <hr style="border:none;border-top:1px solid #e5e7eb;margin:0;">
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:5px 0;font-family:{{ $fontStack }};font-size:14px;color:#6b7280;">
                                Forma de Pagamento
                            </td>
                            <td style="padding:5px 0;font-family:{{ $fontStack }};font-size:14px;color:#111827;text-align:right;">
                                {{ $payment->method->label() }}
                            </td>
                        </tr>
                        @if($payment->isCreditCard() && $payment->installments > 1)
                            <tr>
                                <td style="padding:5px 0;font-family:{{ $fontStack }};font-size:14px;color:#6b7280;">
                                    Parcelamento
                                </td>
                                <td style="padding:5px 0;font-family:{{ $fontStack }};font-size:14px;color:#111827;text-align:right;">
                                    {{ $payment->installments }}x de R$ {{ number_format($payment->installment_amount_in_reais, 2, ',', '.') }}
                                </td>
                            </tr>
                        @endif
                        @if($payment->isCreditCard() && $payment->card_display)
                            <tr>
                                <td style="padding:5px 0;font-family:{{ $fontStack }};font-size:14px;color:#6b7280;">
                                    Cartao
                                </td>
                                <td style="padding:5px 0;font-family:{{ $fontStack }};font-size:14px;color:#111827;text-align:right;">
                                    {{ $payment->card_display }}
                                </td>
                            </tr>
                        @endif
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- Shipping Address --}}
    <table role="presentation" style="width:100%;border:none;border-spacing:0;margin-bottom:25px;">
        <tr>
            <td style="background-color:#fafafa;border-radius:8px;padding:20px;">
                <p style="margin:0 0 15px;font-family:{{ $fontStack }};font-size:16px;font-weight:600;color:#111827;">
                    Endereco de Entrega
                </p>

                {{-- Recipient Name --}}
                <p style="margin:0 0 5px;font-family:{{ $fontStack }};font-size:14px;font-weight:600;color:#111827;">
                    {{ $order->shipping_recipient_name }}
                </p>

                {{-- Full Address --}}
                <p style="margin:0 0 15px;font-family:{{ $fontStack }};font-size:14px;line-height:1.6;color:#4b5563;">
                    {{ $order->shipping_street }}, {{ $order->shipping_number }}
                    @if($order->shipping_complement)
                        - {{ $order->shipping_complement }}
                    @endif
                    <br>
                    {{ $order->shipping_neighborhood }}<br>
                    {{ $order->shipping_city }}/{{ $order->shipping_state }}<br>
                    CEP: {{ $order->shipping_zipcode }}
                </p>

                {{-- Shipping Method & Delivery Info --}}
                <table role="presentation" style="width:100%;border:none;border-spacing:0;border-top:1px solid #e5e7eb;padding-top:15px;">
                    @if($order->shipping_method || $order->shipping_carrier)
                        <tr>
                            <td style="padding:8px 0;font-family:{{ $fontStack }};font-size:14px;color:#6b7280;">
                                Metodo de Envio
                            </td>
                            <td style="padding:8px 0;font-family:{{ $fontStack }};font-size:14px;color:#111827;text-align:right;">
                                {{ $order->shipping_method ?: $order->shipping_carrier }}
                            </td>
                        </tr>
                    @endif
                    @if($order->shipping_days)
                        <tr>
                            <td style="padding:8px 0;font-family:{{ $fontStack }};font-size:14px;color:#6b7280;">
                                Prazo de Entrega
                            </td>
                            <td style="padding:8px 0;font-family:{{ $fontStack }};font-size:14px;color:#111827;text-align:right;">
                                {{ $order->shipping_days }} {{ $order->shipping_days === 1 ? 'dia util' : 'dias uteis' }}
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding:8px 0;font-family:{{ $fontStack }};font-size:14px;color:#6b7280;">
                            Valor do Frete
                        </td>
                        <td style="padding:8px 0;font-family:{{ $fontStack }};font-size:14px;font-weight:600;color:#111827;text-align:right;">
                            R$ {{ number_format($order->shipping_cost / 100, 2, ',', '.') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Payment Instructions (if pending) --}}
    @if($order->isPendingPayment() && $payment)
        @if($payment->method->value === 'pix')
            <table role="presentation" style="width:100%;border:none;border-spacing:0;margin-bottom:25px;">
                <tr>
                    <td style="background-color:#ecfdf5;border:1px solid #a7f3d0;border-radius:8px;padding:20px;">
                        <p style="margin:0 0 10px;font-family:{{ $fontStack }};font-size:16px;font-weight:600;color:#065f46;">
                            Pagamento via Pix
                        </p>
                        <p style="margin:0 0 10px;font-family:{{ $fontStack }};font-size:14px;color:#047857;">
                            Use o codigo Pix abaixo para realizar o pagamento:
                        </p>
                        @if($payment->pix_code)
                            <p style="margin:0 0 10px;font-family:monospace;font-size:12px;color:#065f46;background-color:#d1fae5;padding:10px;border-radius:4px;word-break:break-all;">
                                {{ $payment->pix_code }}
                            </p>
                        @endif
                        @if($payment->expires_at)
                            <p style="margin:0;font-family:{{ $fontStack }};font-size:13px;color:#6b7280;">
                                Valido ate: {{ $payment->expires_at->format('d/m/Y H:i') }}
                            </p>
                        @endif
                    </td>
                </tr>
            </table>
        @elseif($payment->method->value === 'bank_slip')
            <table role="presentation" style="width:100%;border:none;border-spacing:0;margin-bottom:25px;">
                <tr>
                    <td style="background-color:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:20px;">
                        <p style="margin:0 0 10px;font-family:{{ $fontStack }};font-size:16px;font-weight:600;color:#92400e;">
                            Pagamento via Boleto
                        </p>
                        <p style="margin:0 0 10px;font-family:{{ $fontStack }};font-size:14px;color:#a16207;">
                            Use a linha digitavel abaixo para pagar o boleto:
                        </p>
                        @if($payment->bank_slip_barcode)
                            <p style="margin:0 0 10px;font-family:monospace;font-size:12px;color:#92400e;background-color:#fef9c3;padding:10px;border-radius:4px;word-break:break-all;">
                                {{ $payment->bank_slip_barcode }}
                            </p>
                        @endif
                        @if($payment->bank_slip_url)
                            <p style="margin:0 0 10px;">
                                <a href="{{ $payment->bank_slip_url }}" style="display:inline-block;padding:10px 20px;background-color:#f59e0b;color:#ffffff;text-decoration:none;border-radius:6px;font-family:{{ $fontStack }};font-size:14px;font-weight:600;">
                                    Baixar Boleto PDF
                                </a>
                            </p>
                        @endif
                        @if($payment->expires_at)
                            <p style="margin:0;font-family:{{ $fontStack }};font-size:13px;color:#6b7280;">
                                Vencimento: {{ $payment->expires_at->format('d/m/Y') }}
                            </p>
                        @endif
                    </td>
                </tr>
            </table>
        @endif
    @endif

    {{-- CTA Button --}}
    <table role="presentation" style="width:100%;border:none;border-spacing:0;margin-bottom:25px;">
        <tr>
            <td style="text-align:center;">
                <a href="{{ $orderUrl }}" class="mobile-button" style="display:inline-block;padding:16px 40px;background-color:{{ $primaryColor }};color:#ffffff;text-decoration:none;border-radius:8px;font-family:{{ $fontStack }};font-size:18px;font-weight:700;box-shadow:0 4px 6px rgba(0,0,0,0.1);">
                    Acompanhar Pedido
                </a>
            </td>
        </tr>
    </table>

    {{-- Closing --}}
    <p style="margin:0;font-family:{{ $fontStack }};font-size:16px;color:#333333;">
        Obrigado por comprar conosco!
    </p>
</x-emails.layout>
