@extends('layouts.mobile', ['mobileNavActive' => 'home'])

@section('title', 'Tonos de la app')

@section('content')
    @php $colors = $profile->themeColors(); @endphp
    <div class="samuel-home">
        <div class="samuel-section-title samuel-section-title--compact" style="margin-top:0;">
            <a href="{{ route('admin.home') }}" class="samuel-back-link"><i class="ri-arrow-left-line"></i></a>
            <span><i class="ri-palette-line"></i> Tonos de la app</span>
        </div>

        <div class="samuel-theme-preview" style="--preview-primary: {{ $colors['primary'] }}; --preview-secondary: {{ $colors['secondary'] }}; --preview-bg: {{ $colors['background'] }};">
            <span class="samuel-theme-preview__chip">Vista previa</span>
        </div>

        <section class="samuel-settings-card">
            <p class="samuel-settings-card__intro-text">Estilos listos para toda la familia.</p>

            <form method="post" action="{{ route('admin.theme.update') }}" class="samuel-theme-presets">
                @csrf
                @method('PUT')
                @foreach($presets as $key => $preset)
                    <button type="submit" name="preset" value="{{ $key }}" class="samuel-theme-preset">
                        <span class="samuel-theme-preset__dots" aria-hidden="true">
                            <i style="background:{{ $preset['primary'] }}"></i>
                            <i style="background:{{ $preset['secondary'] }}"></i>
                            <i style="background:{{ $preset['background'] }}"></i>
                        </span>
                        {{ $preset['label'] }}
                    </button>
                @endforeach
            </form>

            <form method="post" action="{{ route('admin.theme.update') }}" class="samuel-theme-custom">
                @csrf
                @method('PUT')

                <p class="samuel-settings-card__subhead">Personalizar</p>
                @php
                    $themeFields = [
                        ['name' => 'theme_primary', 'label' => 'Principal', 'key' => 'primary'],
                        ['name' => 'theme_secondary', 'label' => 'Secundario', 'key' => 'secondary'],
                        ['name' => 'theme_background', 'label' => 'Fondo', 'key' => 'background'],
                    ];
                @endphp
                <div class="samuel-settings-list">
                    @foreach($themeFields as $field)
                        @php $val = old($field['name'], $colors[$field['key']]); @endphp
                        <div class="samuel-settings-row samuel-settings-row--color">
                            <span class="samuel-settings-row__label">{{ $field['label'] }}</span>
                            <input type="color" value="{{ $val }}" aria-label="{{ $field['label'] }}">
                            <input class="samuel-settings-row__input samuel-settings-row__input--hex" name="{{ $field['name'] }}" value="{{ $val }}" maxlength="7">
                        </div>
                    @endforeach
                </div>

                <button type="submit" class="samuel-settings-card__submit">Guardar colores</button>
            </form>
        </section>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    document.querySelectorAll('.samuel-settings-row--color').forEach(function (row) {
        var color = row.querySelector('input[type="color"]');
        var hex = row.querySelector('.samuel-settings-row__input--hex');
        if (!color || !hex) return;
        color.addEventListener('input', function () { hex.value = color.value; });
        hex.addEventListener('input', function () {
            if (/^#[0-9A-Fa-f]{6}$/.test(hex.value)) color.value = hex.value;
        });
    });
})();
</script>
@endpush
