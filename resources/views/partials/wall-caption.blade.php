@php
    $caption = trim((string) ($caption ?? ''));
    $limit = 140;
    $long = mb_strlen($caption) > $limit;
    $short = $long ? mb_substr($caption, 0, $limit).'…' : $caption;
@endphp
@if($caption !== '')
    <div class="samuel-wall-caption" @if($long) data-caption-expand @endif>
        <p class="samuel-wall-caption__text" @if($long) data-caption-short @endif>{{ $short }}</p>
        @if($long)
            <p class="samuel-wall-caption__text samuel-wall-caption__text--full" hidden data-caption-full>{{ $caption }}</p>
            <button type="button" class="samuel-wall-caption__more" data-caption-toggle>Ver más</button>
        @endif
    </div>
@endif
