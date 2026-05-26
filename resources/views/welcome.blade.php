<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EcoShare</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <style>
        .hero-title {
            font-family: ui-sans-serif, system-ui, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            letter-spacing: 0.02em;
            line-height: 1.05;
        }

        @keyframes float {
            0%, 100% {
                transform: translate3d(0, 0, 0) rotate(0deg) scale(1);
            }
            25% {
                transform: translate3d(3px, -7px, 0) rotate(0.35deg) scale(1.02);
            }
            50% {
                transform: translate3d(0, -12px, 0) rotate(-0.4deg) scale(1.03);
            }
            75% {
                transform: translate3d(-3px, -6px, 0) rotate(0.3deg) scale(1.015);
            }
        }

        .animate-float {
            animation: float 7.5s ease-in-out infinite;
            transform-origin: 50% 60%;
            will-change: transform;
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-[100dvh] bg-slate-950 font-sans text-white antialiased">
    <section class="relative flex min-h-[100dvh] flex-col overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950/95">

        @include('layouts.partials.marketing-header', ['active' => null])

        <main class="relative z-10 mx-auto flex w-full max-w-[1400px] flex-1 flex-col items-center justify-between gap-10 px-8 pt-10 pb-12 lg:flex-row lg:gap-10 xl:gap-16 lg:py-16">
            <div class="-mt-6 max-w-2xl space-y-5 text-center lg:-mt-10 lg:text-left">
                <p class="inline-flex rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-emerald-300/90">
                    PRODUCCIÓN Y CONSUMO RESPONSABLE
                </p>

                <h1 class="hero-title text-5xl font-extrabold leading-tight tracking-wide md:text-6xl xl:text-7xl">
                    Sostenibilidad en acción, impacto real
                </h1>

                <p class="mx-auto max-w-lg text-lg leading-relaxed text-slate-300 md:text-xl lg:mx-0">
                    Únete a EcoShare para compartir prácticas sostenibles, reducir residuos y promover la economía circular impulsando el ODS 12.
                </p>

                <div class="mt-6 flex flex-col items-center gap-6 sm:flex-row lg:items-start">
                    <a href="{{ route('register') }}" class="btn inline-flex w-full bg-gradient-to-r from-emerald-300 via-cyan-300 to-indigo-300 px-8 text-lg font-bold text-slate-900 shadow-lg shadow-emerald-500/20 hover:brightness-110 sm:w-auto">
                        Comenzar ahora
                    </a>
                    <a href="{{ route('login') }}" class="btn inline-flex w-full border border-white/30 bg-transparent px-8 text-lg font-medium text-white hover:bg-white/10 sm:w-auto">
                        Ya tengo cuenta
                    </a>
                </div>
            </div>

            <div class="flex items-center justify-center lg:justify-end">
                <img
                    src="{{ asset('images/image-hero.png') }}"
                    alt="Hero EcoShare"
                    class="w-[660px] object-contain drop-shadow-[0_30px_80px_rgba(0,0,0,0.6)] md:w-[730px] xl:w-[880px] 2xl:w-[980px] animate-float"
                />
            </div>
        </main>

    </section>
</body>
</html>
