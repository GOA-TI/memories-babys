@extends('layouts.mobile', ['mobileNavActive' => 'wall'])

@section('title', 'Muro')

@section('content')
    <div class="samuel-section-title samuel-section-title--compact" style="margin-top:0.65rem;">
        <span><i class="ri-chat-heart-line"></i> Muro</span>
    </div>

    @foreach($posts as $post)
        <article class="samuel-wall-card samuel-wall-card--compact" id="post-{{ $post->id }}">
            <div class="samuel-wall-card__head">
                @include('partials.wall-card-author', ['post' => $post])
                @include('partials.wall-post-admin-actions', ['post' => $post])
            </div>

            <div class="samuel-wall-card__media">
                @include('partials.post-media-carousel', ['post' => $post, 'priority' => $loop->index < 1])
            </div>

            @include('partials.wall-caption', ['caption' => $post->caption])

            <div class="samuel-wall-card__foot">
                @include('partials.wall-reactions', ['post' => $post, 'compact' => true])
                <form method="post" action="{{ route('posts.favorite', $post) }}" class="samuel-wall-fav" data-samuel-favorite-form data-post-id="{{ $post->id }}">
                    @csrf
                    <button type="submit" class="samuel-wall-fav__btn {{ $post->is_favorite ? 'is-active' : '' }}" data-samuel-favorite-btn aria-label="{{ $post->is_favorite ? 'Quitar favorito' : 'Marcar favorito' }}">
                        <i class="{{ $post->is_favorite ? 'ri-heart-3-fill' : 'ri-heart-3-line' }}" aria-hidden="true"></i>
                    </button>
                </form>
            </div>

            @include('partials.wall-comments', ['post' => $post])
        </article>
    @endforeach

    <div class="samuel-pagination-wrap">
        {{ $posts->links() }}
    </div>
@endsection
