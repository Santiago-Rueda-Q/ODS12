<x-guest-layout title="Registrar usuario | EcoShare">
    <div class="mb-5">
        <h1 class="text-4xl font-extrabold text-white leading-tight">
            Crea tu cuenta
        </h1>
        <p class="mt-2 text-sm text-slate-400">Únete a la comunidad sostenible.</p>
    </div>

    @php
        $stepTwoHasErrors = $errors->has('profile_photo') || $errors->has('description');
    @endphp

    <form id="register-form" method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="space-y-4" novalidate>
        @csrf

        <div id="step-1" class="space-y-4 {{ $stepTwoHasErrors ? 'hidden' : '' }}">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="first_name" class="mb-2 block text-sm font-semibold text-slate-100">Nombre</label>
                    <input id="first_name" class="w-full px-3 py-2.5 text-sm rounded-lg bg-white/10 border border-white/20 text-white focus:outline-none focus:ring-2 focus:ring-green-400" type="text" name="first_name" value="{{ old('first_name') }}" required autofocus autocomplete="given-name" />
                    <p id="first_name_client_error" class="mt-2 hidden rounded-lg border border-rose-400/30 bg-rose-500/10 px-3 py-2 text-sm text-rose-200"></p>
                    <x-input-error :messages="$errors->get('first_name')" class="mt-2 text-sm" />
                </div>

                <div>
                    <label for="last_name" class="mb-2 block text-sm font-semibold text-slate-100">Apellido</label>
                    <input id="last_name" class="w-full px-3 py-2.5 text-sm rounded-lg bg-white/10 border border-white/20 text-white focus:outline-none focus:ring-2 focus:ring-green-400" type="text" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name" />
                    <p id="last_name_client_error" class="mt-2 hidden rounded-lg border border-rose-400/30 bg-rose-500/10 px-3 py-2 text-sm text-rose-200"></p>
                    <x-input-error :messages="$errors->get('last_name')" class="mt-2 text-sm" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="eco_username" class="mb-2 block text-sm font-semibold text-slate-100">Nombre de Usuario (Opcional)</label>
                    <input id="eco_username" class="w-full px-3 py-2.5 text-sm rounded-lg bg-white/10 border border-white/20 text-white focus:outline-none focus:ring-2 focus:ring-green-400" type="text" name="eco_username" value="{{ old('eco_username') }}" placeholder="Auto-generado si vacío" />
                    <x-input-error :messages="$errors->get('eco_username')" class="mt-2 text-sm" />
                </div>

                <div>
                    <label for="linkedin" class="mb-2 block text-sm font-semibold text-slate-100">LinkedIn (Opcional)</label>
                    <input id="linkedin" class="w-full px-3 py-2.5 text-sm rounded-lg bg-white/10 border border-white/20 text-white focus:outline-none focus:ring-2 focus:ring-green-400" type="text" name="linkedin" value="{{ old('linkedin') }}" placeholder="usuario-linkedin" />
                    <x-input-error :messages="$errors->get('linkedin')" class="mt-2 text-sm" />
                </div>
            </div>

            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-slate-100">Correo electrónico</label>
                <input id="email" class="w-full px-3 py-2.5 text-sm rounded-lg bg-white/10 border border-white/20 text-white focus:outline-none focus:ring-2 focus:ring-green-400" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" />
                <p id="email_client_error" class="mt-2 hidden rounded-lg border border-rose-400/30 bg-rose-500/10 px-3 py-2 text-sm text-rose-200"></p>
                <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-slate-100">Contraseña</label>
                    <input id="password" class="w-full px-3 py-2.5 text-sm rounded-lg bg-white/10 border border-white/20 text-white focus:outline-none focus:ring-2 focus:ring-green-400" type="password" name="password" required autocomplete="new-password" />
                    <p id="password_client_error" class="mt-2 hidden rounded-lg border border-rose-400/30 bg-rose-500/10 px-3 py-2 text-sm text-rose-200"></p>
                    <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm" />
                </div>

                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-100">Confirmar contraseña</label>
                    <input id="password_confirmation" class="w-full px-3 py-2.5 text-sm rounded-lg bg-white/10 border border-white/20 text-white focus:outline-none focus:ring-2 focus:ring-green-400" type="password" name="password_confirmation" required autocomplete="new-password" />
                    <p id="password_confirmation_client_error" class="mt-2 hidden rounded-lg border border-rose-400/30 bg-rose-500/10 px-3 py-2 text-sm text-rose-200"></p>
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-sm" />
                </div>
            </div>

            <div>
                <label for="country" class="mb-2 block text-sm font-semibold text-slate-100">País</label>
                <select id="country" name="country" class="w-full rounded-lg bg-gray-800 border border-gray-600 px-3 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <option value="">Selecciona tu país</option>
                    <option value="Colombia" @selected(old('country') === 'Colombia')>Colombia</option>
                    <option value="México" @selected(old('country') === 'México')>México</option>
                    <option value="Argentina" @selected(old('country') === 'Argentina')>Argentina</option>
                    <option value="Chile" @selected(old('country') === 'Chile')>Chile</option>
                    <option value="Perú" @selected(old('country') === 'Perú')>Perú</option>
                    <option value="Ecuador" @selected(old('country') === 'Ecuador')>Ecuador</option>
                    <option value="Venezuela" @selected(old('country') === 'Venezuela')>Venezuela</option>
                    <option value="Bolivia" @selected(old('country') === 'Bolivia')>Bolivia</option>
                    <option value="Paraguay" @selected(old('country') === 'Paraguay')>Paraguay</option>
                    <option value="Uruguay" @selected(old('country') === 'Uruguay')>Uruguay</option>
                </select>
                <p id="country_client_error" class="mt-2 hidden rounded-lg border border-rose-400/30 bg-rose-500/10 px-3 py-2 text-sm text-rose-200"></p>
                <x-input-error :messages="$errors->get('country')" class="mt-2 text-sm" />
            </div>

            <button id="go-step-2" type="button" class="w-full py-2.5 mt-2 rounded-lg text-sm bg-gradient-to-r from-green-400 to-blue-500 text-white font-semibold hover:opacity-90 transition">
                Continuar
            </button>
        </div>

        <div id="step-2" class="{{ $stepTwoHasErrors ? '' : 'hidden' }} space-y-5 max-w-md">
            <button type="button" id="openEditor"
                class="w-full py-3 rounded-xl bg-gradient-to-r from-green-400 to-blue-500 text-white font-semibold hover:opacity-90 transition">
                Seleccionar y editar foto
            </button>

            <div class="flex justify-center">
                <img id="preview"
                    alt="Vista previa"
                    class="w-24 h-24 rounded-full object-cover hidden border-2 border-white">
            </div>

            <input type="hidden" id="profile_photo_base64" name="profile_photo_base64">
            <input id="profile_photo" type="file" name="profile_photo" class="hidden" required>
            <p id="profile_photo_client_error" class="mt-2 hidden rounded-lg border border-rose-400/30 bg-rose-500/10 px-3 py-2 text-sm text-rose-200"></p>
            <x-input-error :messages="$errors->get('profile_photo')" class="mt-2 text-sm" />

            <div>
                <label for="description" class="mb-2 block text-sm font-semibold text-slate-100">Descripción</label>
                <textarea
                    id="description"
                    name="description"
                    placeholder="Cuéntanos sobre ti..."
                    class="w-full rounded-xl bg-gray-800 border border-gray-600 px-4 py-3 text-white resize-none h-24 custom-scroll focus:outline-none focus:ring-2 focus:ring-green-400">{{ old('description') }}</textarea>
                <p id="description_client_error" class="mt-2 hidden rounded-lg border border-rose-400/30 bg-rose-500/10 px-3 py-2 text-sm text-rose-200"></p>
                <x-input-error :messages="$errors->get('description')" class="mt-2 text-sm" />
            </div>

            <div class="flex items-center gap-3">
                <button id="back-step-1" type="button" class="w-1/2 py-3 rounded-xl border border-white/30 bg-transparent text-white font-medium hover:bg-white/10 transition">
                    Atrás
                </button>
                <button type="submit" class="w-1/2 py-3 rounded-xl bg-gradient-to-r from-green-400 to-blue-500 text-white font-semibold hover:opacity-90 transition">
                    Registrarse
                </button>
            </div>
        </div>

        <p class="text-center text-sm text-slate-300">
            ¿Ya tienes cuenta?
            <a class="font-semibold text-cyan-300 hover:text-cyan-200" href="{{ route('login') }}">Inicia sesión</a>
        </p>
    </form>

    <x-avatar-cropper
        mode="register"
        crop-source="dynamic"
        preview-id="preview"
        base64-input-id="profile_photo_base64"
        open-button-id="openEditor"
        crop-image-id="imageCrop"
        modal-id="modalCrop"
        data-transfer-input-id="profile_photo"
        form-id="register-form"
        step-one-id="step-1"
        step-two-id="step-2"
    />
</x-guest-layout>
