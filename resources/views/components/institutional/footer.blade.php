@php
    $contact = config('institutional.contact');
    $divisions = config('institutional.divisions');
    $hasPhone = ! str_starts_with((string) ($contact['phone'] ?? ''), '[PREENCHER');
    $hasAddress = ! str_starts_with((string) ($contact['address'] ?? '[PREENCHER'), '[PREENCHER');
    $socials = array_filter([
        'Instagram' => $contact['social']['instagram'] ?? null,
        'LinkedIn' => $contact['social']['linkedin'] ?? null,
        'Facebook' => $contact['social']['facebook'] ?? null,
    ]);
@endphp

<footer class="bg-bg-dark text-white pt-16 pb-8 border-t border-neutral-medium/20">
    <div class="container mx-auto max-w-7xl px-6">
        <div class="mb-16 grid grid-cols-1 gap-12 md:grid-cols-2 lg:grid-cols-4">
            <div class="col-span-1 space-y-4 md:col-span-1">
                <div class="flex items-center gap-2">
                    <img
                        src="{{ asset('images/logo-tupan-400.webp') }}"
                        alt="Logo TUPAN"
                        width="147"
                        height="36"
                        loading="lazy"
                        class="h-9 w-auto"
                    />
                    <span class="sr-only">TUPAN</span>
                </div>
                <p class="text-sm leading-relaxed text-neutral-light">
                    Há 16 anos intermediando segurança, técnica e cuidado para quem atua em saúde. Nascemos no Sertão. Construímos autoridade com estudo, responsabilidade e entrega coerente.
                </p>
                @if(count($socials) > 0)
                <div class="-ml-2.5 flex gap-1 pt-2">
                    @isset($socials['Instagram'])<a href="{{ $socials['Instagram'] }}" target="_blank" rel="noopener noreferrer" class="p-2.5 text-neutral-light transition-colors hover:text-primary-light" aria-label="Instagram">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="5" ry="5"></rect>
                            <path d="M16 11.37a4 4 0 1 1-7.87 1.13 4 4 0 0 1 7.87-1.13z"></path>
                            <line x1="17.5" y1="6.5" x2="17.5" y2="6.5"></line>
                        </svg>
                    </a>@endisset
                    @isset($socials['LinkedIn'])<a href="{{ $socials['LinkedIn'] }}" target="_blank" rel="noopener noreferrer" class="p-2.5 text-neutral-light transition-colors hover:text-primary-light" aria-label="LinkedIn">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path>
                            <rect x="2" y="9" width="4" height="12"></rect>
                            <circle cx="4" cy="4" r="2"></circle>
                        </svg>
                    </a>@endisset
                    @isset($socials['Facebook'])<a href="{{ $socials['Facebook'] }}" target="_blank" rel="noopener noreferrer" class="p-2.5 text-neutral-light transition-colors hover:text-primary-light" aria-label="Facebook">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                        </svg>
                    </a>@endisset
                </div>
                @endif
            </div>

            <div>
                <h5 class="mb-6 text-sm font-bold uppercase tracking-wider text-neutral-light">Divisões Especializadas</h5>
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
                <h5 class="mb-6 text-sm font-bold uppercase tracking-wider text-neutral-light">Institucional</h5>
                <ul class="space-y-3 text-sm text-neutral-light">
                    <li><a href="{{ route('about') }}" class="transition-colors hover:text-primary-light">Quem Somos</a></li>
                    <li><a href="{{ route('about') }}" class="transition-colors hover:text-primary-light">Nossa História</a></li>
                    <li><a href="{{ route('blog.index') }}" class="transition-colors hover:text-primary-light">Conhecimento Técnico</a></li>
                    <li><a href="{{ route('contact') }}" class="transition-colors hover:text-primary-light">Trabalhe Conosco</a></li>
                    @if(! empty($contact['privacy_url']))
                        <li><a href="{{ $contact['privacy_url'] }}" class="transition-colors hover:text-primary-light">Política de Privacidade</a></li>
                    @endif
                </ul>
            </div>

            <div>
                <h5 class="mb-6 text-sm font-bold uppercase tracking-wider text-neutral-light">Fale Conosco</h5>
                <ul class="space-y-3 text-sm text-neutral-light">
                    @if($hasPhone)
                    <li><x-institutional.contact-link type="phone" :value="$contact['phone'] ?? ''" class="hover:text-primary-light hover:underline" /></li>
                    @endif
                    <li class="min-w-0 break-all"><x-institutional.contact-link type="email" :value="$contact['page_emails'][0] ?? $contact['emails'][1] ?? 'comercial@tupan.com.br'" class="hover:text-primary-light hover:underline" /></li>
                    <li>{{ $contact['city'] ?? 'Recife, PE' }}</li>
                    @if($hasAddress)
                        <li>{{ $contact['address'] }}</li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="flex flex-col items-center justify-between gap-4 border-t border-neutral-medium/20 pt-8 text-xs text-neutral-light md:flex-row">
            <p>&copy; {{ date('Y') }} TUPAN - Produtos para Sua Saúde. Todos os direitos reservados.</p>
            <p>Conhecimento técnico aplicado há 16 anos.</p>
        </div>
    </div>
</footer>
