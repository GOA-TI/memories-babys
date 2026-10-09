@php
    use App\Models\MemoryMedia;

    $useFeed = $useFeed ?? true;
    $inlineVideo = (bool) ($inlineVideo ?? false);
    $isVideo = $item->isVideo();
    $url = $useFeed ? $item->feedDisplayUrl() : $item->displayUrl();
    $defer = (bool) ($defer ?? false);
    $eager = (bool) ($eager ?? false);
    $enableLightbox = ($lightbox ?? true) && $item instanceof MemoryMedia;
    $viewerUrl = $enableLightbox ? $item->viewerUrl() : null;
    $lightboxPayload = null;
    if ($enableLightbox && $viewerUrl) {
        $lightboxPayload = [
            'type' => $isVideo ? 'video' : 'image',
            'url' => $viewerUrl,
            'poster' => $isVideo ? $item->feedPosterUrl() : null,
            'download' => $item->appDownloadUrl(),
            'filename' => $item->original_name ?: ($isVideo ? 'video.mp4' : 'recuerdo.jpg'),
            'caption' => $item->caption,
        ];
    }
    $showVideoPoster = $isVideo && ! $inlineVideo;
    $videoFullUrl = $isVideo && $inlineVideo ? ($item->displayUrl()) : null;
@endphp
<div
    class="samuel-media-frame @if($lightboxPayload) samuel-media-frame--lightbox @endif @if($showVideoPoster) samuel-media-frame--video-poster @endif"
    data-samuel-media-frame
    @if($lightboxPayload)
        data-samuel-lightbox-item='@json($lightboxPayload)'
        tabindex="0"
        role="button"
        aria-label="{{ $isVideo ? 'Ver video' : 'Ver en grande' }}"
    @endif
>
    @if($showVideoPoster)
        @if($url)
            <img
                @if($defer)
                    data-samuel-src="{{ $url }}"
                @else
                    src="{{ $url }}"
                @endif
                alt="{{ $item->caption ?? 'Video' }}"
                class="samuel-media-frame__img samuel-media-frame__img--video-poster"
                decoding="async"
                loading="{{ $eager ? 'eager' : 'lazy' }}"
            >
        @else
            <div class="samuel-media-frame__placeholder samuel-media-frame__placeholder--video">Video</div>
        @endif
        <span class="samuel-media-frame__play" aria-hidden="true"><i class="ri-play-fill"></i></span>
    @elseif($isVideo && $videoFullUrl)
        <video
            src="{{ $defer ? '' : $videoFullUrl }}"
            @if($defer && $videoFullUrl) data-samuel-src="{{ $videoFullUrl }}" @endif
            controls
            playsinline
            preload="{{ $defer ? 'none' : 'metadata' }}"
        ></video>
    @elseif($url)
        <img
            @if($defer)
                data-samuel-src="{{ $url }}"
            @else
                src="{{ $url }}"
            @endif
            alt="{{ $item->caption ?? 'Recuerdo' }}"
            class="samuel-media-frame__img"
            decoding="async"
            @if($eager)
                loading="eager" fetchpriority="high"
            @else
                loading="lazy"
            @endif
        >
    @else
        <div class="samuel-media-frame__placeholder">Archivo en Drive</div>
    @endif
    @if($lightboxPayload)
        <span class="samuel-media-frame__open" aria-hidden="true"><i class="ri-fullscreen-line"></i></span>
    @endif
    <span class="samuel-media-frame__shimmer" aria-hidden="true"></span>
</div>
