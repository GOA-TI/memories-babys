@extends('layouts.mobile', ['mobileNavActive' => 'albums'])

@section('title', $album->title)

@section('content')
    <div class="samuel-hero">
        <p class="mb-0" style="font-size:1.8rem;">{{ $album->emoji }}</p>
        <h1 class="samuel-hero__title">{{ $album->title }}</h1>
        @if($album->description)
            <p class="samuel-hero__sub">{{ $album->description }}</p>
        @endif
    </div>

    <div class="samuel-grid">
        @forelse($mediaItems as $item)
            <article class="samuel-grid__item">
                @include('partials.media-thumb', ['item' => $item, 'defer' => true])
            </article>
        @empty
            <p class="samuel-home-empty">Esta carpeta está esperando el primer recuerdo.</p>
        @endforelse
    </div>

    <div class="samuel-pagination-wrap">{{ $mediaItems->links() }}</div>
@endsection
