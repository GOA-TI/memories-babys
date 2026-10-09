<li class="samuel-wall-comments__item" data-samuel-comment-id="{{ $comment['id'] }}">
    <div class="samuel-wall-comments__avatar" aria-hidden="true">
        @if(! empty($comment['author']['photo_url']))
            <img src="{{ $comment['author']['photo_url'] }}" alt="" class="samuel-member-avatar samuel-member-avatar--xs samuel-member-avatar--photo" loading="lazy">
        @else
            <span class="samuel-member-avatar samuel-member-avatar--xs samuel-member-avatar--emoji">{{ $comment['author']['emoji'] }}</span>
        @endif
    </div>
    <div class="samuel-wall-comments__bubble">
        <p class="samuel-wall-comments__meta">
            <span class="samuel-wall-comments__name">{{ $comment['author']['name'] }}</span>
            <span class="samuel-wall-comments__when">{{ $comment['created_at_human'] }}</span>
        </p>
        <p class="samuel-wall-comments__body">{{ $comment['body'] }}</p>
    </div>
    @if($comment['can_delete'])
        <button
            type="button"
            class="samuel-wall-comments__delete"
            data-samuel-comment-delete
            data-comment-id="{{ $comment['id'] }}"
            aria-label="Eliminar tu comentario"
        >
            <i class="ri-close-line" aria-hidden="true"></i>
        </button>
    @endif
</li>
