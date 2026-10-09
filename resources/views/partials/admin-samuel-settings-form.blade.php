@php
    $profile = $profile ?? \App\Models\SamuelProfile::current();
    $birth = $profile->birthAtLocal();
    $birthDate = old('birth_date', $birth?->format('Y-m-d'));
    $birthTime = old('birth_time', $profile->hasBirthTime() ? $birth?->format('H:i') : '');
@endphp
<section class="samuel-settings-card" id="samuel-config">
    <h2 class="samuel-settings-card__heading">Datos de {{ config('samuel.app_name') }}</h2>

    <form method="post" action="{{ route('admin.samuel.update') }}">
        @csrf
        @method('PUT')

        <div class="samuel-settings-card__intro">
            <span class="samuel-settings-card__intro-emoji" data-samuel-emoji-preview>{{ old('emoji', $profile->emoji()) }}</span>
            <p>Edad en el inicio y en cada recuerdo.</p>
        </div>

        <div class="samuel-settings-list">
            <label class="samuel-settings-row">
                <span class="samuel-settings-row__label">Emoji</span>
                <input class="samuel-settings-row__input samuel-settings-row__input--emoji" name="emoji" maxlength="8"
                       value="{{ old('emoji', $profile->emoji()) }}" data-samuel-emoji-input>
            </label>
            <label class="samuel-settings-row">
                <span class="samuel-settings-row__label">Nombre</span>
                <input class="samuel-settings-row__input" name="display_name" maxlength="80"
                       placeholder="{{ config('samuel.app_name') }}"
                       value="{{ old('display_name', $profile->display_name) }}">
            </label>
            <label class="samuel-settings-row">
                <span class="samuel-settings-row__label">Nació</span>
                <input class="samuel-settings-row__input samuel-settings-row__input--date" name="birth_date"
                       type="date" required max="{{ now()->format('Y-m-d') }}" value="{{ $birthDate }}">
            </label>
        </div>

        <details class="samuel-settings-more">
            <summary>Hora exacta <span class="samuel-label-optional">opcional</span></summary>
            <label class="samuel-settings-row samuel-settings-row--solo">
                <input class="samuel-settings-row__input" name="birth_time" type="time" value="{{ $birthTime }}">
            </label>
        </details>

        <button type="submit" class="samuel-settings-card__submit">Guardar</button>
    </form>
</section>
