@extends('layouts.mobile', ['mobileNavActive' => 'home'])

@section('title', 'Datos de Samuel')

@section('content')
    <div class="samuel-home">
        <div class="samuel-section-title samuel-section-title--compact" style="margin-top:0;">
            <a href="{{ route('samuel.show') }}" class="samuel-back-link"><i class="ri-arrow-left-line"></i></a>
            <span><i class="ri-bear-smile-line" aria-hidden="true"></i> Datos de {{ config('samuel.app_name') }}</span>
        </div>

        @if($errors->any())
            <div class="samuel-flash samuel-flash--err samuel-flash--inline" role="alert">
                @foreach($errors->all() as $err)
                    <div>{{ $err }}</div>
                @endforeach
            </div>
        @endif

        <form class="samuel-settings-card" method="post" action="{{ route('admin.samuel.update') }}">
            @csrf
            @method('PUT')

            <div class="samuel-settings-card__intro">
                <span class="samuel-settings-card__intro-emoji">{{ old('emoji', $profile->emoji()) }}</span>
                <p>Edad en el inicio y en cada recuerdo del álbum.</p>
            </div>

            <div class="samuel-settings-list">
                <label class="samuel-settings-row">
                    <span class="samuel-settings-row__label">Emoji</span>
                    <input class="samuel-settings-row__input samuel-settings-row__input--emoji" id="emoji" name="emoji" maxlength="8"
                           value="{{ old('emoji', $profile->emoji()) }}" aria-label="Emoji">
                </label>

                <label class="samuel-settings-row">
                    <span class="samuel-settings-row__label">Nombre</span>
                    <input class="samuel-settings-row__input" id="display_name" name="display_name" maxlength="80"
                           placeholder="{{ config('samuel.app_name') }}"
                           value="{{ old('display_name', $profile->display_name) }}">
                </label>

                <label class="samuel-settings-row">
                    <span class="samuel-settings-row__label">Nació</span>
                    <input class="samuel-settings-row__input samuel-settings-row__input--date" id="birth_date" name="birth_date"
                           type="date" required max="{{ now()->format('Y-m-d') }}" value="{{ $birthDate }}">
                </label>
            </div>

            <details class="samuel-settings-more">
                <summary>Hora de nacimiento <span class="samuel-label-optional">opcional</span></summary>
                <label class="samuel-settings-row samuel-settings-row--solo">
                    <input class="samuel-settings-row__input" id="birth_time" name="birth_time" type="time" value="{{ $birthTime }}">
                </label>
            </details>

            <button type="submit" class="samuel-settings-card__submit">Guardar</button>
        </form>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var emojiInput = document.getElementById('emoji');
    var preview = document.querySelector('.samuel-settings-card__intro-emoji');
    if (!emojiInput || !preview) return;
    emojiInput.addEventListener('input', function () {
        preview.textContent = emojiInput.value || '👶';
    });
})();
</script>
@endpush
