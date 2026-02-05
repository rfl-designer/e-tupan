@php
    $contact = config('institutional.contact');
    $divisions = config('institutional.divisions');
@endphp

<footer class="bg-bg-dark text-white pt-16 pb-8 border-t border-neutral-medium/20">
    <div class="container mx-auto max-w-7xl px-6">
        <div class="mb-16 grid gap-12 md:grid-cols-4">
            <div class="col-span-1 space-y-4 md:col-span-1">
                <div class="flex items-center gap-2">
                    <img
                        src="{{ asset('images/logo-tupan.png') }}"
                        alt="Logo TUPAN"
                        class="h-9 w-auto"
                    />
                    <span class="sr-only">TUPAN</span>
                </div>
                <p class="text-sm leading-relaxed text-neutral-light">
                    Ha 16 anos intermediando seguranca, tecnica e cuidado para quem atua em saude. Nascemos no Sertao. Construimos autoridade com estudo, responsabilidade e entrega coerente.
                </p>
                <div class="flex gap-4 pt-2">
                    <a href="#" class="text-neutral-light transition-colors hover:text-primary-light" aria-label="Instagram">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="5" ry="5"></rect>
                            <path d="M16 11.37a4 4 0 1 1-7.87 1.13 4 4 0 0 1 7.87-1.13z"></path>
                            <line x1="17.5" y1="6.5" x2="17.5" y2="6.5"></line>
                        </svg>
                    </a>
                    <a href="#" class="text-neutral-light transition-colors hover:text-primary-light" aria-label="LinkedIn">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path>
                            <rect x="2" y="9" width="4" height="12"></rect>
                            <circle cx="4" cy="4" r="2"></circle>
                        </svg>
                    </a>
                    <a href="#" class="text-neutral-light transition-colors hover:text-primary-light" aria-label="Facebook">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                        </svg>
                    </a>
                </div>
            </div>

            <div>
                <h5 class="mb-6 text-sm font-bold uppercase tracking-wider text-neutral-medium">Divisoes Especializadas</h5>
                <ul class="space-y-3 text-sm text-neutral-light">
                    @foreach($divisions as $division)
                        <li>
                            <a href="{{ route('solutions.show', $division['id']) }}" class="transition-colors hover:text-primary-light">
                                {{ $division['title'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h5 class="mb-6 text-sm font-bold uppercase tracking-wider text-neutral-medium">Institucional</h5>
                <ul class="space-y-3 text-sm text-neutral-light">
                    <li><a href="{{ route('about') }}" class="transition-colors hover:text-primary-light">Quem Somos</a></li>
                    <li><a href="{{ route('about') }}" class="transition-colors hover:text-primary-light">Nossa Historia</a></li>
                    <li><a href="{{ route('blog.index') }}" class="transition-colors hover:text-primary-light">Conhecimento Tecnico</a></li>
                    <li><a href="{{ route('contact') }}" class="transition-colors hover:text-primary-light">Trabalhe Conosco</a></li>
                    <li><a href="#" class="transition-colors hover:text-primary-light">Politica de Privacidade</a></li>
                </ul>
            </div>

            <div>
                <h5 class="mb-6 text-sm font-bold uppercase tracking-wider text-neutral-medium">Fale Conosco</h5>
                <ul class="space-y-3 text-sm text-neutral-light">
                    <li>{{ $contact['phone'] ?? '(81) 3333-0000' }}</li>
                    <li>{{ $contact['page_emails'][0] ?? $contact['emails'][1] ?? 'comercial@tupan.com.br' }}</li>
                    <li>Av. Principal, 1000 - Recife, PE</li>
                </ul>
            </div>
        </div>

        <div class="flex flex-col items-center justify-between gap-4 border-t border-neutral-medium/20 pt-8 text-xs text-neutral-medium md:flex-row">
            <p>&copy; {{ date('Y') }} TUPAN - Produtos para Sua Saude. Todos os direitos reservados.</p>
            <p>Conhecimento tecnico aplicado ha 16 anos.</p>
        </div>
    </div>
</footer>
