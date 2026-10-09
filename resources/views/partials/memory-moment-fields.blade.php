@php
    $moment = $moment ?? now();
    $defaultTaken = $defaultTaken ?? (old('memory_taken') ?: 'today');
    $dateValue = old('memory_date', $moment->format('Y-m-d'));
    $timeValue = old('memory_time', $moment->format('H:i') !== '00:00' ? $moment->format('H:i') : '');
    $babyName = ($samuelProfile ?? null)?->displayName() ?? config('samuel.app_name');
@endphp
<fieldset class="samuel-memory-when">
    <legend class="samuel-upload-form__label">Fecha de la toma</legend>
    <div class="samuel-memory-when__choices">
        <label class="samuel-memory-when__chip">
            <input type="radio" name="memory_taken" value="today" @checked($defaultTaken === 'today')>
            <span>La acabo de tomar</span>
        </label>
        <label class="samuel-memory-when__chip">
            <input type="radio" name="memory_taken" value="other" @checked($defaultTaken === 'other')>
            <span>Otra fecha</span>
        </label>
    </div>
    <div class="samuel-memory-when__custom" id="memory-taken-custom" @if($defaultTaken !== 'other') hidden @endif>
        <input id="memory_date" name="memory_date" type="date" max="{{ now()->format('Y-m-d') }}" value="{{ $dateValue }}" aria-label="Día de la foto">
        <input id="memory_time" name="memory_time" type="time" value="{{ $timeValue }}" aria-label="Hora de la foto (opcional)">
    </div>
    <p class="samuel-memory-when__note" @if($defaultTaken !== 'other') hidden @endif>
        <i class="ri-calendar-check-line" aria-hidden="true"></i>
        Edad de {{ $babyName }} según la fecha de la foto.
    </p>
</fieldset>

@once
    @push('scripts')
        @php $memoryMomentJsV = file_exists(public_path('js/samuel-memory-moment.js')) ? filemtime(public_path('js/samuel-memory-moment.js')) : 1; @endphp
        <script src="{{ asset('js/samuel-memory-moment.js') }}?v={{ $memoryMomentJsV }}" defer></script>
    @endpush
@endonce
