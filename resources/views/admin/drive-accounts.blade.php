@extends('layouts.mobile')

@section('title', 'Google Drive')

@section('content')
    <div class="samuel-section-title" style="margin-top:0.85rem;">
        <span>Google Drive</span>
        <a href="{{ route('admin.home') }}" class="small text-decoration-none">Volver</a>
    </div>

    <div class="samuel-form-card">
        <p class="small text-muted" style="margin:0 0 0.85rem;">
            Pega los datos de OAuth (Google Cloud). La <strong>carpeta raíz</strong> es el ID de la carpeta en Drive donde se crearán las carpetas del álbum.
        </p>

        <form method="post" action="{{ route('admin.drive.store') }}">
            @csrf
            <label>Nombre (para ti)</label>
            <input name="label" required placeholder="Drive Samuel" value="{{ old('label') }}">

            <label>Correo del Drive (opcional)</label>
            <input name="owner_email" type="email" placeholder="familia@gmail.com" value="{{ old('owner_email') }}">

            <label>Client ID</label>
            <input name="google_client_id" required autocomplete="off" value="{{ old('google_client_id') }}">

            <label>Client secret</label>
            <input name="google_client_secret" required autocomplete="new-password" value="{{ old('google_client_secret') }}">

            <label>Refresh token</label>
            <textarea name="refresh_token" rows="2" required autocomplete="off" placeholder="1//0e...">{{ old('refresh_token') }}</textarea>

            <label>ID carpeta raíz (root_folder_id)</label>
            <input name="root_folder_id" placeholder="1AbCdEf..." value="{{ old('root_folder_id') }}">

            <input type="hidden" name="is_default" value="0">
            <label><input type="checkbox" name="is_default" value="1" checked> Usar como cuenta principal</label>

            <button class="samuel-btn" type="submit" style="width:100%;margin-top:0.35rem;">Guardar cuenta Drive</button>
        </form>
    </div>

    @foreach($accounts as $account)
        <div class="samuel-form-card">
            <strong>{{ $account->label }}</strong>
            <div class="small text-muted mb-2">
                {{ $account->owner_email ?: '—' }}
                · {{ $account->isConfigured() ? 'Conectada' : 'Incompleta' }}
                @if($account->is_default) · <span style="color:#059669;">Principal</span>@endif
            </div>

            <form method="post" action="{{ route('admin.drive.update', $account) }}">
                @csrf
                @method('PUT')
                <label>Nombre</label>
                <input name="label" required value="{{ $account->label }}">

                <label>Correo (opcional)</label>
                <input name="owner_email" type="email" value="{{ $account->owner_email }}">

                <label>Client ID</label>
                <input name="google_client_id" required value="{{ $account->google_client_id }}">

                <label>Client secret</label>
                <input name="google_client_secret" type="password" autocomplete="new-password"
                       placeholder="{{ $account->google_client_secret ? '•••••••• (dejar vacío para no cambiar)' : 'Obligatorio' }}">

                <label>Refresh token</label>
                <textarea name="refresh_token" rows="2" placeholder="{{ $account->refresh_token ? '•••••••• (dejar vacío para no cambiar)' : 'Obligatorio' }}"></textarea>

                <label>ID carpeta raíz</label>
                <input name="root_folder_id" value="{{ $account->root_folder_id }}">

                <input type="hidden" name="is_default" value="0">
                <label><input type="checkbox" name="is_default" value="1" @checked($account->is_default)> Cuenta principal</label>

                <button class="samuel-btn" type="submit" style="width:100%;margin-top:0.35rem;">Actualizar</button>
            </form>

            <form method="post" action="{{ route('admin.drive.test', $account) }}" class="mt-2">
                @csrf
                <button type="submit" class="samuel-btn samuel-btn--ghost" style="width:100%;padding:0.5rem;">
                    Probar conexión
                </button>
            </form>
        </div>
    @endforeach
@endsection
