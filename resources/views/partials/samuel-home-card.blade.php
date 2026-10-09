@php
    $profile = $samuelProfile ?? null;
@endphp
@if($profile && $profile->isConfigured())
    @php
        $isBirthday = $profile->isBirthdayToday();
        $daysUntil = $profile->daysUntilNextBirthday();
        $currentAge = $profile->currentAge();
    @endphp
    <section class="samuel-home-card samuel-home-card--samuel {{ $isBirthday ? 'samuel-home-card--birthday' : '' }}">
        @if($isBirthday)
            <p class="samuel-samuel-card__celebrate"><i class="ri-cake-3-fill"></i> ¡Hoy es su cumple!</p>
        @endif
        <div class="samuel-samuel-card">
            <div class="samuel-samuel-card__emoji" aria-hidden="true">{{ $profile->emoji() }}</div>
            <div class="samuel-samuel-card__body">
                <h2 class="samuel-samuel-card__name">{{ $profile->displayName() }}</h2>
                @if($currentAge)
                    <p class="samuel-samuel-card__age">{{ $currentAge }}</p>
                @endif
                <p class="samuel-samuel-card__birth">
                    Nació el {{ $profile->formattedBirthdayLong() }}
                    @if($profile->formattedBirthTime())
                        · {{ $profile->formattedBirthTime() }}
                    @endif
                </p>
                @if(! $isBirthday && $daysUntil !== null && $daysUntil <= 14)
                    <p class="samuel-samuel-card__soon">
                        @if($daysUntil === 0)
                            Cumpleaños hoy
                        @elseif($daysUntil === 1)
                            Mañana cumple {{ $profile->ageTurningOnNextBirthday() }}
                        @else
                            Cumple el {{ $profile->formattedBirthdayShort() }} (en {{ $daysUntil }} días)
                        @endif
                    </p>
                @endif
            </div>
            <a href="{{ route('samuel.show') }}" class="samuel-samuel-card__link" aria-label="Ver datos de {{ $profile->displayName() }}">
                <i class="ri-arrow-right-s-line"></i>
            </a>
        </div>
    </section>
@elseif($familyMember?->is_admin)
    <section class="samuel-home-card samuel-home-card--hint">
        <p class="samuel-home-empty mb-2">Configura la fecha de nacimiento para ver la edad en el inicio y en cada recuerdo.</p>
        <a href="{{ route('admin.samuel.edit') }}" class="samuel-home-cta">Datos de {{ config('samuel.app_name') }}</a>
    </section>
@endif
