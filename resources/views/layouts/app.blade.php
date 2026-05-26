<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'EcoShare') }}</title>
        @if (filled($metaDescription))
            <meta name="description" content="{{ $metaDescription }}">
        @endif
        @if (filled($metaDescription) || filled($ogImage) || filled($ogUrl))
            <meta property="og:title" content="{{ $title ?? config('app.name') }}">
            <meta property="og:type" content="article">
            @if (filled($metaDescription))
                <meta property="og:description" content="{{ $metaDescription }}">
            @endif
            @if (filled($ogImage))
                <meta property="og:image" content="{{ $ogImage }}">
            @endif
            <meta property="og:url" content="{{ $ogUrl ?? url()->current() }}">
            <meta name="twitter:card" content="summary_large_image">
        @endif
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

        <!-- Scripts (Vite; sin CDN externos — compatible CSP script-src 'self') -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    @php($isProfileEdit = request()->routeIs('settings.profile') || request()->routeIs('settings.account') || request()->routeIs('profile.show'))
    @php($isPostShow = request()->routeIs('posts.show'))
    <body class="font-sans antialiased {{ $isProfileEdit || $isPostShow ? 'bg-slate-950 text-slate-100' : 'bg-gray-100 text-slate-900' }}">
        <div class="flex min-h-[100dvh] flex-col {{ $isProfileEdit || $isPostShow ? 'bg-slate-950' : 'bg-gray-100' }}">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="{{ $isProfileEdit ? 'bg-white/5 border-b border-white/10 backdrop-blur' : 'bg-white shadow' }}">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="safe-nav-offset safe-bottom safe-left safe-right flex min-h-0 flex-1 flex-col {{ $isProfileEdit || $isPostShow ? 'bg-slate-950 text-slate-100' : '' }}">
                {{ $slot }}
            </main>
        </div>

    </body>
</html>
