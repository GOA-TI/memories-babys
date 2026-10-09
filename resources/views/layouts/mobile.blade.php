<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Inicio') — {{ config('samuel.app_name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=overlays-content">
    <meta name="theme-color" content="{{ $appTheme['primary'] ?? config('samuel.theme.primary') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="samuel-pwa-name" content="{{ config('samuel.pwa_name') }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ \Illuminate\Support\Str::limit(config('samuel.pwa_name'), 14, '…') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/pwa/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/pwa/icon-192.png') }}">
    <script>
        (function () {
            var standalone = window.navigator.standalone === true
                || ['standalone', 'fullscreen', 'minimal-ui'].some(function (m) {
                    return window.matchMedia && window.matchMedia('(display-mode: ' + m + ')').matches;
                });
            if (standalone) {
                document.documentElement.classList.add('samuel-pwa-standalone');
            }
        })();
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@700&family=Josefin+Sans:wght@600&display=swap" rel="stylesheet">
    @php
        $samuelThemeCssV = file_exists(public_path('css/samuel-theme.css')) ? filemtime(public_path('css/samuel-theme.css')) : 1;
        $samuelShellJsV = file_exists(public_path('js/samuel-mobile-shell.js')) ? filemtime(public_path('js/samuel-mobile-shell.js')) : 1;
        $samuelHardReloadJsV = file_exists(public_path('js/samuel-hard-reload.js')) ? filemtime(public_path('js/samuel-hard-reload.js')) : 1;
        $samuelPwaJsV = file_exists(public_path('js/samuel-pwa-install.js')) ? filemtime(public_path('js/samuel-pwa-install.js')) : 1;
        $samuelCarouselJsV = file_exists(public_path('js/samuel-post-carousel.js')) ? filemtime(public_path('js/samuel-post-carousel.js')) : 1;
        $samuelPostMenuJsV = file_exists(public_path('js/samuel-post-menu.js')) ? filemtime(public_path('js/samuel-post-menu.js')) : 1;
        $samuelConfirmJsV = file_exists(public_path('js/samuel-confirm.js')) ? filemtime(public_path('js/samuel-confirm.js')) : 1;
        $samuelWelcomeJsV = file_exists(public_path('js/samuel-welcome-intro.js')) ? filemtime(public_path('js/samuel-welcome-intro.js')) : 1;
        $samuelWallCaptionJsV = file_exists(public_path('js/samuel-wall-caption.js')) ? filemtime(public_path('js/samuel-wall-caption.js')) : 1;
        $samuelWallInteractionsJsV = file_exists(public_path('js/samuel-wall-interactions.js')) ? filemtime(public_path('js/samuel-wall-interactions.js')) : 1;
        $samuelWallCommentsJsV = file_exists(public_path('js/samuel-wall-comments.js')) ? filemtime(public_path('js/samuel-wall-comments.js')) : 1;
        $samuelReactionsWhoJsV = file_exists(public_path('js/samuel-reactions-who.js')) ? filemtime(public_path('js/samuel-reactions-who.js')) : 1;
        $samuelMediaLoadJsV = file_exists(public_path('js/samuel-media-load.js')) ? filemtime(public_path('js/samuel-media-load.js')) : 1;
        $samuelGlobalProgressJsV = file_exists(public_path('js/samuel-global-progress.js')) ? filemtime(public_path('js/samuel-global-progress.js')) : 1;
        $samuelBackgroundUploadJsV = file_exists(public_path('js/samuel-background-upload.js')) ? filemtime(public_path('js/samuel-background-upload.js')) : 1;
        $samuelMediaLightboxJsV = file_exists(public_path('js/samuel-media-lightbox.js')) ? filemtime(public_path('js/samuel-media-lightbox.js')) : 1;
        $samuelBaseCssV = file_exists(public_path('css/samuel-mobile-base.css')) ? filemtime(public_path('css/samuel-mobile-base.css')) : 2;
    @endphp
    <link href="{{ asset('css/samuel-mobile-base.css') }}?v={{ $samuelBaseCssV }}" rel="stylesheet">
    <link href="{{ asset('css/samuel-theme.css') }}?v={{ $samuelThemeCssV }}" rel="stylesheet">
    <style>
        .cp-mobile-app.samuel-app {
            --samuel-primary: {{ $appTheme['primary'] ?? config('samuel.theme.primary') }};
            --samuel-secondary: {{ $appTheme['secondary'] ?? config('samuel.theme.secondary') }};
            --samuel-background: {{ $appTheme['background'] ?? config('samuel.theme.background', '#F3FAF0') }};
        }
    </style>
    @stack('head')
</head>
<body class="cp-mobile-app samuel-app @yield('body_class')">
<div class="cp-mobile-app__frame">
    <header class="cp-mobile-topbar cp-mobile-topbar--single samuel-mobile-topbar">
        @include('partials.mobile-topbar-decor')
        <div class="samuel-mobile-topbar__brand-col">
            @include('partials.mobile-topbar-brand')
        </div>
        @if($familyMember ?? null)
            <div class="samuel-mobile-topbar__spacer" aria-hidden="true"></div>
            <div class="samuel-mobile-topbar__actions">
                @include('partials.mobile-topbar-user')
                <span class="samuel-mobile-topbar__divider" aria-hidden="true"></span>
                <button type="button" class="cp-mobile-topbar__menu" id="cpMobileMenuOpen" aria-label="Menú" title="Menú">
                    <i class="ri-menu-3-line" aria-hidden="true"></i>
                </button>
            </div>
        @endif
    </header>
    @include('partials.global-upload-progress')
    @if(session('success'))
        <div class="samuel-flash samuel-flash--ok samuel-flash--dock">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="samuel-flash samuel-flash--err samuel-flash--dock">{{ session('error') }}</div>
    @endif

    <main class="samuel-main">
        @yield('content')
    </main>

    @if($familyMember ?? null)
        @php $navActive = $mobileNavActive ?? 'home'; @endphp
        <nav class="cp-mobile-bottom-nav cp-mobile-bottom-nav--cols-4 samuel-bottom-nav" aria-label="Principal">
            <a href="{{ route('home') }}" class="{{ $navActive === 'home' ? 'is-active' : '' }}">
                <i class="ri-home-smile-2-line" aria-hidden="true"></i><span>Inicio</span>
            </a>
            <a href="{{ route('wall') }}" class="{{ $navActive === 'wall' ? 'is-active' : '' }}">
                <i class="ri-gallery-line" aria-hidden="true"></i><span>Muro</span>
            </a>
            <a href="{{ route('memories.index') }}" class="{{ $navActive === 'memories' ? 'is-active' : '' }}" title="Mis recuerdos">
                <i class="ri-gallery-upload-line" aria-hidden="true"></i><span>Recuerdos</span>
            </a>
            <a href="{{ route('albums.index') }}" class="{{ $navActive === 'albums' ? 'is-active' : '' }}">
                <i class="ri-folder-2-line" aria-hidden="true"></i><span>Carpetas</span>
            </a>
        </nav>
    @endif
</div>

@stack('overlays')

@if($familyMember ?? null)
    @include('partials.mobile-drawer')
    @include('partials.pwa-install-sheet')
    @include('partials.samuel-confirm-dialog')
    @include('partials.samuel-media-lightbox')
    @include('partials.reactions-who-sheet')
    @if(! $familyMember->welcome_seen_at)
        @include('partials.welcome-intro-sheet')
    @endif
    <script src="{{ asset('js/samuel-global-progress.js') }}?v={{ $samuelGlobalProgressJsV }}" defer></script>
    <script src="{{ asset('js/samuel-background-upload.js') }}?v={{ $samuelBackgroundUploadJsV }}" defer></script>
    <script src="{{ asset('js/samuel-hard-reload.js') }}?v={{ $samuelHardReloadJsV }}" defer></script>
    <script src="{{ asset('js/samuel-mobile-shell.js') }}?v={{ $samuelShellJsV }}" defer></script>
    <script src="{{ asset('js/samuel-pwa-install.js') }}?v={{ $samuelPwaJsV }}" defer></script>
    <script src="{{ asset('js/samuel-media-load.js') }}?v={{ $samuelMediaLoadJsV }}" defer></script>
    <script src="{{ asset('js/samuel-media-lightbox.js') }}?v={{ $samuelMediaLightboxJsV }}" defer></script>
    <script src="{{ asset('js/samuel-post-carousel.js') }}?v={{ $samuelCarouselJsV }}" defer></script>
    <script src="{{ asset('js/samuel-post-menu.js') }}?v={{ $samuelPostMenuJsV }}" defer></script>
    <script src="{{ asset('js/samuel-confirm.js') }}?v={{ $samuelConfirmJsV }}" defer></script>
    <script src="{{ asset('js/samuel-wall-caption.js') }}?v={{ $samuelWallCaptionJsV }}" defer></script>
    <script src="{{ asset('js/samuel-wall-interactions.js') }}?v={{ $samuelWallInteractionsJsV }}" defer></script>
    <script src="{{ asset('js/samuel-wall-comments.js') }}?v={{ $samuelWallCommentsJsV }}" defer></script>
    <script src="{{ asset('js/samuel-reactions-who.js') }}?v={{ $samuelReactionsWhoJsV }}" defer></script>
    @if(! $familyMember->welcome_seen_at)
        <script src="{{ asset('js/samuel-welcome-intro.js') }}?v={{ $samuelWelcomeJsV }}" defer></script>
    @endif
@endif
@stack('scripts')
</body>
</html>
