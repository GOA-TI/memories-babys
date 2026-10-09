@php
    $cover = $post->coverMedia();
    $count = $post->mediaItems->count();
@endphp
<article class="samuel-my-memory" id="my-mem-{{ $post->id }}">
    <a href="{{ route('wall') }}#post-{{ $post->id }}" class="samuel-my-memory__thumb">
        @if($cover)
            @include('partials.media-thumb', ['item' => $cover])
        @endif
        @if($count > 1)
            <span class="samuel-my-memory__stack-badge">{{ $count }}</span>
        @endif
    </a>
    <div class="samuel-my-memory__body">
        <div class="samuel-my-memory__head">
            <div class="samuel-my-memory__head-text">
                @if($post->caption)
                    <p class="samuel-my-memory__caption">{{ \Illuminate\Support\Str::limit($post->caption, 80) }}</p>
                @else
                    <p class="samuel-my-memory__caption samuel-my-memory__caption--muted">Sin descripción</p>
                @endif
                <p class="samuel-my-memory__meta">
                    {{ $post->memoryMoment()->locale('es')->isoFormat('D MMM YYYY') }}
                    @if($post->album)
                        · {{ $post->album->emoji }}
                    @endif
                    @if(! $post->show_on_wall)
                        · <span class="samuel-my-memory__private">Solo tú</span>
                    @endif
                </p>
                @include('partials.samuel-age-at', ['moment' => $post->memoryMoment()])
            </div>
            @include('partials.post-context-menu', ['post' => $post, 'fromMemories' => true])
        </div>
    </div>
</article>
