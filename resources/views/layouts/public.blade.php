<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-neutral-100 antialiased dark:bg-neutral-950">
        <main class="mx-auto w-full max-w-[1800px] p-4 md:p-8">
            {{ $slot }}
        </main>
        @fluxScripts
    </body>
</html>
