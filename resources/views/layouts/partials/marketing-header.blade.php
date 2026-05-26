{{--
    Navegación marketing: Explorar | Cómo funciona
    $active: null | 'explore' | 'how-it-works'
--}}
@php
    $active = $active ?? null;
    $navLink = 'group relative inline-flex items-center gap-2 rounded-lg px-3 py-2.5 text-[15px] font-medium tracking-tight outline-none transition-all duration-200 ease-out focus-visible:ring-2 focus-visible:ring-cyan-400/50 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950';
    $navIdle = 'text-slate-400 hover:bg-white/[0.04] hover:text-white';
    $navActive = 'text-cyan-100 bg-white/[0.08] ring-1 ring-cyan-400/25 shadow-[0_0_24px_-8px_rgba(34,211,238,0.35)]';
@endphp

<header class="relative z-20 border-b border-white/[0.06] bg-slate-950/40 backdrop-blur-md">
    <div class="mx-auto flex w-full max-w-[1400px] flex-wrap items-center gap-x-3 gap-y-3 px-4 py-3.5 sm:flex-nowrap sm:gap-6 sm:px-6 sm:py-4 md:px-8">
        <a
            href="{{ route('welcome') }}"
            aria-label="EcoShare, inicio"
            class="flex shrink-0 items-center gap-2.5 whitespace-nowrap rounded-lg outline-none ring-offset-2 ring-offset-slate-950 transition-opacity hover:opacity-95 focus-visible:ring-2 focus-visible:ring-cyan-400/40 sm:gap-3"
        >
            <img
                src="{{ asset('favicon.png') }}"
                alt=""
                width="40"
                height="40"
                class="h-9 w-9 object-contain sm:h-10 sm:w-10"
            >
            <span class="hidden text-lg font-extrabold tracking-tight text-white sm:inline">EcoShare</span>
        </a>

        <nav
            class="flex flex-1 flex-wrap items-center justify-center gap-1 sm:justify-start sm:gap-1 md:gap-2"
            aria-label="Secciones principales"
        >
            {{-- Explorar --}}
            <a
                href="{{ route('explore') }}"
                @if ($active === 'explore') aria-current="page" @endif
                class="{{ $navLink }} {{ $active === 'explore' ? $navActive : $navIdle }}"
            >
                <svg class="h-4 w-4 shrink-0 text-current opacity-70 transition-opacity duration-200 group-hover:opacity-100" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                </svg>
                <span class="whitespace-nowrap">Explorar</span>
                @if ($active === 'explore')
                    <span
                        class="pointer-events-none absolute inset-x-2 -bottom-px h-0.5 rounded-full bg-gradient-to-r from-transparent via-cyan-400 to-transparent opacity-95 shadow-[0_0_14px_rgba(34,211,238,0.35)]"
                        aria-hidden="true"
                    ></span>
                @endif
            </a>

            {{-- Cómo funciona --}}
            <a
                href="{{ route('how-it-works') }}"
                @if ($active === 'how-it-works') aria-current="page" @endif
                class="{{ $navLink }} {{ $active === 'how-it-works' ? $navActive : $navIdle }}"
            >
                <svg class="h-4 w-4 shrink-0 text-current opacity-70 transition-opacity duration-200 group-hover:opacity-100" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                <span class="whitespace-nowrap">Cómo funciona</span>
                @if ($active === 'how-it-works')
                    <span
                        class="pointer-events-none absolute inset-x-2 -bottom-px h-0.5 rounded-full bg-gradient-to-r from-transparent via-cyan-400 to-transparent opacity-95 shadow-[0_0_14px_rgba(34,211,238,0.35)]"
                        aria-hidden="true"
                    ></span>
                @endif
            </a>
        </nav>

        <div class="flex shrink-0 items-center justify-end gap-2 sm:ml-auto sm:gap-3">
            @auth
                <a
                    href="{{ route('dashboard') }}"
                    class="btn h-10 rounded-xl border border-white/20 bg-white/[0.06] px-4 text-sm font-medium text-white transition-colors duration-200 ease-out hover:border-white/30 hover:bg-white/10 sm:h-11 sm:px-5 sm:text-base"
                >Ir al muro</a>
            @else
                <a
                    href="{{ route('login') }}"
                    class="btn h-10 rounded-xl border border-white/20 bg-transparent px-4 text-sm font-medium text-slate-200 transition-colors duration-200 ease-out hover:border-white/35 hover:bg-white/[0.06] hover:text-white sm:h-11 sm:px-5 sm:text-base"
                >Iniciar sesión</a>
                <a
                    href="{{ route('register') }}"
                    class="btn h-10 rounded-xl bg-gradient-to-r from-cyan-400/95 to-emerald-400/95 px-4 text-sm font-semibold text-slate-950 shadow-md shadow-cyan-500/15 transition-[filter,box-shadow] duration-200 ease-out hover:brightness-105 sm:h-11 sm:px-6 sm:text-base"
                >Registrarse</a>
            @endauth
        </div>
    </div>
</header>
