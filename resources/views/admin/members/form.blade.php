@extends('layouts.mobile', ['mobileNavActive' => 'home'])

@section('title', $isCreate ? 'Nuevo usuario' : 'Editar usuario')

@section('content')
    <div class="samuel-home">
        <div class="samuel-section-title samuel-section-title--compact" style="margin-top:0;">
            <a href="{{ route('admin.members.index') }}" class="samuel-back-link"><i class="ri-arrow-left-line"></i></a>
            <span>{{ $isCreate ? 'Nuevo usuario' : 'Editar usuario' }}</span>
        </div>

        @if($errors->any())
            <div class="samuel-flash samuel-flash--err samuel-flash--inline" role="alert">
                @foreach($errors->all() as $err)
                    <div>{{ $err }}</div>
                @endforeach
            </div>
        @endif

        <form
            class="samuel-settings-card"
            method="post"
            action="{{ $isCreate ? route('admin.members.store') : route('admin.members.update', $member) }}"
            enctype="multipart/form-data"
        >
            @csrf
            @if(! $isCreate)
                @method('PUT')
            @endif

            <div class="samuel-member-form-avatar">
                <div data-avatar-preview-root>
                    @if($isCreate)
                        <span class="samuel-member-form-avatar__preview samuel-member-avatar samuel-member-avatar--lg samuel-member-avatar--emoji" id="member-emoji-preview" aria-hidden="true">{{ old('avatar_emoji', '🙂') }}</span>
                    @else
                        @include('partials.member-avatar', ['member' => $member, 'size' => 'lg'])
                    @endif
                </div>
                <div class="samuel-member-form-avatar__fields">
                    <label class="samuel-link-action samuel-member-form-avatar__pick" for="avatar_photo">Subir foto</label>
                    <input id="avatar_photo" name="avatar_photo" type="file" accept="image/*,.heic,.heif" class="samuel-upload-form__file" data-samuel-avatar-input>
                    <p class="samuel-upload-form__hint samuel-avatar-preview__filename" data-samuel-avatar-filename hidden></p>
                    @if(! $isCreate && $member->hasAvatarPhoto())
                        <label class="samuel-upload-form__check samuel-upload-form__check--inline">
                            <input type="checkbox" name="remove_avatar_photo" value="1"> Quitar foto
                        </label>
                    @endif
                    <label class="samuel-settings-row samuel-settings-row--solo samuel-settings-row--compact">
                        <span class="samuel-settings-row__label">Emoji</span>
                        <input class="samuel-settings-row__input samuel-settings-row__input--emoji" id="avatar_emoji" name="avatar_emoji" maxlength="8"
                               value="{{ old('avatar_emoji', $member?->avatar_emoji ?? '🙂') }}" data-samuel-avatar-emoji-input>
                    </label>
                </div>
            </div>

            <div class="samuel-settings-list">
                @if($isCreate)
                    <label class="samuel-settings-row">
                        <span class="samuel-settings-row__label">Cédula</span>
                        <input class="samuel-settings-row__input" name="cedula" required inputmode="numeric" placeholder="Solo números" value="{{ old('cedula') }}">
                    </label>
                @else
                    <div class="samuel-settings-row">
                        <span class="samuel-settings-row__label">Cédula</span>
                        <span class="samuel-settings-row__value">{{ $member->cedula }}</span>
                    </div>
                @endif

                <label class="samuel-settings-row">
                    <span class="samuel-settings-row__label">Nombre</span>
                    <input class="samuel-settings-row__input" name="name" required value="{{ old('name', $member?->name) }}">
                </label>

                <label class="samuel-settings-row">
                    <span class="samuel-settings-row__label">Celular</span>
                    <input class="samuel-settings-row__input" name="phone" inputmode="numeric" placeholder="Opcional" value="{{ old('phone', $member?->phone) }}">
                </label>

                <label class="samuel-settings-row">
                    <span class="samuel-settings-row__label">PIN</span>
                    <input class="samuel-settings-row__input" name="pin" type="password" minlength="4" autocomplete="new-password"
                           @if($isCreate) required @endif
                           placeholder="{{ $isCreate ? '4+ dígitos' : 'Nueva (opcional)' }}">
                </label>
            </div>

            <p class="samuel-settings-card__subhead">Parentesco</p>
            <div class="samuel-relationship-grid samuel-relationship-grid--compact">
                <label class="samuel-relationship-chip">
                    <input type="radio" name="relationship" value="" @checked(old('relationship', $member?->relationship) === null || old('relationship', $member?->relationship) === '')>
                    <span>Sin definir</span>
                </label>
                @foreach($relationships as $key => $label)
                    <label class="samuel-relationship-chip">
                        <input type="radio" name="relationship" value="{{ $key }}" @checked(old('relationship', $member?->relationship) === $key)>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            <label class="samuel-upload-form__check">
                <input type="checkbox" name="is_admin" value="1" @checked(old('is_admin', $member?->is_admin ?? false))>
                Administrador
            </label>

            <button type="submit" class="samuel-settings-card__submit">{{ $isCreate ? 'Crear usuario' : 'Guardar' }}</button>
        </form>

        @if(! $isCreate)
            <div class="samuel-member-form__actions">
                @if($pin = $member->adminVisiblePin())
                    <div class="samuel-member-form__pin-box">
                        <span class="samuel-pin-reveal__label-inline">PIN actual</span>
                        <span class="samuel-pin-reveal__code samuel-pin-reveal__code--inline">{{ $pin }}</span>
                    </div>
                @endif
                <form method="post" action="{{ route('admin.members.reset-pin', $member) }}">
                    @csrf
                    <button type="submit" class="samuel-link-action samuel-link-action--block">Generar nueva PIN</button>
                </form>
                <form method="post" action="{{ route('admin.members.toggle', $member) }}">
                    @csrf
                    <button type="submit" class="samuel-link-action samuel-link-action--block samuel-link-action--muted">
                        {{ $member->is_active ? 'Desactivar acceso' : 'Activar acceso' }}
                    </button>
                </form>
                <form
                    method="post"
                    action="{{ route('admin.members.destroy', $member) }}"
                    data-samuel-confirm
                    data-samuel-confirm-title="¿Eliminar usuario?"
                    data-samuel-confirm-text="Se borrará {{ $member->name }} y sus recuerdos del muro. Esta acción no se puede deshacer."
                    data-samuel-confirm-ok="Sí, eliminar usuario"
                >
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="samuel-link-action samuel-link-action--block samuel-link-action--danger">
                        Eliminar usuario por completo
                    </button>
                </form>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
@php $avatarPickerJsV = file_exists(public_path('js/samuel-avatar-picker.js')) ? filemtime(public_path('js/samuel-avatar-picker.js')) : 1; @endphp
<script src="{{ asset('js/samuel-avatar-picker.js') }}?v={{ $avatarPickerJsV }}" defer></script>
<script>
(function () {
    var emoji = document.getElementById('avatar_emoji');
    var preview = document.getElementById('member-emoji-preview');
    if (emoji && preview) {
        emoji.addEventListener('input', function () {
            if (document.getElementById('avatar_photo')?.files?.length) {
                return;
            }
            preview.textContent = emoji.value || '🙂';
        });
    }
})();
</script>
@endpush
