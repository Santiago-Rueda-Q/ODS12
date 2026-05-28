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
<body class="min-h-screen bg-slate-950 text-slate-200 antialiased selection:bg-emerald-500/30 selection:text-emerald-200">
    <header class="border-b border-slate-800/60 bg-slate-950/80 backdrop-blur-sm">
        <div class="mx-auto flex h-14 w-full max-w-6xl items-center justify-center px-4">
            <span class="text-sm font-bold tracking-widest text-emerald-400 uppercase">EcoShare</span>
        </div>
    </header>

    <main class="mx-auto flex w-full max-w-6xl flex-1 items-center px-4 py-8 sm:px-6 sm:py-10">
        @yield('content')
    </main>

    <footer class="border-t border-slate-800/60 bg-slate-950/80 backdrop-blur-sm">
        <div class="mx-auto w-full max-w-6xl px-4 py-4 text-center text-xs text-slate-500 sm:px-6">
            Si el problema persiste, contacta al equipo de soporte.
        </div>
    </footer>
</body>
</html>
