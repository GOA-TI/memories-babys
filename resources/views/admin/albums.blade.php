@extends('layouts.mobile')

@section('title', 'Carpetas admin')

@section('content')
    <div class="samuel-section-title" style="margin-top:0.85rem;">
        <span>Carpetas</span>
        <a href="{{ route('admin.home') }}" class="small text-decoration-none">Volver</a>
    </div>

    <form class="samuel-form-card" method="post" action="{{ route('admin.albums.store') }}">
        @csrf
        <label>Título</label>
        <input name="title" required placeholder="Primeros pasos">

        <label>Emoji</label>
        <input name="emoji" value="📁" maxlength="8">

        <label>Color (#hex)</label>
        <input name="color" value="#fbcfe8">

        <label>Descripción</label>
        <input name="description" placeholder="Opcional">

        @if($driveAccounts->isNotEmpty())
            <label>Cuenta Google Drive</label>
            <select name="drive_account_id">
                @foreach($driveAccounts as $acc)
                    <option value="{{ $acc->id }}" @selected($acc->is_default)>
                        {{ $acc->label }}@unless($acc->isConfigured()) (sin conectar)@endunless
                    </option>
                @endforeach
            </select>
            <p class="small text-muted" style="margin:-0.5rem 0 0.85rem;">Al guardar, se crea la misma carpeta dentro de tu Drive.</p>
        @else
            <p class="small text-muted">Sin cuentas Drive. Ve a <a href="{{ route('admin.drive.index') }}">Admin → Google Drive</a> y agrega client_id, secret, refresh token y carpeta raíz.</p>
        @endif

        <button class="samuel-btn" type="submit" style="width:100%;">Crear carpeta</button>
    </form>

    @foreach($albums as $album)
        <div class="samuel-form-card" style="padding:0.75rem 1rem;">
            <strong>{{ $album->emoji }} {{ $album->title }}</strong>
            <div class="small text-muted">
                /{{ $album->slug }}
                @if($album->drive_folder_id)
                    · <span style="color:#059669;">Drive ✓</span>
                @else
                    · sin Drive
                @endif
            </div>
            @if(! $album->drive_folder_id && $driveAccounts->contains(fn ($a) => $a->isConfigured()))
                <form method="post" action="{{ route('admin.albums.sync-drive', $album) }}" class="mt-2">
                    @csrf
                    <button type="submit" class="samuel-btn samuel-btn--ghost" style="padding:0.4rem 0.7rem;font-size:0.82rem;">
                        Crear en Drive ahora
                    </button>
                </form>
            @endif
        </div>
    @endforeach
@endsection
