<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')

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
    <body class="min-h-screen bg-white antialiased dark:bg-zinc-900">
        <div class="flex min-h-screen flex-col">
            <x-storefront.header />

            <main class="flex-1">
                {{ $slot }}
            </main>

            <x-storefront.footer />
        </div>

        @fluxScripts
    </body>
</html>
