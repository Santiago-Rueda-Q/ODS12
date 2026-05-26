@extends('layouts.marketing', ['active' => 'how-it-works'])

@section('title', 'Cómo funciona — EcoShare')

@section('meta_description', 'Cómo publicar un EcoAnálisis, cómo funciona el análisis asistido y cómo ver el resultado en tu publicación.')

@section('content')
    <article class="mx-auto max-w-3xl space-y-10">
        <header class="space-y-4 text-center sm:text-left">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-200/80">Cómo funciona</p>
            <h1 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl md:text-5xl">
                EcoAnálisiss con contexto y análisis en la misma tarjeta
            </h1>
            <p class="text-lg leading-relaxed text-slate-300">
                EcoShare une una publicación tipo red social con un análisis técnico breve generado en segundo plano: tú escribes la experiencia; el sistema la complementa con una lectura estructurada cuando la configuración del servidor lo permite.
            </p>
        </header>

        <ol class="relative space-y-8 border-l border-white/15 pl-8 sm:pl-10">
            <li class="relative">
                <span class="absolute -left-[9px] top-1.5 h-4 w-4 rounded-full border-2 border-emerald-400/80 bg-slate-950 sm:-left-[11px]"></span>
                <h2 class="text-xl font-bold text-white">Publicas tu EcoAnálisis</h2>
                <p class="mt-2 text-slate-300">
                    Añades título, descripción y etiquetas (país, tipo de práctica, impacto, experiencia). Opcionalmente una imagen. Las etiquetas ayudan a clasificar; el relato en texto es la base del análisis automático.
                </p>
            </li>
            <li class="relative">
                <span class="absolute -left-[9px] top-1.5 h-4 w-4 rounded-full border-2 border-emerald-400/80 bg-slate-950 sm:-left-[11px]"></span>
                <h2 class="text-xl font-bold text-white">El servidor encola un análisis</h2>
                <p class="mt-2 text-slate-300">
                    No hace falta esperar en la misma pantalla: un proceso en cola llama a un modelo de lenguaje configurado en el entorno del servidor. Las credenciales de la API no salen del backend.
                </p>
            </li>
            <li class="relative">
                <span class="absolute -left-[9px] top-1.5 h-4 w-4 rounded-full border-2 border-emerald-400/80 bg-slate-950 sm:-left-[11px]"></span>
                <h2 class="text-xl font-bold text-white">Ves el resultado en la tarjeta</h2>
                <p class="mt-2 text-slate-300">
                    En el muro y en el detalle del post, la tarjeta permite alternar entre la publicación y el panel de análisis (historia, afinidad, equilibrio, recomendación y puntuación). Si el análisis tarda unos segundos, la interfaz puede actualizarse cuando está listo sin recargar la página.
                </p>
            </li>
            <li class="relative">
                <span class="absolute -left-[9px] top-1.5 h-4 w-4 rounded-full border-2 border-emerald-400/80 bg-slate-950 sm:-left-[11px]"></span>
                <h2 class="text-xl font-bold text-white">Autor: volver a analizar</h2>
                <p class="mt-2 text-slate-300">
                    Si editaste el texto o quieres reintentar, como autor puedes solicitar un nuevo análisis desde la vista del post cuando la aplicación lo ofrezca.
                </p>
            </li>
        </ol>

        <div class="rounded-2xl border border-amber-500/25 bg-amber-950/20 px-5 py-4 text-sm leading-relaxed text-amber-100/95">
            <strong class="text-amber-200">Nota:</strong> si no hay clave de API o el servicio no responde, el sistema puede guardar un mensaje de respaldo para que la tarjeta no quede vacía. La experiencia principal no depende de volver a llamar a la IA desde el navegador.
        </div>

        <div class="flex flex-col items-center justify-center gap-4 pt-2 sm:flex-row">
            <a
                href="{{ route('explore') }}"
                class="text-sm font-semibold text-cyan-300 underline-offset-2 hover:underline"
            >← Volver a Explorar</a>
            @guest
                <a
                    href="{{ route('register') }}"
                    class="btn inline-flex bg-gradient-to-r from-cyan-300 to-emerald-300 px-6 py-2.5 text-sm font-bold text-slate-900 hover:brightness-110"
                >Empezar</a>
            @endguest
        </div>
    </article>
@endsection
