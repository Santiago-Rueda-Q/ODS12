<x-guest-layout title="Confirmar contraseña | EcoShare">
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-white leading-tight">
            Confirma tu contraseña
        </h1>
        <p class="mt-2 text-sm text-slate-300">
            Esta es una zona segura. Ingresa tu contraseña para continuar.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <label for="password" class="mb-2 block text-sm font-semibold text-slate-100">Contraseña</label>
            <x-text-input id="password" class="block mt-1 w-full rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-white"
                type="password"
                name="password"
                required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm" />
        </div>

        <div class="pt-1">
            <x-primary-button class="w-full justify-center rounded-xl bg-gradient-to-r from-green-400 to-blue-500 py-3 text-white hover:opacity-90">
                Confirmar
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
