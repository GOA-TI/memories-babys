<a href="{{ route($familyMember ?? null ? 'home' : 'login') }}" class="samuel-mobile-topbar__brand">
    <span class="samuel-mobile-topbar__logo" aria-hidden="true">
        <img src="{{ asset('images/pwa/icon-192.png') }}" alt="">
    </span>
    <span class="samuel-mobile-topbar__wordmark">
        <span class="samuel-mobile-topbar__memorias">Memorias</span>
        <span class="samuel-mobile-topbar__name">{{ config('samuel.app_name') }}</span>
    </span>
</a>
