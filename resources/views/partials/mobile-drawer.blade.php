@php
    $navActive = $mobileNavActive ?? 'home';
    $appName = config('samuel.app_name');
    $pwaName = config('samuel.pwa_name');
@endphp
<div class="cp-mobile-drawer-backdrop" id="cpMobileDrawerBackdrop"></div>
<aside class="cp-mobile-drawer" id="cpMobileDrawer" aria-hidden="true" aria-label="Menú">
    <div class="cp-mobile-drawer__head">
        @if($familyMember ?? null)
            <a href="{{ route('profile.edit') }}" class="cp-mobile-drawer__user">
                @include('partials.member-avatar', ['member' => $familyMember, 'size' => 'sm'])
                <span class="cp-mobile-drawer__user-text">
                    <span class="cp-mobile-drawer__user-name">{{ $familyMember->name }}</span>
                    @if($familyMember->relationshipLabel())
                        <span class="cp-mobile-drawer__user-relation">{{ $familyMember->relationshipLabel() }}</span>
                    @endif
                </span>
            </a>
        @endif
        <span class="cp-mobile-drawer__app-name">{{ $pwaName }}</span>
        <button type="button" class="cp-mobile-drawer__close" id="cpMobileMenuClose" aria-label="Cerrar menú">
            <i class="ri-close-line"></i>
        </button>
    </div>

    <nav class="cp-mobile-drawer__nav">
        <a href="{{ route('home') }}" class="{{ $navActive === 'home' ? 'is-active' : '' }}">
            <i class="ri-home-smile-2-line"></i> Inicio
        </a>
        <a href="{{ route('wall') }}" class="{{ $navActive === 'wall' ? 'is-active' : '' }}">
            <i class="ri-gallery-line"></i> Muro
        </a>
        <a href="{{ route('memories.index') }}" class="{{ $navActive === 'memories' ? 'is-active' : '' }}">
            <i class="ri-gallery-upload-line"></i> Mis recuerdos
        </a>
        <a href="{{ route('upload.create') }}">
            <i class="ri-camera-lens-line"></i> Subir recuerdo
        </a>
        <a href="{{ route('albums.index') }}" class="{{ $navActive === 'albums' ? 'is-active' : '' }}">
            <i class="ri-folder-2-line"></i> Carpetas
        </a>
        <a href="{{ route('profile.edit') }}">
            <i class="ri-user-heart-line"></i> Mi perfil y parentesco
        </a>
        <a href="{{ route('samuel.show') }}" class="{{ request()->routeIs('samuel.show') ? 'is-active' : '' }}">
            <i class="ri-bear-smile-line" aria-hidden="true"></i> {{ $appName }}
        </a>
        @if($familyMember?->is_admin)
            <a href="{{ route('admin.home') }}">
                <i class="ri-settings-3-line"></i> Administración
            </a>
            <a href="{{ route('admin.members.index') }}">
                <i class="ri-team-line"></i> Usuarios
            </a>
        @endif
    </nav>

    <div class="cp-mobile-drawer__footer">
        <button type="button" class="cp-mobile-drawer__install" data-samuel-pwa-install-wrap data-samuel-pwa-install hidden>
            <i class="ri-download-2-line" aria-hidden="true"></i>
            <span>Instalar {{ $pwaName }}</span>
        </button>
        <button type="button" class="cp-mobile-drawer__reload" id="samuelDrawerReload">
            <i class="ri-refresh-line" aria-hidden="true"></i>
            <span>Recargar</span>
        </button>
        <form method="post" action="{{ route('logout') }}" class="cp-mobile-drawer__logout-form">
            @csrf
            <button type="submit" class="cp-mobile-drawer__logout">Cerrar sesión</button>
        </form>
    </div>
</aside>
