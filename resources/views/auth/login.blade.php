<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ config('samuel.app_name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
    @php
        $loginTheme = \App\Models\SamuelProfile::current()->themeColors();
        $samuelThemeCssV = file_exists(public_path('css/samuel-theme.css')) ? filemtime(public_path('css/samuel-theme.css')) : 1;
        $samuelPwaJsV = file_exists(public_path('js/samuel-pwa-install.js')) ? filemtime(public_path('js/samuel-pwa-install.js')) : 1;
        $pwaName = config('samuel.pwa_name');
    @endphp
    <meta name="theme-color" content="{{ $loginTheme['primary'] }}">
    <meta name="samuel-pwa-name" content="{{ $pwaName }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ \Illuminate\Support\Str::limit($pwaName, 14, '…') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/pwa/apple-touch-icon.png') }}">
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
    <link href="{{ asset('css/samuel-theme.css') }}?v={{ $samuelThemeCssV }}" rel="stylesheet">
    <style>:root { --samuel-login-accent: {{ $loginTheme['primary'] }}; }</style>
</head>
<body class="samuel-login-page">
<div class="samuel-login-page__shell">
    <div class="samuel-login-page__hero" aria-hidden="true">
        <div class="samuel-login-page__hero-crop">
            <img src="{{ asset('images/samuel-login-hero.png') }}" alt="" class="samuel-login-page__photo">
        </div>
        <div class="samuel-login-page__hero-overlay"></div>
        <p class="samuel-login-page__hero-caption">Nuestros recuerdos</p>
    </div>

    <main class="samuel-login-page__panel">
        <header class="samuel-login-page__brand">
            <h1 class="samuel-login-page__title">{{ config('samuel.app_name') }}</h1>
            <p class="samuel-login-page__tagline">{{ config('samuel.tagline') }}</p>
        </header>

        @if(session('error'))
            <p class="samuel-login-page__notice samuel-login-page__notice--error" role="alert">{{ session('error') }}</p>
        @elseif(session('info'))
            <p class="samuel-login-page__notice samuel-login-page__notice--info">{{ session('info') }}</p>
        @endif

        <form method="post" action="{{ route('login.submit') }}">
            @csrf
            <div class="samuel-login-page__field">
                <label for="login">Celular o cédula</label>
                <input id="login" name="login" inputmode="numeric" autocomplete="username" required
                       value="{{ old('login') }}" placeholder="Ej. 3001234567 o tu cédula">
            </div>
            <div class="samuel-login-page__field">
                <label for="pin">Clave</label>
                <input id="pin" name="pin" type="password" inputmode="numeric" autocomplete="current-password"
                       required minlength="4" placeholder="••••">
            </div>
            <button type="submit" class="samuel-login-page__submit">Entrar</button>
        </form>

        <div class="samuel-login-page__download" data-samuel-pwa-install-wrap data-samuel-pwa-install-always>
            <button type="button" class="samuel-login-page__install" data-samuel-pwa-install>
                <i class="ri-download-2-line" aria-hidden="true"></i>
                Descargar app
            </button>
            <p class="samuel-login-page__install-hint">Añádela a tu pantalla de inicio para abrirla como {{ $pwaName }}.</p>
        </div>
    </main>
</div>

@include('partials.pwa-install-sheet')
<script src="{{ asset('js/samuel-pwa-install.js') }}?v={{ $samuelPwaJsV }}" defer></script>
</body>
</html>
