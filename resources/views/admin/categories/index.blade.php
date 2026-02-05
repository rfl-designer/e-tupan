<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        {{-- SortableJS --}}
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="min-h-svh p-6 md:p-10">
            <div class="mx-auto max-w-6xl">
                <livewire:category-tree />
            </div>
        </div>
        @fluxScripts
    </body>
</html>
