@php
    use App\Domain\Admin\Services\SettingsService;
    use Illuminate\Support\Facades\Storage;

    $settings = app(SettingsService::class);
    $storeName = $settings->get('general.store_name') ?: config('app.name');
    $storeEmail = $settings->get('general.store_email');
    $storePhone = $settings->get('general.store_phone');
    $storeAddress = $settings->get('general.store_address');
    $storeLogo = $settings->get('general.store_logo');
    $primaryColor = $settings->get('general.primary_color') ?: '#059669';
    $storeUrl = config('app.url');

    $logoUrl = $storeLogo && Storage::disk('public')->exists($storeLogo)
        ? Storage::disk('public')->url($storeLogo)
        : null;

    // Common font stack for email compatibility
    $fontStack = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
@endphp

@props([
    'preheader' => '',
    'subject' => '',
])

<!DOCTYPE html>
<html lang="pt-BR" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <title>{{ $subject ?: $storeName }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        /* Responsive styles for mobile devices */
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                max-width: 100% !important;
            }
            .email-content {
                padding: 20px 15px !important;
            }
            .email-header {
                padding: 20px 15px 15px !important;
            }
            .email-footer {
                padding: 15px !important;
            }
            .stack-column {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            .stack-column-center {
                text-align: center !important;
            }
            .mobile-padding {
                padding-left: 10px !important;
                padding-right: 10px !important;
            }
            .mobile-text-center {
                text-align: center !important;
            }
            .mobile-font-small {
                font-size: 14px !important;
            }
            .mobile-button {
                padding: 14px 30px !important;
                font-size: 16px !important;
            }
            .mobile-hide {
                display: none !important;
            }
            .mobile-full-width {
                width: 100% !important;
            }
        }
    </style>
</head>
<body style="margin:0;padding:0;word-spacing:normal;background-color:#f5f5f5;">
    {{-- Preheader text (hidden but shown in email preview) --}}
    @if($preheader)
        <div style="display:none;font-size:1px;color:#f5f5f5;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
            {{ $preheader }}
            {{-- Padding to push other content away from preheader --}}
            &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847;
        </div>
    @endif

    {{-- Wrapper table for Outlook --}}
    <table role="presentation" style="width:100%;border:none;border-spacing:0;background-color:#f5f5f5;">
        <tr>
            <td align="center" style="padding:20px 10px;">
                {{-- Main container --}}
                <table role="presentation" class="email-container" style="width:100%;max-width:600px;border:none;border-spacing:0;background-color:#ffffff;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
                    {{-- Header --}}
                    <tr>
                        <td class="email-header" style="padding:30px 30px 20px;text-align:center;border-bottom:2px solid #e5e5e5;">
                            @if($logoUrl)
                                <a href="{{ $storeUrl }}" style="text-decoration:none;">
                                    <img src="{{ $logoUrl }}" alt="{{ $storeName }}" style="max-width:180px;max-height:60px;border:0;display:inline-block;">
                                </a>
                            @else
                                <a href="{{ $storeUrl }}" style="text-decoration:none;">
                                    <span style="font-family:{{ $fontStack }};font-size:24px;font-weight:700;color:{{ $primaryColor }};">
                                        {{ $storeName }}
                                    </span>
                                </a>
                            @endif

                            @if($subject)
                                <h1 style="margin:20px 0 0;font-family:{{ $fontStack }};font-size:22px;font-weight:600;color:#111827;">
                                    {{ $subject }}
                                </h1>
                            @endif
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td class="email-content" style="padding:30px;font-family:{{ $fontStack }};font-size:16px;line-height:1.6;color:#333333;">
                            {{ $slot }}
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td class="email-footer" style="padding:20px 30px 30px;border-top:1px solid #e5e5e5;background-color:#fafafa;border-radius:0 0 8px 8px;">
                            {{-- Store info --}}
                            <table role="presentation" style="width:100%;border:none;border-spacing:0;">
                                <tr>
                                    <td style="text-align:center;padding-bottom:15px;">
                                        <a href="{{ $storeUrl }}" style="font-family:{{ $fontStack }};font-size:16px;font-weight:600;color:{{ $primaryColor }};text-decoration:none;">
                                            {{ $storeName }}
                                        </a>
                                    </td>
                                </tr>

                                {{-- Contact info --}}
                                <tr>
                                    <td style="text-align:center;font-family:{{ $fontStack }};font-size:14px;line-height:1.8;color:#6b7280;">
                                        @if($storeAddress)
                                            <span style="display:block;">{{ $storeAddress }}</span>
                                        @endif

                                        @if($storePhone)
                                            <span style="display:block;">
                                                Tel: <a href="tel:{{ preg_replace('/\D/', '', $storePhone) }}" style="color:#6b7280;text-decoration:none;">{{ $storePhone }}</a>
                                            </span>
                                        @endif

                                        @if($storeEmail)
                                            <span style="display:block;">
                                                Email: <a href="mailto:{{ $storeEmail }}" style="color:#6b7280;text-decoration:none;">{{ $storeEmail }}</a>
                                            </span>
                                        @endif

                                        <span style="display:block;margin-top:5px;">
                                            <a href="{{ $storeUrl }}" style="color:{{ $primaryColor }};text-decoration:none;">Visite nossa loja</a>
                                        </span>
                                    </td>
                                </tr>

                                {{-- Copyright --}}
                                <tr>
                                    <td style="text-align:center;padding-top:20px;font-family:{{ $fontStack }};font-size:12px;color:#9ca3af;">
                                        &copy; {{ date('Y') }} {{ $storeName }}. Todos os direitos reservados.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
