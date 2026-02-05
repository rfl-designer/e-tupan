<x-mail::message>
# Email de Teste

Este é um email de teste enviado pelo painel administrativo de **{{ $storeName }}**.

**Data do envio:** {{ $sentAt->format('d/m/Y') }}

**Hora do envio:** {{ $sentAt->format('H:i:s') }}

---

Se você recebeu este email, significa que as configurações de email estão funcionando corretamente.

Atenciosamente,<br>
{{ $storeName }}
</x-mail::message>
