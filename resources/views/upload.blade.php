@extends('layouts.mobile', ['mobileNavActive' => 'memories'])

@section('body_class', 'samuel-page-upload')

@section('title', 'Subir recuerdo')

@php
    $birthIso = ($samuelProfile ?? null)?->birthAtLocal()?->toIso8601String();
@endphp

@section('content')
    <div class="samuel-section-title samuel-section-title--compact" style="margin-top:0.65rem;">
        <a href="{{ route('memories.index') }}" class="samuel-back-link"><i class="ri-arrow-left-line"></i></a>
        <span><i class="ri-camera-lens-line"></i> Nueva publicación</span>
    </div>

    @if($errors->any())
        <div class="samuel-flash samuel-flash--err samuel-flash--inline" role="alert">
            @foreach($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </div>
    @endif

    <form
        id="samuel-upload-form"
        class="samuel-form-card samuel-upload-form samuel-upload-form--compact samuel-form-card--with-submit"
        method="post"
        action="{{ route('upload.store') }}"
        enctype="multipart/form-data"
        data-upload-preview="1"
        data-preview-url="{{ route('upload.preview') }}"
        data-samuel-name="{{ ($samuelProfile ?? null)?->displayName() ?? config('samuel.app_name') }}"
        @if($birthIso) data-samuel-birth="{{ $birthIso }}" @endif
    >
        @csrf

        <div class="samuel-upload-form__field samuel-upload-form__field--media">
            <input id="media" name="media[]" class="samuel-upload-form__file" type="file"
                   accept="image/*,video/*,.heic,.heif" multiple aria-labelledby="media-pick"
                   aria-required="true">
            <label for="media" class="samuel-upload-form__pick samuel-upload-form__pick--hero" id="media-pick">
                <i class="ri-image-add-line" aria-hidden="true"></i>
                <span>Elegir fotos o videos</span>
            </label>
            <p class="samuel-upload-form__hint samuel-upload-form__hint--center" id="media-file-name">
                Hasta {{ config('samuel.upload.max_files_per_post') }} archivos · {{ config('samuel.upload.max_mb') }} MB por archivo · videos ~{{ config('samuel.upload.max_video_minutes', 3) }} min
            </p>
            <div id="samuel-upload-thumbs" class="samuel-upload-thumbs" hidden aria-live="polite"></div>
        </div>

        @include('partials.memory-moment-fields', ['moment' => now(), 'defaultTaken' => \App\Support\MemoryMomentInput::defaultTakenMode()])

        <div class="samuel-upload-form__field">
            <label class="samuel-upload-form__label" for="caption">Descripción <span class="samuel-label-optional">opcional</span></label>
            <textarea id="caption" name="caption" rows="2" maxlength="500"
                      placeholder="Qué pasó…">{{ old('caption') }}</textarea>
        </div>

        <details class="samuel-upload-more" open>
            <summary>Más opciones</summary>
            <div class="samuel-upload-more__body">
                <div class="samuel-upload-form__field">
                    <label class="samuel-upload-form__label" for="album_id">Carpeta</label>
                    <select id="album_id" name="album_id">
                        <option value="">Sin carpeta</option>
                        @foreach($albums as $album)
                            <option value="{{ $album->id }}" @selected((string) old('album_id') === (string) $album->id)>
                                {{ $album->emoji }} {{ $album->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($driveAccounts->count() > 1)
                    <div class="samuel-upload-form__field">
                        <label class="samuel-upload-form__label" for="drive_account_id">Drive</label>
                        <select id="drive_account_id" name="drive_account_id">
                            @foreach($driveAccounts as $acc)
                                <option value="{{ $acc->id }}" @selected($acc->is_default)>{{ $acc->label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <label class="samuel-upload-form__check">
                    <input type="checkbox" name="show_on_wall" value="1" @checked(old('show_on_wall', '1'))> Mostrar en el muro
                </label>
            </div>
        </details>

        <button type="button" class="samuel-upload-form__submit samuel-form-card__submit" id="samuel-upload-preview-btn">
            Ver vista previa
        </button>
    </form>

@endsection

@push('overlays')
    @include('partials.upload-preview-sheet')
@endpush

@push('scripts')
@php
    $heic2anyV = file_exists(public_path('js/vendor/heic2any.min.js')) ? filemtime(public_path('js/vendor/heic2any.min.js')) : 1;
    $uploadFilePreviewV = file_exists(public_path('js/samuel-upload-file-preview.js')) ? filemtime(public_path('js/samuel-upload-file-preview.js')) : 1;
    $uploadPreviewJsV = file_exists(public_path('js/samuel-upload-preview.js')) ? filemtime(public_path('js/samuel-upload-preview.js')) : 1;
@endphp
<script src="{{ asset('js/vendor/heic2any.min.js') }}?v={{ $heic2anyV }}" defer></script>
<script src="{{ asset('js/samuel-upload-file-preview.js') }}?v={{ $uploadFilePreviewV }}" defer></script>
<script src="{{ asset('js/samuel-upload-preview.js') }}?v={{ $uploadPreviewJsV }}" defer></script>
<script>
(function () {
    var legacy = document.getElementById('samuelUploadPublishing');
    if (legacy) {
        legacy.remove();
    }
})();
</script>
<script>
(function () {
    var input = document.getElementById('media');
    var hint = document.getElementById('media-file-name');
    if (!input || !hint) return;
    input.addEventListener('change', function () {
        var n = input.files ? input.files.length : 0;
        if (!n) {
            hint.textContent = 'Hasta {{ config('samuel.upload.max_files_per_post') }} archivos · {{ config('samuel.upload.max_mb') }} MB por archivo · videos ~{{ config('samuel.upload.max_video_minutes', 3) }} min';
            if (window.samuelUploadFilePreview) {
                window.samuelUploadFilePreview.revokeAll();
                window.samuelUploadFilePreview.clearRetainedFiles();
            }
            var thumbs = document.getElementById('samuel-upload-thumbs');
            if (thumbs) {
                thumbs.hidden = true;
                thumbs.innerHTML = '';
            }
            return;
        }
        hint.textContent = n === 1 ? 'Preparando vista previa…' : (n + ' archivos · preparando vista previa…');
        window.setTimeout(function () {
            var still = input.files ? input.files.length : 0;
            if (!still) {
                return;
            }
            hint.textContent = still === 1 ? input.files[0].name : (still + ' archivos listos para publicar');
        }, 2800);
    });
})();
</script>
@endpush
