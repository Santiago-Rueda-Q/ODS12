<x-guest-layout title="Ingresar | EcoShare">
    <div class="mb-8">
        <h1 class="text-4xl font-extrabold text-white leading-tight">
            ¡Bienvenido de nuevo!
        </h1>
        <p class="mt-2 text-sm text-slate-300">Ingresa para continuar.</p>
    </div>

    <x-auth-session-status class="mb-4 rounded-xl border border-emerald-300/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="mb-2 block text-sm font-semibold text-slate-100">Correo electrónico</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M2.94 5.5A2 2 0 0 1 4.9 4h10.2a2 2 0 0 1 1.96 1.5L10 9.72 2.94 5.5Zm-.04 1.74V14a2 2 0 0 0 2 2h10.2a2 2 0 0 0 2-2V7.24L10.45 11.5a1 1 0 0 1-.9 0L2.9 7.24Z"/></svg>
                </span>
                <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="Ingresa tu correo" required autofocus autocomplete="username" class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-green-400 pl-10">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm" />
        </div>

        <div>
            <label for="password" class="mb-2 block text-sm font-semibold text-slate-100">Contraseña</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 2a4 4 0 0 0-4 4v2H5a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-5a2 2 0 0 0-2-2h-1V6a4 4 0 0 0-4-4Zm2 6V6a2 2 0 1 0-4 0v2h4Z" clip-rule="evenodd"/></svg>
                </span>
                <input id="password" name="password" type="password" required autocomplete="current-password" class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-green-400 pl-10">
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-300">
                <input id="remember_me" type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                Recuérdame
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-cyan-300 transition hover:text-cyan-200">
                    ¿Olvidaste tu contraseña?
                </a>
            @endif
        </div>

        <div class="pt-1">
            <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-green-400 to-blue-500 text-white font-semibold hover:opacity-90 transition">
                Ingresar
            </button>
        </div>

        <p class="text-center text-sm text-slate-300">
            ¿No tienes cuenta?
            <a href="{{ route('register') }}" class="font-semibold text-cyan-300 hover:text-cyan-200">Regístrate</a>
        </p>
    </form>
</x-guest-layout>
