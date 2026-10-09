@extends('layouts.mobile', ['mobileNavActive' => 'home'])

@section('title', 'Mi perfil y parentesco')

@section('content')
    <div class="samuel-home samuel-profile-page">
        <div class="samuel-section-title samuel-section-title--compact" style="margin-top:0;">
            <span><i class="ri-user-heart-line"></i> Mi perfil y parentesco</span>
        </div>

        @if(session('success'))
            <div class="samuel-flash samuel-flash--ok samuel-flash--inline" role="status">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="samuel-flash samuel-flash--err samuel-flash--inline" role="alert">
                @foreach($errors->all() as $err)
                    <div>{{ $err }}</div>
                @endforeach
            </div>
        @endif

        <form
            id="samuel-profile-form"
            class="samuel-form-card samuel-upload-form samuel-profile-form"
            method="post"
            action="{{ route('profile.update') }}"
            enctype="multipart/form-data"
        >
            @csrf
            @method('PUT')

            <div class="samuel-profile-avatar-block">
                <div class="samuel-profile-avatar-block__preview" data-avatar-preview-root>
                    @include('partials.member-avatar', ['member' => $member, 'size' => 'lg'])
                </div>
                <div class="samuel-profile-avatar-block__fields">
                    <label class="samuel-upload-form__label" for="avatar_photo">Foto (opcional)</label>
                    <input id="avatar_photo" name="avatar_photo" type="file" accept="image/*,.heic,.heif" class="samuel-upload-form__file" data-samuel-avatar-input>
                    <label for="avatar_photo" class="samuel-upload-form__pick">Elegir foto</label>
                    <p class="samuel-upload-form__hint samuel-avatar-preview__filename" data-samuel-avatar-filename hidden></p>
                    <p class="samuel-avatar-preview__note" data-samuel-avatar-preview-note hidden></p>
                    @if($member->hasAvatarPhoto())
                        <label class="samuel-upload-form__check samuel-upload-form__check--inline">
                            <input type="checkbox" name="remove_avatar_photo" value="1"> Quitar foto y usar emoji
                        </label>
                    @endif
                    <div class="samuel-profile-emoji-row">
                        <span class="samuel-profile-emoji-row__label">Emoji</span>
                        <label class="samuel-profile-emoji-pill" for="avatar_emoji">
                            <span class="samuel-profile-emoji-pill__hint">Toca para cambiar</span>
                            <input
                                id="avatar_emoji"
                                name="avatar_emoji"
                                class="samuel-profile-emoji-pill__input"
                                value="{{ old('avatar_emoji', $member->avatar_emoji ?: '🙂') }}"
                                maxlength="8"
                                inputmode="text"
                                autocomplete="off"
                                data-samuel-avatar-emoji-input
                                aria-label="Emoji si no hay foto"
                            >
                        </label>
                    </div>
                </div>
            </div>

            <div class="samuel-relationship-grid" id="samuelProfileRelationship">
                @foreach($relationships as $key => $label)
                    <label class="samuel-relationship-chip">
                        <input type="radio" name="relationship" value="{{ $key }}" @checked(old('relationship', $member->relationship) === $key) required>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            <button type="submit" class="samuel-upload-form__submit samuel-profile-form__submit" id="samuelProfileSubmit">
                Guardar perfil
            </button>
        </form>
    </div>

@endsection

@push('overlays')
    <div id="samuelProfileSaving" class="samuel-app-fixed-layer samuel-upload-publishing" hidden aria-live="polite" aria-busy="true">
        <div class="samuel-upload-publishing__card">
            <span class="samuel-upload-publishing__spinner" aria-hidden="true"></span>
            <p class="samuel-upload-publishing__title">Guardando perfil…</p>
            <p class="samuel-upload-publishing__hint">Un momentito.</p>
        </div>
    </div>
@endpush

@push('scripts')
@php
    $avatarPickerJsV = file_exists(public_path('js/samuel-avatar-picker.js')) ? filemtime(public_path('js/samuel-avatar-picker.js')) : 1;
    $profileFormJsV = file_exists(public_path('js/samuel-profile-form.js')) ? filemtime(public_path('js/samuel-profile-form.js')) : 1;
@endphp
<script src="{{ asset('js/samuel-avatar-picker.js') }}?v={{ $avatarPickerJsV }}" defer></script>
<script src="{{ asset('js/samuel-profile-form.js') }}?v={{ $profileFormJsV }}" defer></script>
@endpush
