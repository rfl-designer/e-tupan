@php
    $whatsapp = config('institutional.contact.whatsapp', 'https://wa.me/');
@endphp

<section class="relative overflow-hidden bg-primary py-20">
    <div class="absolute left-0 top-0 h-64 w-64 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white/10"></div>
    <div class="absolute bottom-0 right-0 h-96 w-96 translate-x-1/3 translate-y-1/3 rounded-full bg-white/10"></div>

    <div class="container mx-auto max-w-4xl px-6 text-center relative z-10">
        <x-institutional.reveal>
            <h2 class="mb-6 text-3xl font-bold text-white md:text-4xl">
                Sua instituicao merece um parceiro tecnico, nao apenas um fornecedor.
            </h2>
            <p class="mb-10 max-w-2xl mx-auto text-lg text-primary-bg">
                Fale com nossa equipe de consultores especializados. Entendemos que por tras de cada pedido existe um paciente esperando pelo melhor cuidado.
            </p>
            <div class="flex flex-col justify-center gap-4 sm:flex-row">
                <a href="#contato" class="inline-flex items-center justify-center rounded-[999px] bg-white px-6 py-3 text-xs font-semibold uppercase tracking-[0.2em] text-primary">
                    Solicitar Consultoria Tecnica
                </a>
                <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-[999px] border border-transparent bg-secondary px-6 py-3 text-xs font-semibold text-white shadow-sm transition hover:bg-secondary-hover hover:shadow-md">
                    Falar pelo WhatsApp
                </a>
            </div>
        </x-institutional.reveal>
    </div>
</section>
