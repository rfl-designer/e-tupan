<x-emails.layout
    subject="Teste de Configuracao de Email"
    preheader="Este e um email de teste para verificar a configuracao de email da sua loja."
>
    <p style="margin:0 0 20px;">
        Este e um email de teste enviado pela loja <strong>{{ $storeName }}</strong> para verificar se a configuracao de email esta funcionando corretamente.
    </p>

    {{-- Info box --}}
    <table role="presentation" style="width:100%;border:none;border-spacing:0;background-color:#f9fafb;border-radius:6px;margin:20px 0;">
        <tr>
            <td style="padding:20px;">
                <table role="presentation" style="width:100%;border:none;border-spacing:0;font-size:14px;">
                    <tr>
                        <td style="padding:10px 0;border-bottom:1px solid #e5e5e5;">
                            <span style="font-weight:600;color:#6b7280;">Loja:</span>
                            <span style="color:#111827;float:right;">{{ $storeName }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;border-bottom:1px solid #e5e5e5;">
                            <span style="font-weight:600;color:#6b7280;">Driver de Email:</span>
                            <span style="color:#111827;float:right;">{{ strtoupper($driver) }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;">
                            <span style="font-weight:600;color:#6b7280;">Data/Hora do Teste:</span>
                            <span style="color:#111827;float:right;">{{ $sentAt->format('d/m/Y H:i:s') }}</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Success badge --}}
    <table role="presentation" style="width:100%;border:none;border-spacing:0;margin:20px 0;">
        <tr>
            <td align="center">
                <span style="display:inline-block;background-color:#d1fae5;color:#065f46;padding:8px 16px;border-radius:20px;font-size:14px;font-weight:600;">
                    Configuracao Funcionando
                </span>
            </td>
        </tr>
    </table>

    <p style="margin:20px 0 0;">
        Se voce recebeu este email, sua configuracao de envio de emails esta funcionando corretamente.
    </p>
</x-emails.layout>
