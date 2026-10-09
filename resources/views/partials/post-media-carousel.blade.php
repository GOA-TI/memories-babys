@php
    $items = $post->mediaItems ?? collect([$post->coverMedia()])->filter();
    $uid = 'carousel-'.$post->id;
    $priority = (bool) ($priority ?? false);
    $inlineVideo = (bool) ($inlineVideo ?? false);
    $useFeed = $useFeed ?? true;
    $thumbParams = ['inlineVideo' => $inlineVideo, 'useFeed' => $useFeed];
@endphp

@if($items->count() <= 1)
    <div class="samuel-post-media samuel-post-media--single">
        @foreach($items as $item)
            @include('partials.media-thumb', array_merge($thumbParams, ['item' => $item, 'eager' => $priority]))
        @endforeach
    </div>
@else
    <div class="samuel-post-media samuel-post-media--carousel" id="{{ $uid }}" data-carousel>
        <div class="samuel-post-media__track">
            @foreach($items as $index => $item)
                <div class="samuel-post-media__slide {{ $index === 0 ? 'is-active' : '' }}" data-slide="{{ $index }}">
                    @include('partials.media-thumb', array_merge($thumbParams, [
                        'item' => $item,
                        'defer' => $index > 0,
                        'eager' => $priority && $index === 0,
                    ]))
                </div>
            @endforeach
        </div>
        <div class="samuel-post-media__nav" aria-hidden="true">
            <button type="button" class="samuel-post-media__arrow" data-carousel-prev aria-label="Anterior"><i class="ri-arrow-left-s-line"></i></button>
            <span class="samuel-post-media__count"><span data-carousel-current>1</span>/{{ $items->count() }}</span>
            <button type="button" class="samuel-post-media__arrow" data-carousel-next aria-label="Siguiente"><i class="ri-arrow-right-s-line"></i></button>
        </div>
    </div>
@endif
