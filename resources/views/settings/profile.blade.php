@extends('layouts.settings', [
    'title' => __('Editar perfil'),
    'user' => $user,
    'active' => 'profile',
    'avatarId' => 'avatarPreview',
])

@section('settings-sidebar-actions')
    <div class="flex flex-col items-center gap-3 text-center">
        <button type="button" id="openAvatarModal" class="inline-flex items-center gap-1.5 rounded-full border border-white/20 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/10 hover:text-white">
            <x-ui.icon name="image-plus" class="h-4 w-4 shrink-0" />
            Cambiar foto
        </button>
        <input type="file" id="avatarInput" accept="image/*" class="sr-only" tabindex="-1" autocomplete="off">
    </div>
@endsection

@section('settings-content')
    <form method="post" action="{{ route('settings.profile.update') }}" class="space-y-6">
        @csrf
        @method('patch')

        <input type="hidden" name="profile_edit_form" value="1">
        <input type="hidden" name="profile_photo_base64" id="profile_photo_base64" value="">
        <x-input-error class="mt-2" :messages="$errors->get('profile_photo_base64')" />

        <div class="bg-white/5 backdrop-blur rounded-2xl p-6 space-y-5 border border-white/10 shadow-lg shadow-black/20">
            <h2 class="text-white text-lg font-semibold">Información personal</h2>

            <div>
                <label for="username" class="block text-sm font-medium text-gray-300 mb-1">Nombre de usuario</label>
                <input id="username" name="username" type="text" class="input font-mono" value="{{ old('username', $user->username) }}" required autocomplete="nickname" minlength="3" maxlength="30" pattern="[a-z0-9_-]+" title="Solo minúsculas, números, guiones y guiones bajos" data-check-url="{{ route('username.availability') }}" data-original="{{ $user->username }}">
                <x-input-error class="mt-2" :messages="$errors->get('username')" />
                <p id="username-availability" class="mt-1 text-xs min-h-[1.25rem]" role="status" aria-live="polite"></p>
                <p class="mt-0.5 text-xs text-gray-500">
                    URL pública: <span class="text-gray-400">{{ url('/profile/') }}</span><span id="username-url-preview" class="text-green-400/90">{{ old('username', $user->username) }}</span>
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-300 mb-1">Nombre</label>
                    <input id="first_name" name="first_name" type="text" class="input" value="{{ old('first_name', $user->first_name) }}" required autocomplete="given-name">
                    <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-300 mb-1">Apellido</label>
                    <input id="last_name" name="last_name" type="text" class="input" value="{{ old('last_name', $user->last_name) }}" required autocomplete="family-name">
                    <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
                </div>
            </div>

            <div>
                <label for="email_display" class="block text-sm font-medium text-gray-300 mb-1">Correo electrónico</label>
                <input id="email_display" type="email" class="input opacity-70 cursor-not-allowed" value="{{ old('email', $user->email) }}" disabled autocomplete="username">
                <input type="hidden" name="email" value="{{ old('email', $user->email) }}">
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>

            <div>
                <label for="birthdate" class="block text-sm font-medium text-gray-300 mb-1">Fecha de nacimiento</label>
                <input id="birthdate" type="date" name="birthdate" value="{{ old('birthdate', $user->birthdate?->format('Y-m-d')) }}" max="{{ now()->subDay()->format('Y-m-d') }}" class="input" autocomplete="bday">
                <x-input-error class="mt-2" :messages="$errors->get('birthdate')" />
                <p class="mt-1 text-xs text-gray-500">Opcional. Sirve para mostrar tu edad en el perfil.</p>
            </div>
        </div>

        <div class="bg-white/5 backdrop-blur rounded-2xl p-6 space-y-5 border border-white/10 shadow-lg shadow-black/20">
            <h2 class="text-white text-lg font-semibold">Perfil</h2>

            <div>
                <label for="country" class="block text-sm font-medium text-gray-300 mb-1">País</label>
                <select id="country" name="country" class="input" required>
                    <option value="" disabled {{ old('country', $user->country) ? '' : 'selected' }}>Selecciona tu país</option>
                    <option value="Colombia" @selected(old('country', $user->country) === 'Colombia')>Colombia</option>
                    <option value="México" @selected(old('country', $user->country) === 'México')>México</option>
                    <option value="Argentina" @selected(old('country', $user->country) === 'Argentina')>Argentina</option>
                    <option value="Chile" @selected(old('country', $user->country) === 'Chile')>Chile</option>
                    <option value="Perú" @selected(old('country', $user->country) === 'Perú')>Perú</option>
                    <option value="Ecuador" @selected(old('country', $user->country) === 'Ecuador')>Ecuador</option>
                    <option value="Venezuela" @selected(old('country', $user->country) === 'Venezuela')>Venezuela</option>
                    <option value="Bolivia" @selected(old('country', $user->country) === 'Bolivia')>Bolivia</option>
                    <option value="Paraguay" @selected(old('country', $user->country) === 'Paraguay')>Paraguay</option>
                    <option value="Uruguay" @selected(old('country', $user->country) === 'Uruguay')>Uruguay</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('country')" />
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-300 mb-1">Sobre ti</label>
                <textarea id="description" name="description" rows="5" class="input h-28 resize-none" placeholder="Cuéntanos sobre ti">{{ old('description', $user->description) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('description')" />
            </div>

            <div class="border-t border-white/10 pt-5 space-y-4">
                <h3 class="text-white text-sm font-semibold">Preferencias sostenibles</h3>
                <p class="text-xs text-gray-500 -mt-2">Elige todas las que te representen (aparecen como etiquetas en tu perfil).</p>

                <div class="grid grid-cols-2 gap-3">
                    @foreach (\App\Models\User::PREFERENCE_OPTIONS as $pref)
                        <label class="flex items-center gap-2.5 cursor-pointer bg-white/5 px-3 py-2 rounded-xl hover:bg-white/10 transition border border-white/10 text-left">
                            <input
                                type="checkbox"
                                name="preferences[]"
                                value="{{ $pref }}"
                                @checked(in_array($pref, old('preferences', $user->preferences ?? []), true))
                                class="peer sr-only"
                            >
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md border-2 border-white/35 bg-slate-900/90 transition peer-focus-visible:outline-none peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-400/70 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-slate-950 peer-checked:border-emerald-400 peer-checked:bg-emerald-500 peer-checked:[&>svg]:opacity-100">
                                <svg class="h-3.5 w-3.5 text-white opacity-0 transition-opacity" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                                    <path d="M2.5 6L5 8.5L9.5 3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span class="text-sm text-gray-200">{{ $pref }}</span>
                        </label>
                    @endforeach
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('preferences')" />
            </div>

            <div class="border-t border-white/10 pt-5 space-y-4">
                <h3 class="text-white text-sm font-semibold">Redes sociales</h3>
                <p class="text-xs text-gray-500 -mt-2">Solo el usuario (sin @); en LinkedIn puedes pegar la URL del perfil o el slug.</p>

                <div class="grid md:grid-cols-1 gap-4">
                    <div>
                        <label for="linkedin" class="block text-sm font-medium text-gray-300 mb-1">
                            <x-ui.icon name="brand-linkedin" class="inline h-4 w-4 text-sky-400 mr-1 align-text-bottom" />LinkedIn
                        </label>
                        <input id="linkedin" type="text" name="linkedin" value="{{ old('linkedin', $user->linkedin) }}" placeholder="usuario-o-url-linkedin" class="input" autocomplete="off">
                        <x-input-error class="mt-2" :messages="$errors->get('linkedin')" />
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-green-400 to-blue-500 text-white font-semibold shadow-lg shadow-green-900/30 hover:opacity-95 transition">
            Guardar cambios
        </button>
    </form>
@endsection

@section('settings-footer')
    <x-avatar-cropper
        mode="profile"
        crop-source="persistent"
        crop-source-input-id="avatarInput"
        preview-id="avatarPreview"
        base64-input-id="profile_photo_base64"
        open-button-id="openAvatarModal"
        crop-image-id="cropperImage"
        modal-id="avatarCropModal"
    />
@endsection
