@php
    $threads = $post->commentsPayloadFor($familyMember?->id);
    $count = $post->commentsCount();
    $destroyUrlTemplate = route('posts.comment.destroy', ['comment' => '__ID__']);
@endphp

<section
    class="samuel-wall-comments"
    data-samuel-wall-comments
    data-post-id="{{ $post->id }}"
    data-samuel-comment-destroy-url="{{ $destroyUrlTemplate }}"
    data-samuel-comments-count="{{ $count }}"
    aria-label="Comentarios"
>
    @if($count > 0)
        <button
            type="button"
            class="samuel-wall-comments__toggle"
            data-samuel-comments-toggle
            aria-expanded="true"
        >
            <i class="ri-chat-3-line" aria-hidden="true"></i>
            <span data-samuel-comments-toggle-label>Ocultar comentarios ({{ $count }})</span>
            <i class="ri-arrow-up-s-line samuel-wall-comments__toggle-chevron" aria-hidden="true"></i>
        </button>
    @endif

    <div class="samuel-wall-comments__thread" data-samuel-comments-thread @if($count === 0) hidden @endif>
        <ul class="samuel-wall-comments__list" data-samuel-comments-list @if($count === 0) hidden @endif>
            @foreach($threads as $comment)
                @include('partials.wall-comment-item', ['comment' => $comment])
            @endforeach
        </ul>
    </div>

    <p class="samuel-wall-comments__reply-hint" data-samuel-comment-reply-hint hidden></p>

    <form
        method="post"
        action="{{ route('posts.comment.store', $post) }}"
        class="samuel-wall-comments__composer"
        data-samuel-comment-form
    >
        @csrf
        <input type="hidden" name="parent_comment_id" value="" data-samuel-comment-parent>
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
        <button type="button" class="samuel-wall-comments__cancel-reply" data-samuel-comment-cancel-reply hidden aria-label="Cancelar respuesta">
            <i class="ri-close-line" aria-hidden="true"></i>
        </button>
        <button type="submit" class="samuel-wall-comments__send" aria-label="Enviar comentario" data-samuel-comment-send disabled>
            <i class="ri-send-plane-2-fill" aria-hidden="true"></i>
        </button>
    </form>
</section>
