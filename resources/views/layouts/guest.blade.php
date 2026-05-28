<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'EcoShare') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-100 antialiased">
        <main class="min-h-[100dvh] px-4 py-6 sm:px-6 sm:py-8 bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950 overflow-y-auto">
            <div class="grid lg:grid-cols-2 gap-8 lg:gap-10 w-full max-w-6xl mx-auto items-start lg:items-center min-h-[100dvh] lg:min-h-0">
                <section class="flex items-start lg:items-center justify-center w-full">
                    <div class="w-full max-w-lg">
                        <a href="/" class="mb-6 sm:mb-8 inline-flex items-center gap-3">
                            <img
                                src="{{ asset('favicon.png') }}"
                                alt="Logo EcoShare"
                                class="h-10 w-10 object-contain"
                            >
                            <h1 class="text-xl font-bold text-white tracking-wide">
                                Eco<span class="text-green-400">Share</span>
                            </h1>
                        </a>
                        {{ $slot }}
                    </div>
                </section>

                <aside class="hidden lg:flex items-center justify-center relative min-h-[560px]">
                    <div class="relative z-10 flex items-center justify-center w-full">
                        <img
                            src="{{ asset('images/hero-sustainable.png') }}"
                            alt="EcoShare"
                            class="w-[520px] xl:w-[650px] 2xl:w-[750px] object-contain scale-110 drop-shadow-2xl"
                        >
                    </div>
                </aside>
            </div>
        </main>
    </body>
</html>
