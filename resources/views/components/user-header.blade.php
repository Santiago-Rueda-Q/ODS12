@props([
    'user',
    'viewerFollows' => false,
    'postsCount' => 0,
    'likesReceived' => 0,
    'followersCount' => 0,
    'followingCount' => 0,
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

@php
    $profileUrl = route('profile.show', ['username' => $user->username]);
    $qrUrl = 'https://quickchart.io/qr?size=360&text='.urlencode($profileUrl);
@endphp

<div {{ $attributes->merge(['class' => 'relative flex flex-col items-center text-center space-y-3']) }}>
    <div class="absolute right-0 top-0 z-10 flex items-center gap-2">
        @auth
            @if (auth()->id() === $user->id)
                <a
                    href="{{ route('settings.profile') }}"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/15 bg-white/5 text-slate-200 transition hover:bg-white/10 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
                    aria-label="Editar perfil"
                >
                    <x-ui.icon name="pencil" class="h-4 w-4" />
                </a>
            @endif
        @endauth

        <button
            type="button"
            id="profile-share-open"
            class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/15 bg-white/5 text-slate-200 transition hover:bg-white/10 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
            aria-label="Compartir perfil"
        >
            <x-ui.icon name="send" class="h-4 w-4" />
        </button>
    </div>

    <x-user-profile-card
        :user="$user"
        :show-country="false"
        class="w-full"
    />

    <div class="space-y-1 w-full">

        <x-user-stats
            class="mt-2"
            :posts-count="$postsCount"
            :likes-received="$likesReceived"
            :followers-count="$followersCount"
            :following-count="$followingCount"
        />

        @auth
            @if (auth()->id() !== $user->id)
                <button
                    type="button"
                    id="profile-follow-btn"
                    data-following="{{ $viewerFollows ? '1' : '0' }}"
                    data-follow-store-url="{{ route('users.follow.store', $user->username) }}"
                    data-follow-destroy-url="{{ route('users.follow.destroy', $user->username) }}"
                    class="mt-4 w-full rounded-full px-4 py-2.5 text-sm font-semibold transition border {{ $viewerFollows ? 'border-white/30 bg-white/10 text-white hover:bg-white/15' : 'border-green-400 bg-green-500 text-white hover:bg-green-400' }}"
                >
                    {{ $viewerFollows ? 'Dejar de seguir' : 'Seguir' }}
                </button>
            @endif
        @else
            <a
                href="{{ route('login', [], false) }}"
                class="mt-4 inline-flex w-full justify-center rounded-full border border-green-400 bg-green-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-400 transition"
            >
                Inicia sesión para seguir
            </a>
        @endauth

        <div class="flex items-center justify-center gap-2 text-gray-400 text-sm mt-4 pt-4 border-t border-white/10">
            <x-ui.icon name="map-pin" class="w-4 h-4 text-green-400 shrink-0" />
            <span>{{ $user->country ?? '—' }}</span>
        </div>
    </div>

    <div class="mt-3 w-full space-y-3 text-sm text-gray-400 border-t border-white/10 pt-4">
        @if ($user->birthdate)
            <div class="flex items-center justify-center gap-2">
                <x-ui.icon name="cake" class="w-4 h-4 text-green-400 shrink-0" />
                <span class="text-gray-300">{{ $user->birthdate->age }} años</span>
            </div>
        @endif

        <div class="flex items-center justify-center gap-2 text-center">
            <x-ui.icon name="calendar" class="w-4 h-4 text-green-400 shrink-0" />
            <span>
                Miembro desde
                <span class="text-gray-300 font-medium">
                    {{ $user->created_at->locale(app()->getLocale())->translatedFormat('F Y') }}
                </span>
            </span>
        </div>
    </div>

    @if ($user->preferences && count($user->preferences))
        <div class="mt-3 flex flex-wrap gap-2 justify-center w-full">
            @foreach ($user->preferences as $pref)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs rounded-full bg-green-400/20 text-green-300 border border-green-400/30">
                    <x-ui.icon :name="$preferenceIcons[$pref] ?? 'star'" class="w-3.5 h-3.5 text-green-400 shrink-0" />
                    <span>{{ $pref }}</span>
                </span>
            @endforeach
        </div>
    @endif

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
</div>

<div id="profile-share-modal" class="profile-share-modal fixed inset-0 z-50 hidden items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="profile-share-title">
    <button type="button" id="profile-share-backdrop" class="absolute inset-0 bg-black/75 backdrop-blur-md transition-opacity duration-300" aria-label="Cerrar compartir"></button>

    <div id="profile-share-panel" class="profile-share-panel relative w-full max-w-md overflow-hidden rounded-3xl border border-white/15 bg-slate-950/85 p-5 text-slate-100 shadow-[0_30px_70px_-20px_rgba(0,0,0,0.8)] ring-1 ring-white/10 sm:p-6">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(16,185,129,0.18),_transparent_55%)]"></div>

        <div class="relative">
            <div class="mb-5 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h3 id="profile-share-title" class="text-lg font-semibold tracking-tight text-white">Compartir perfil</h3>
                    <p class="mt-1 truncate text-sm text-slate-300">{{ '@'.$user->username }}</p>
                </div>

                <button type="button" id="profile-share-close" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/15 bg-white/5 text-slate-300 transition hover:-translate-y-0.5 hover:bg-white/10 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60" aria-label="Cerrar">
                    <x-ui.icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <div class="mx-auto mb-4 w-fit rounded-[1.25rem] bg-gradient-to-r from-emerald-300 via-cyan-400 to-emerald-300 p-[2px] shadow-[0_0_30px_rgba(45,212,191,0.22)] profile-share-qr-gradient">
                <div class="rounded-[1.1rem] bg-white/95 p-4 shadow-inner ring-1 ring-black/5">
                    <img id="profile-share-qr" src="{{ $qrUrl }}" alt="QR del perfil" class="profile-share-qr h-56 w-56 rounded-xl object-contain" loading="lazy" />
                </div>
            </div>

            <div class="mb-4 rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-2.5">
                <p class="mb-1 text-[11px] font-medium uppercase tracking-wide text-slate-400">Enlace del perfil</p>
                <p class="truncate text-xs text-slate-200" id="profile-share-url">{{ $profileUrl }}</p>
            </div>

            <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                <button type="button" id="profile-share-native" class="inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-emerald-500 to-cyan-500 px-4 py-2.5 text-sm font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:brightness-110 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300/70">
                    <x-ui.icon name="send" class="h-4 w-4 shrink-0" />
                    <span data-profile-share-native-label>Compartir</span>
                </button>

                <button type="button" id="profile-share-copy" class="inline-flex items-center justify-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-medium text-slate-100 transition duration-200 hover:-translate-y-0.5 hover:bg-white/15 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300/60">
                    <x-ui.icon name="copy" class="h-4 w-4 shrink-0" />
                    <span data-profile-share-copy-label>Copiar enlace</span>
                </button>

                <a id="profile-share-download" href="{{ $qrUrl }}" download="perfil-{{ $user->username }}-qr.png" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-full border border-emerald-400/45 bg-emerald-500/15 px-4 py-2.5 text-sm font-medium text-emerald-200 transition duration-200 hover:-translate-y-0.5 hover:bg-emerald-500/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300/60">
                    <x-ui.icon name="download" class="h-4 w-4 shrink-0" />
                    Descargar
                </a>
            </div>
        </div>
    </div>
</div>

