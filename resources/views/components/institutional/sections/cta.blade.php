@props([
    'href' => '#contato',
])

@php
    $whatsappReady = \App\Domain\Institutional\Services\WhatsappLink::isAvailable();
    $whatsapp = \App\Domain\Institutional\Services\WhatsappLink::make();
@endphp

<section class="relative overflow-hidden bg-primary-strong py-20">
    <div class="absolute left-0 top-0 h-64 w-64 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white/10"></div>
    <div class="absolute bottom-0 right-0 h-96 w-96 translate-x-1/3 translate-y-1/3 rounded-full bg-white/10"></div>

    <div class="container mx-auto max-w-4xl px-6 text-center relative z-10">
        <x-institutional.reveal>
            <h2 class="mb-6 text-3xl font-bold text-white md:text-4xl">
                Sua instituição merece um parceiro técnico, não apenas um fornecedor.
            </h2>
            <p class="mb-10 max-w-2xl mx-auto text-lg text-white">
                Fale com nossa equipe de consultores especializados. Entendemos que por trás de cada pedido existe um paciente esperando pelo melhor cuidado.
            </p>
            <div class="flex flex-col justify-center gap-4 sm:flex-row">
                <a href="{{ $href }}" class="inline-flex items-center justify-center rounded-[999px] border border-transparent bg-white px-6 py-3 text-sm font-semibold text-neutral-strong shadow-sm transition hover:bg-primary-bg hover:shadow-md">
                    Falar com um consultor
                </a>
                @if($whatsappReady)
                    <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-[999px] border-2 border-white/80 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                        Falar pelo WhatsApp
                    </a>
                @endif
            </div>
        </x-institutional.reveal>
    </div>
</section>
