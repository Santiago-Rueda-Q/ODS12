<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'EcoShare')</title>
    <meta name="description" content="@yield('meta_description', 'EcoShare — EcoAnálisiss, cultura y comunidad sostenible.')">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-[100dvh] bg-slate-950 font-sans text-white antialiased">
    <div class="relative flex min-h-[100dvh] flex-col overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950/95">
        @include('layouts.partials.marketing-header', ['active' => $active ?? null])

        <main class="relative z-10 mx-auto w-full max-w-[1100px] flex-1 px-4 py-10 sm:px-6 sm:py-14 md:py-16">
            @yield('content')
        </main>

        <footer class="relative z-10 border-t border-white/10 py-8 text-center text-sm text-slate-500">
            <p>&copy; {{ date('Y') }} EcoShare · <a href="{{ route('welcome') }}" class="text-slate-400 underline-offset-2 hover:text-cyan-300 hover:underline">Inicio</a></p>
        </footer>
    </div>
</body>
</html>
