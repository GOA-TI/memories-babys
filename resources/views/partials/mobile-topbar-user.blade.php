@php
    $member = $familyMember ?? null;
@endphp
@if($member)
    @php
        $topbarName = trim((string) str($member->name)->before(' '));
        if ($topbarName === '') {
            $topbarName = $member->name;
        }
    @endphp
    <a href="{{ route('profile.edit') }}" class="cp-mobile-topbar__user cp-mobile-topbar__user--head samuel-mobile-topbar__user">
        @include('partials.member-avatar', ['member' => $member, 'size' => 'topbar'])
        <span class="cp-mobile-topbar__user-text">
            <span class="cp-mobile-topbar__user-name">{{ $topbarName }}</span>
            @if($member->relationshipLabel())
                <span class="cp-mobile-topbar__user-relation">
                    {{ $member->relationshipLabel() }}
                    <i class="ri-heart-3-fill samuel-mobile-topbar__relation-heart" aria-hidden="true"></i>
                </span>
            @else
                <span class="cp-mobile-topbar__user-relation cp-mobile-topbar__user-relation--muted">Completar parentesco</span>
            @endif
        </span>
    </a>
@endif
