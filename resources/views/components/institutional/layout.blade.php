<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        @if(isset($metaTitle))
            <meta property="og:title" content="{{ $metaTitle }}" />
        @endif
        @if(isset($metaDescription))
            <meta name="description" content="{{ $metaDescription }}" />
            <meta property="og:description" content="{{ $metaDescription }}" />
        @endif
        @if(isset($canonicalUrl))
            <link rel="canonical" href="{{ $canonicalUrl }}" />
        @endif
        @if(isset($metaRobots))
            <meta name="robots" content="{{ $metaRobots }}" />
        @endif
    </head>
    <body
        class="min-h-screen bg-bg-light text-neutral-strong antialiased selection:bg-primary-bg selection:text-primary-dark font-sans"
        style="--font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif;"
    >
        <div class="flex min-h-screen flex-col">
            <livewire:institutional.header />

            <main class="flex-1">
                {{ $slot }}
            </main>

            <x-institutional.footer />
        </div>

        @fluxScripts
    </body>
</html>
