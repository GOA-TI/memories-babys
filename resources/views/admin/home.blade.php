@extends('layouts.mobile')

@section('title', 'Admin')

@section('content')
    <div class="samuel-section-title" style="margin-top:0.85rem;">
        <span><i class="ri-settings-3-line"></i> Administración</span>
    </div>
    <div class="samuel-form-card">
        <nav class="samuel-admin-menu" aria-label="Opciones de administración">
            <a class="samuel-btn samuel-btn--ghost samuel-btn--full" href="{{ route('samuel.show') }}">
                <i class="ri-bear-smile-line" aria-hidden="true"></i> {{ config('samuel.app_name') }}
            </a>
            <a class="samuel-btn samuel-btn--ghost samuel-btn--full" href="{{ route('admin.theme.edit') }}">
                <i class="ri-palette-line"></i> Tonos de la app
            </a>
            <a class="samuel-btn samuel-btn--ghost samuel-btn--full" href="{{ route('admin.members.index') }}">
                <i class="ri-team-line"></i> Usuarios
            </a>
            <p class="samuel-admin-menu__hint">
                Agregar familiares, parentesco, clave de acceso y quién es administrador.
            </p>
            <a class="samuel-btn samuel-btn--ghost samuel-btn--full" href="{{ route('admin.albums.index') }}">
                <i class="ri-folder-2-line"></i> Carpetas
            </a>
            <a class="samuel-btn samuel-btn--ghost samuel-btn--full" href="{{ route('admin.r2.index') }}">
                <i class="ri-cloud-line"></i> Cloudflare R2
            </a>
            <a class="samuel-btn samuel-btn--ghost samuel-btn--full" href="{{ route('admin.drive.index') }}">
                <i class="ri-drive-line"></i> Google Drive (opcional)
            </a>
        </nav>
    </div>
@endsection
