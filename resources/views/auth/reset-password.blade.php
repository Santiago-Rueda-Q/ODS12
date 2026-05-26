<x-guest-layout title="Restablecer contraseña | EcoShare">
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-white leading-tight">
            Crear nueva contraseña
        </h1>
        <p class="mt-2 text-sm text-slate-300">
            Define una contraseña segura para proteger tu cuenta.
        </p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="mb-2 block text-sm font-semibold text-slate-100">Correo electrónico</label>
            <x-text-input id="email" class="block mt-1 w-full rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-white" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm" />
        </div>

        <div>
            <label for="password" class="mb-2 block text-sm font-semibold text-slate-100">Contraseña</label>
            <x-text-input id="password" class="block mt-1 w-full rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-white" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm" />
        </div>

        <div>
            <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-100">Confirmar contraseña</label>
            <x-text-input id="password_confirmation" class="block mt-1 w-full rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-white"
                type="password"
                name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-sm" />
        </div>

        <div class="pt-1">
            <x-primary-button class="w-full justify-center rounded-xl bg-gradient-to-r from-green-400 to-blue-500 py-3 text-white hover:opacity-90">
                Restablecer contraseña
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
