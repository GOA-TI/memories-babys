@extends('layouts.mobile', ['mobileNavActive' => 'home'])

@section('title', $profile->displayName())

@section('content')
    <div class="samuel-home">
        <div class="samuel-section-title samuel-section-title--compact" style="margin-top:0;">
            <a href="{{ route('home') }}" class="samuel-back-link"><i class="ri-arrow-left-line"></i></a>
            <span><i class="ri-bear-smile-line" aria-hidden="true"></i> {{ config('samuel.app_name') }}</span>
        </div>

        @if($errors->any())
            <div class="samuel-flash samuel-flash--err samuel-flash--inline" role="alert">
                @foreach($errors->all() as $err)
                    <div>{{ $err }}</div>
                @endforeach
            </div>
        @endif

        @if($profile->isConfigured())
            @php $isBirthday = $profile->isBirthdayToday(); @endphp
            <section class="samuel-samuel-page samuel-samuel-page--in-home {{ $isBirthday ? 'samuel-samuel-page--birthday' : '' }}">
                @if($isBirthday)
                    <p class="samuel-samuel-page__banner"><i class="ri-cake-3-fill"></i> ¡Feliz cumple!</p>
                @endif
                <div class="samuel-samuel-page__hero">
                    <span class="samuel-samuel-page__emoji">{{ $profile->emoji() }}</span>
                    <p class="samuel-samuel-page__age">{{ $profile->currentAge() }}</p>
                    <p class="samuel-samuel-page__label">Edad hoy</p>
                </div>
                <dl class="samuel-samuel-page__facts">
                    <div>
                        <dt>Nacimiento</dt>
                        <dd>{{ $profile->formattedBirthdayLong() }}</dd>
                    </div>
                    @if($profile->formattedBirthTime())
                        <div>
                            <dt>Hora</dt>
                            <dd>{{ $profile->formattedBirthTime() }}</dd>
                        </div>
                    @endif
                    @if(! $isBirthday && ($daysUntil = $profile->daysUntilNextBirthday()) !== null)
                        <div>
                            <dt>Próximo cumple</dt>
                            <dd>
                                {{ $profile->formattedBirthdayShort() }}
                                @if($daysUntil > 0)
                                    · en {{ $daysUntil }} {{ $daysUntil === 1 ? 'día' : 'días' }}
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>
            </section>
        @else
            <section class="samuel-home-card">
                <p class="samuel-home-empty mb-0">Configura la fecha de nacimiento para ver la edad en el álbum.</p>
            </section>
        @endif

        @if($familyMember?->is_admin)
            @include('partials.admin-samuel-settings-form', ['profile' => $profile])
        @endif
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var input = document.querySelector('[data-samuel-emoji-input]');
    var preview = document.querySelector('[data-samuel-emoji-preview]');
    if (!input || !preview) return;
    input.addEventListener('input', function () {
        preview.textContent = input.value || '👶';
    });
})();
</script>
@endpush
