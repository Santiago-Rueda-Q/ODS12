<x-guest-layout title="Verificar correo | EcoShare">
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-white leading-tight">
            Verifica tu correo electrónico
        </h1>
        <p class="mt-2 text-sm text-slate-300">
            Te enviamos un enlace de verificación. Ábrelo para activar tu cuenta y continuar.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 rounded-xl border border-emerald-300/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200">
            Enviamos un nuevo enlace de verificación a tu correo electrónico.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button class="rounded-xl bg-gradient-to-r from-green-400 to-blue-500 text-white hover:opacity-90">
                    Reenviar correo de verificación
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="text-sm font-medium text-cyan-300 transition hover:text-cyan-200 focus:outline-none">
                Cerrar sesión
            </button>
        </form>
    </div>
</x-guest-layout>
