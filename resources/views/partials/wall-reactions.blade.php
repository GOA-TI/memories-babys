@php
    use App\Support\ReactionCatalog;
    $mine = $post->reactionForMember($familyMember?->id);
    $counts = $post->reactionCounts();
    $total = $counts->sum();
    $compact = $compact ?? false;
    $people = $post->reactionsPeoplePayload();
@endphp

<div
    class="samuel-reactions {{ $compact ? 'samuel-reactions--compact' : '' }}"
    data-samuel-reactions
    data-post-id="{{ $post->id }}"
    data-samuel-reactions-people='@json($people)'
    aria-label="Reacciones"
>
    <button
        type="button"
        class="samuel-reactions__who-open"
        data-samuel-reactions-open-who
        @if($total === 0) hidden @endif
        aria-label="Ver quién reaccionó"
    >
        <span class="samuel-reactions__summary" data-samuel-reactions-summary>
            @foreach($counts as $emotion => $count)
                <span class="samuel-reactions__pill samuel-reactions__pill--summary" title="{{ ReactionCatalog::label($emotion) }}">
                    {{ ReactionCatalog::emoji($emotion) }} {{ $count }}
                </span>
            @endforeach
        </span>
        <span class="samuel-reactions__who-open-label">Quién reaccionó</span>
    </button>

    <div class="samuel-reactions__picker">
        @foreach(ReactionCatalog::all() as $key => $meta)
            <form method="post" action="{{ route('posts.reaction', $post) }}" class="samuel-reactions__form">
                @csrf
                <input type="hidden" name="emotion" value="{{ $key }}">
                <button type="submit"
                        class="samuel-reactions__btn {{ $mine?->emotion === $key ? 'is-active' : '' }}"
                        title="{{ $meta['label'] }}"
                        aria-label="{{ $meta['label'] }}">
                    <span aria-hidden="true">{{ $meta['emoji'] }}</span>
                </button>
            </form>
        @endforeach
    </div>

    @if(! $compact && $post->reactions->isNotEmpty())
        <p class="samuel-reactions__who small text-muted mb-0">
            @php
                $names = $post->reactions->map(fn ($r) => $r->member?->name)->filter()->unique()->take(4);
            @endphp
            {{ $names->implode(', ') }}
            @if($post->reactions->count() > 4)
                y más
            @endif
        </p>
    @endif
</div>
