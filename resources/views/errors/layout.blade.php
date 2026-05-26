<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Algo salió distinto') · EcoShare</title>
    <meta name="description" content="@yield('meta_description', 'Información sobre lo que ocurrió y cómo continuar.')">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 text-slate-900 antialiased selection:bg-teal-100/90 selection:text-slate-900">
    <header class="border-b border-slate-200 bg-gray-100">
        <div class="mx-auto flex h-14 w-full max-w-6xl items-center justify-center px-4">
            <span class="text-sm font-semibold tracking-wide text-gray-800">EcoShare</span>
        </div>
    </header>

    <main class="mx-auto flex w-full max-w-6xl flex-1 items-center px-4 py-8 sm:px-6 sm:py-10">
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-gray-100">
        <div class="mx-auto w-full max-w-6xl px-4 py-4 text-center text-xs text-gray-500 sm:px-6">
            Si el problema persiste, contacta al equipo de soporte.
        </div>
    </footer>
</body>
</html>
