@php
    $comfortLine = $comfort_line ?? 'Si no carga en este momento, inténtalo de nuevo en unos segundos.';

    $errorImage = is_file(public_path('entre_sabores_error.png'))
        ? asset('entre_sabores_error.png')
        : asset('images/entre_sabores_error.png');
@endphp

{{-- Vista estilo panel de aplicación --}}
<section class="mx-auto w-full max-w-xl text-center">
    <div class="mb-5 flex justify-center">
        <img
            src="{{ $errorImage }}"
            alt="Ilustración de error"
            class="error-page-illustration"
            loading="eager"
            decoding="async"
            draggable="false"
        >
    </div>

    <div class="space-y-3">
        <h1 class="text-2xl font-bold tracking-tight text-slate-50 sm:text-3xl">
            {{ $title ?? 'Vaya, no encontramos esa página' }}
        </h1>
        <p class="mx-auto max-w-lg text-sm leading-relaxed text-slate-400 sm:text-base">
            {{ $message ?? 'La dirección puede haber cambiado o ya no estar disponible. Puedes volver al inicio o reintentar ahora.' }}
        </p>
    </div>

    @if (! empty($comfortLine))
        <p class="mt-3 text-sm text-slate-500">
            {{ $comfortLine }}
        </p>
    @endif

    <div class="mt-7 flex flex-col items-center justify-center gap-3 sm:flex-row">
        <a href="{{ route('welcome') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 sm:w-auto">
            <span class="inline-flex h-4 w-4 items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-4 w-4" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6" />
                </svg>
            </span>
            <span>Volver al inicio</span>
        </a>

        <a href="{{ url()->full() }}" class="inline-flex w-full items-center justify-center gap-2 rounded-full border border-slate-700 bg-slate-800/50 px-6 py-2.5 text-sm font-semibold text-slate-300 transition hover:bg-slate-700 hover:text-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 sm:w-auto">
            <span class="inline-flex h-4 w-4 items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-4 w-4" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 4.5v6h6m9-1.5a9 9 0 10-2.64 6.36L19.5 18" />
                </svg>
            </span>
            <span>Reintentar</span>
        </a>
    </div>
</section>
