@php
    $moment = $moment ?? null;
    $profile = $samuelProfile ?? null;
    $age = ($profile && $profile->isConfigured() && $moment)
        ? $profile->ageAt($moment)
        : null;
@endphp
@if($age)
    <span class="samuel-age-at">{{ $profile->displayName() }} tenía {{ $age }}</span>
@endif
