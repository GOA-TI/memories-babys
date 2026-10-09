@extends('layouts.mobile', ['mobileNavActive' => 'memories'])

@section('body_class', 'samuel-page-upload')

@section('title', 'Editar publicación')

@section('content')
    <div class="samuel-section-title" style="margin-top:0.85rem;">
        <a href="{{ route('memories.index') }}" class="samuel-back-link"><i class="ri-arrow-left-line"></i></a>
        <span><i class="ri-edit-line"></i> Editar publicación</span>
    </div>

    <form id="samuel-upload-form" class="samuel-form-card samuel-upload-form samuel-form-card--with-submit" method="post"
          action="{{ route('posts.update', $post) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" name="redirect" value="memories">

        <div class="samuel-upload-form__field">
            <span class="samuel-upload-form__label">Fotos y videos actuales ({{ $post->mediaItems->count() }})</span>
            <div class="samuel-edit-preview mb-2">
                @include('partials.post-media-carousel', ['post' => $post, 'inlineVideo' => true, 'useFeed' => false])
            </div>
            <span class="samuel-upload-form__label">Añadir más (opcional)</span>
            <input id="media" name="media[]" class="samuel-upload-form__file" type="file"
                   accept="image/*,video/*" multiple>
            <label for="media" class="samuel-upload-form__pick">Elegir fotos o videos extra</label>
            <p class="samuel-upload-form__hint" id="media-file-name">Sin archivos nuevos</p>
        </div>

        @include('partials.memory-moment-fields', [
            'moment' => $post->memoryMoment(),
            'defaultTaken' => \App\Support\MemoryMomentInput::defaultTakenMode($post->memoryMoment()),
        ])

        <div class="samuel-upload-form__field">
            <label class="samuel-upload-form__label" for="caption">Descripción</label>
            <textarea id="caption" name="caption" rows="3" maxlength="500"
                      placeholder="Qué pasó en este momentico…">{{ old('caption', $post->caption) }}</textarea>
        </div>

        <div class="samuel-upload-form__field">
            <label class="samuel-upload-form__label" for="album_id">Carpeta (opcional)</label>
            <select id="album_id" name="album_id">
                <option value="">— Sin carpeta —</option>
                @foreach($albums as $album)
                    <option value="{{ $album->id }}" @selected((string) old('album_id', $post->album_id) === (string) $album->id)>
                        {{ $album->emoji }} {{ $album->title }}
                    </option>
                @endforeach
            </select>
        </div>

        <label class="samuel-upload-form__check">
            <input type="checkbox" name="show_on_wall" value="1" @checked(old('show_on_wall', $post->show_on_wall))> Mostrar en el muro
        </label>

        <button type="submit" class="samuel-upload-form__submit samuel-form-card__submit">
            Guardar cambios
        </button>
    </form>
@endsection

@push('head')
<script>
(function () {
    var meta = document.querySelector('meta[name="viewport"]');
    if (meta) {
        meta.setAttribute(
            'content',
            'width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content'
        );
    }
})();
</script>
@endpush

@push('scripts')
@php
    $uploadBottomNavJsV = file_exists(public_path('js/samuel-upload-bottom-nav.js')) ? filemtime(public_path('js/samuel-upload-bottom-nav.js')) : 1;
@endphp
<script src="{{ asset('js/samuel-upload-bottom-nav.js') }}?v={{ $uploadBottomNavJsV }}" defer></script>
<script>
(function () {
    var input = document.getElementById('media');
    var hint = document.getElementById('media-file-name');
    if (!input || !hint) return;
    input.addEventListener('change', function () {
        var n = input.files ? input.files.length : 0;
        hint.textContent = n ? (n + (n === 1 ? ' archivo nuevo' : ' archivos nuevos')) : 'Sin archivos nuevos';
    });
})();
</script>
@endpush
