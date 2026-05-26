@props([
    'user',
    'avatarId' => null,
    'showPublicLink' => true,
    'showCountry' => true,
    'showAge' => true,
    'showMemberSince' => true,
    'showPreferences' => true,
    'showSocialLinks' => true,
])

@php
    $preferenceIcons = [
        'Reciclaje y residuos'         => 'recycle',
        'Consumo responsable'          => 'shopping-bag',
        'Energía renovable'            => 'bolt',
        'Moda sostenible'              => 'shirt',
        'Alimentación ecológica'       => 'leaf',
        'Movilidad sostenible'         => 'bike',
        'Economía circular'            => 'arrows-right-left',
        'Activismo ambiental'          => 'globe',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col items-center text-center space-y-3']) }}>
    <img
        @if ($avatarId) id="{{ $avatarId }}" @endif
        src="{{ $user->profile_photo_url }}"
        alt=""
        class="w-28 h-28 rounded-full border-2 border-green-400 object-cover shrink-0"
    >

    <div class="space-y-1">
        <h2 class="text-white font-semibold text-lg leading-tight">
            {{ $user->first_name }} {{ $user->last_name }}
        </h2>
        <p class="text-gray-400 text-sm">{{ '@'.$user->username }}</p>

        @if ($showPublicLink)
            <a
                href="{{ route('profile.show', $user->username) }}"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 text-xs text-green-400 hover:text-green-300 hover:underline"
            >
                <x-ui.icon name="external-link" class="w-3.5 h-3.5 shrink-0" />
                Ver perfil público
            </a>
        @endif
    </div>

    @if ($showCountry)
        <div class="flex items-center justify-center gap-2 text-gray-400 text-sm">
            <x-ui.icon name="map-pin" class="w-4 h-4 text-green-400 shrink-0" />
            <span>{{ $user->country ?? '—' }}</span>
        </div>
    @endif

    @if ($showAge || $showMemberSince)
        <div class="mt-3 w-full space-y-3 text-sm text-gray-400 border-t border-white/10 pt-4">
            @if ($showAge && $user->birthdate)
                <div class="flex items-center justify-center gap-2">
                    <x-ui.icon name="cake" class="w-4 h-4 text-green-400 shrink-0" />
                    <span class="text-gray-300">{{ $user->birthdate->age }} años</span>
                </div>
            @endif

            @if ($showMemberSince)
                <div class="flex items-center justify-center gap-2 text-center">
                    <x-ui.icon name="calendar" class="w-4 h-4 text-green-400 shrink-0" />
                    <span>
                        Miembro desde
                        <span class="text-gray-300 font-medium">
                            {{ $user->created_at->locale(app()->getLocale())->translatedFormat('F Y') }}
                        </span>
                    </span>
                </div>
            @endif
        </div>
    @endif

    @if ($showPreferences && $user->preferences && count($user->preferences))
        <div class="mt-3 flex flex-wrap gap-2 justify-center w-full">
            @foreach ($user->preferences as $pref)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs rounded-full bg-green-400/20 text-green-300 border border-green-400/30">
                    <x-ui.icon :name="$preferenceIcons[$pref] ?? 'star'" class="w-3.5 h-3.5 text-green-400 shrink-0" />
                    <span>{{ $pref }}</span>
                </span>
            @endforeach
        </div>
    @endif

    @if ($showSocialLinks)
        <div class="mt-3 flex flex-wrap gap-4 justify-center items-center w-full border-t border-white/10 pt-4">


            @if ($user->linkedin)
                <a
                    href="https://www.linkedin.com/in/{{ $user->linkedin }}/"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex items-center gap-2 text-sky-400 hover:scale-105 transition"
                >
                    <x-ui.icon name="brand-linkedin" class="h-5 w-5 shrink-0" />
                    <span class="text-sm">{{ $user->linkedin }}</span>
                </a>
            @endif
        </div>
    @endif
</div>
