@php
    use App\Support\FamilySession;

    $member = $member ?? null;
    $size = $size ?? 'md';
    $class = 'samuel-member-avatar samuel-member-avatar--'.$size;
    $isTopbar = $size === 'topbar';
    $photoUrl = null;
    if ($member && $member->hasAvatarPhoto()) {
        $photoUrl = $isTopbar
            ? (FamilySession::topbarAvatarUrl($member) ?? $member->avatarPhotoUrl())
            : $member->avatarPhotoUrl();
    }
@endphp
@if($member && $photoUrl)
    <img
        src="{{ $photoUrl }}"
        alt=""
        class="{{ $class }} samuel-member-avatar--photo @if($isTopbar) samuel-topbar-avatar-img @endif"
        @if($isTopbar) loading="eager" decoding="sync" fetchpriority="high" @else loading="lazy" @endif
    >
@elseif($member)
    <span class="{{ $class }} samuel-member-avatar--emoji" aria-hidden="true">{{ $member->avatar_emoji ?: '🙂' }}</span>
@else
    <span class="{{ $class }} samuel-member-avatar--emoji" aria-hidden="true">🙂</span>
@endif
