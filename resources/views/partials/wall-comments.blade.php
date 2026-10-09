@php
    $comments = $post->commentsPayloadFor($familyMember?->id);
    $count = count($comments);
    $destroyUrlTemplate = route('posts.comment.destroy', ['comment' => '__ID__']);
@endphp

<section
    class="samuel-wall-comments"
    data-samuel-wall-comments
    data-post-id="{{ $post->id }}"
    data-samuel-comment-destroy-url="{{ $destroyUrlTemplate }}"
    aria-label="Comentarios"
>
    @if($count > 0)
        <ul class="samuel-wall-comments__list" data-samuel-comments-list>
            @foreach($comments as $comment)
                @include('partials.wall-comment-item', ['comment' => $comment])
            @endforeach
        </ul>
    @else
        <ul class="samuel-wall-comments__list" data-samuel-comments-list hidden></ul>
    @endif

    <form
        method="post"
        action="{{ route('posts.comment.store', $post) }}"
        class="samuel-wall-comments__composer"
        data-samuel-comment-form
    >
        @csrf
        @include('partials.member-avatar', ['member' => $familyMember, 'size' => 'xs'])
        <div class="samuel-wall-comments__field">
            <input
                type="text"
                name="body"
                id="comment-body-{{ $post->id }}"
                class="samuel-wall-comments__input"
                maxlength="500"
                placeholder="Escribe algo lindo…"
                autocomplete="off"
                enterkeyhint="send"
                aria-label="Tu comentario"
                data-samuel-comment-input
            >
        </div>
        <button type="submit" class="samuel-wall-comments__send" aria-label="Enviar comentario" data-samuel-comment-send disabled>
            <i class="ri-send-plane-2-fill" aria-hidden="true"></i>
        </button>
    </form>
</section>
