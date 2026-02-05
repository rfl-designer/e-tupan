@php
    $fontStack = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
    $primaryColor = '#099775';
@endphp

<x-emails.layout :subject="'Contato institucional - ' . $topic" :preheader="'Nova mensagem de ' . $name">
    <p style="margin:0 0 16px;font-family:{{ $fontStack }};font-size:16px;color:#111827;">
        Nova solicitacao recebida pelo site institucional.
    </p>

    <table role="presentation" style="width:100%;border:none;border-spacing:0;margin:0 0 20px;">
        <tr>
            <td style="padding:16px;border-radius:10px;background-color:#f9fafb;">
                <p style="margin:0 0 8px;font-family:{{ $fontStack }};font-size:13px;text-transform:uppercase;letter-spacing:0.08em;color:#6b7280;">
                    Dados do contato
                </p>
                <p style="margin:0;font-family:{{ $fontStack }};font-size:15px;font-weight:600;color:#111827;">
                    {{ $name }}
                </p>
                @if($company)
                    <p style="margin:4px 0 0;font-family:{{ $fontStack }};font-size:14px;color:#4b5563;">
                        {{ $company }}
                    </p>
                @endif
                <p style="margin:8px 0 0;font-family:{{ $fontStack }};font-size:14px;color:#4b5563;">
                    {{ $email }}
                </p>
            </td>
        </tr>
    </table>

    <table role="presentation" style="width:100%;border:none;border-spacing:0;margin:0 0 20px;">
        <tr>
            <td style="padding:16px;border-radius:10px;border:1px solid #e5e7eb;">
                <p style="margin:0 0 8px;font-family:{{ $fontStack }};font-size:13px;text-transform:uppercase;letter-spacing:0.08em;color:#6b7280;">
                    Assunto
                </p>
                <p style="margin:0;font-family:{{ $fontStack }};font-size:15px;font-weight:600;color:{{ $primaryColor }};">
                    {{ $topic }}
                </p>
            </td>
        </tr>
    </table>

    <table role="presentation" style="width:100%;border:none;border-spacing:0;margin:0;">
        <tr>
            <td style="padding:16px;border-radius:10px;background-color:#ffffff;border:1px solid #e5e7eb;">
                <p style="margin:0 0 8px;font-family:{{ $fontStack }};font-size:13px;text-transform:uppercase;letter-spacing:0.08em;color:#6b7280;">
                    Mensagem
                </p>
                <p style="margin:0;font-family:{{ $fontStack }};font-size:15px;color:#111827;line-height:1.6;">
                    {{ $message }}
                </p>
            </td>
        </tr>
    </table>
</x-emails.layout>
