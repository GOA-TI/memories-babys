@php
    $member = $post->member;
    $profileUrl = route('members.show', $member);
@endphp
<a href="{{ $profileUrl }}" class="samuel-wall-card__author">
    @include('partials.member-avatar', ['member' => $member, 'size' => 'sm'])
    <div class="samuel-wall-card__meta">
        <div class="samuel-wall-card__line1">
            <strong class="samuel-wall-card__name">{{ $member->name }}</strong>
            @if($member->relationshipLabel())
                <span class="samuel-wall-card__dot" aria-hidden="true">·</span>
                <span class="samuel-wall-card__relation">{{ $member->relationshipLabel() }}</span>
            @endif
        </div>
        @php
            $publishedAt = $post->created_at;
            $shotAt = $post->memoryMoment();
            $sameShotDay = $publishedAt->toDateString() === $shotAt->toDateString();
        @endphp
        <div class="samuel-wall-card__time">
            Publicado {{ $publishedAt->locale('es')->isoFormat('D MMM YYYY') }}
            @if(! $sameShotDay)
                · Foto del {{ $shotAt->locale('es')->isoFormat('D MMM YYYY') }}
            @endif
            @if($post->album)
                · {{ $post->album->emoji }} {{ $post->album->title }}
            @endif
            @if($post->mediaItems->count() > 1)
                · {{ $post->mediaItems->count() }} archivos
            @endif
        </div>
        <div class="samuel-wall-card__age-line">
            @include('partials.samuel-age-at', ['moment' => $post->memoryMoment()])
        </div>
    </div>
</a>
