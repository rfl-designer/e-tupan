<div>
    <div class="relative overflow-hidden bg-bg-dark pb-20 pt-40 text-white">
        <div class="absolute right-0 top-0 h-full w-1/2 rounded-l-[100px] bg-primary opacity-10"></div>
        <div class="absolute bottom-0 left-0 h-32 w-32 rounded-tr-full bg-secondary opacity-20"></div>

        <div class="container mx-auto max-w-7xl px-6 relative z-10">
            <x-institutional.reveal>
                <span class="mb-4 block text-xs font-bold uppercase tracking-wider text-primary-light">Nossa História</span>
                <h1 class="mb-6 text-4xl font-medium md:text-5xl lg:text-6xl">
                    Autoridade não se declara, <br />
                    <span class="text-primary-light">se constrói.</span>
                </h1>
                <p class="max-w-2xl text-xl leading-relaxed text-neutral-light">
                    Há 16 anos, nascemos no Sertão com uma certeza: saúde não é lugar para improviso. Construímos nossa reputação com a mesma seriedade que aprendemos onde palavra vale mais que contrato.
                </p>
            </x-institutional.reveal>
        </div>
    </div>

    <x-institutional.section variant="white">
        <div class="grid items-center gap-16 lg:grid-cols-2">
            <x-institutional.reveal>
                <div class="relative">
                    <div class="absolute -left-4 -top-4 h-24 w-24 rounded-full bg-primary-bg -z-10"></div>
                    <img
                        src="{{ asset('images/institucional/about-image.png') }}"
                        alt="Equipe Tupan"
                        class="h-[500px] w-full rounded-[32px] object-cover shadow-2xl"
                    />
                    <div class="absolute -bottom-6 -right-6 max-w-xs rounded-2xl border-l-4 border-secondary bg-white p-6 shadow-xl">
                        <p class="text-lg font-semibold text-neutral-strong">"Somos uma empresa de saude feita do sal da terra."</p>
                    </div>
                </div>
            </x-institutional.reveal>

            <div class="space-y-8">
                <x-institutional.reveal :delay="100">
                    <h2 class="text-3xl font-medium text-neutral-strong">De Onde Viemos, Para Onde Vamos</h2>
                    <p class="mt-4 text-lg leading-relaxed text-neutral-medium">
                        A TUPAN nasceu no Sertão, em uma terra onde o comércio é construído na confiança, no aperto de mão e no respeito. Nosso fundador é farmacêutico, empresário e apaixonado por conhecimento. Ao lado de profissionais técnicos e gestores, construiu uma empresa que não se contenta em fazer mais do mesmo.
                    </p>
                    <p class="mt-4 text-lg leading-relaxed text-neutral-medium">
                        Atuamos em segmentos sensíveis: laboratório, banco de sangue, diagnóstico por imagem, curativos especiais, equipamentos hospitalares. Cada produto entregue pode impactar a vida de alguém, mesmo que indiretamente. Por isso, buscamos produtos com qualificação técnica, processos organizados e relacionamentos construídos no longo prazo.
                    </p>
                    <p class="mt-4 text-lg leading-relaxed text-neutral-medium">
                        Mais do que vender, intermediamos segurança, técnica e cuidado. Queremos ser referência não apenas em produtos, mas em informação confiável, em um mundo onde sobra discurso e falta verdade.
                    </p>
                </x-institutional.reveal>

                <x-institutional.reveal :delay="200">
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <div class="flex items-center gap-3 rounded-xl bg-bg-light p-4">
                            <flux:icon name="building-office" class="size-6 text-primary" />
                            <div>
                                <h4 class="font-bold text-neutral-strong">Sede em Recife</h4>
                                <p class="text-sm text-neutral-medium">Hub logístico estratégico para todo o Nordeste</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl bg-bg-light p-4">
                            <flux:icon name="map" class="size-6 text-primary" />
                            <div>
                                <h4 class="font-bold text-neutral-strong">16 Anos de Atuação</h4>
                                <p class="text-sm text-neutral-medium">Construindo autoridade com estudo e entrega</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl bg-bg-light p-4">
                            <flux:icon name="sparkles" class="size-6 text-primary" />
                            <div>
                                <h4 class="font-bold text-neutral-strong">Raízes Sertanejas</h4>
                                <p class="text-sm text-neutral-medium">Seriedade e compromisso em cada relação</p>
                            </div>
                        </div>
                    </div>
                </x-institutional.reveal>
            </div>
        </div>
    </x-institutional.section>

    <div class="bg-bg-light">
        <x-institutional.sections.values />
    </div>

    <x-institutional.section variant="cream">
        <div class="mx-auto mb-16 max-w-3xl text-center">
            <x-institutional.reveal>
                <h2 class="mb-4 text-3xl font-medium text-neutral-strong">O Que Nos Diferencia</h2>
                <p class="text-neutral-medium">
                    Não entregamos apenas caixas. Entregamos conhecimento técnico aplicado, suporte consultivo e a segurança de quem entende a responsabilidade de atuar em saúde.
                </p>
            </x-institutional.reveal>
        </div>

        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
            <x-institutional.reveal :delay="100">
                <x-institutional.card variant="standard" class="h-full hover:border-primary transition-colors">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-primary-bg text-primary">
                        <flux:icon name="book-open" class="size-6" />
                    </div>
                    <h3 class="mb-3 text-xl font-semibold">Conhecimento Técnico Aplicado</h3>
                    <p class="text-neutral-medium">
                        Nosso CEO é farmacêutico. Nossa equipe estuda, testa e valida antes de padronizar. Não fazemos mais do mesmo: buscamos produtos com real qualificação técnica.
                    </p>
                </x-institutional.card>
            </x-institutional.reveal>

            <x-institutional.reveal :delay="200">
                <x-institutional.card variant="standard" class="h-full hover:border-primary transition-colors">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-secondary/10 text-secondary">
                        <flux:icon name="hand-thumb-up" class="size-6" />
                    </div>
                    <h3 class="mb-3 text-xl font-semibold">Consultoria, Não Apenas Venda</h3>
                    <p class="text-neutral-medium">
                        Assumimos papel de consultor, não apenas de fornecedor. Explicamos o porquê das escolhas técnicas, apoiamos decisões e construímos parcerias de longo prazo.
                    </p>
                </x-institutional.card>
            </x-institutional.reveal>

            <x-institutional.reveal :delay="300">
                <x-institutional.card variant="standard" class="h-full hover:border-primary transition-colors">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                        <flux:icon name="heart" class="size-6" />
                    </div>
                    <h3 class="mb-3 text-xl font-semibold">Compromisso com Vidas</h3>
                    <p class="text-neutral-medium">
                        Saúde não é faturamento. É responsabilidade. Cada curativo, reagente, equipamento ou seringa precisa estar no lugar certo, na hora certa, com o suporte certo.
                    </p>
                </x-institutional.card>
            </x-institutional.reveal>

            <x-institutional.reveal :delay="400">
                <x-institutional.card variant="standard" class="h-full hover:border-primary transition-colors">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-orange-100 text-orange-600">
                        <flux:icon name="briefcase" class="size-6" />
                    </div>
                    <h3 class="mb-3 text-xl font-semibold">Parcerias com Multinacionais</h3>
                    <p class="text-neutral-medium">
                        Representamos fabricantes de referência mundial, como Fresenius e Inbras. Nossa credibilidade é validada por quem exige o mais alto padrão de qualidade.
                    </p>
                </x-institutional.card>
            </x-institutional.reveal>
        </div>
    </x-institutional.section>
</div>
