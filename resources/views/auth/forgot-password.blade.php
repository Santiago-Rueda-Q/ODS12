<x-guest-layout title="Recuperar contraseña | EcoShare">
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-white leading-tight">
            Recuperar contraseña
        </h1>
        <p class="mt-2 text-sm text-slate-300">
            Ingresa tu correo electrónico y te enviaremos un enlace para crear una nueva contraseña.
        </p>
    </div>

    <x-auth-session-status class="mb-4 rounded-xl border border-emerald-300/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="mb-2 block text-sm font-semibold text-slate-100">Correo electrónico</label>
            <x-text-input id="email" class="block mt-1 w-full rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-white" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm" />
        </div>

        <div class="pt-1">
            <x-primary-button class="w-full justify-center rounded-xl bg-gradient-to-r from-green-400 to-blue-500 py-3 text-white hover:opacity-90">
                Enviar enlace de recuperación
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
