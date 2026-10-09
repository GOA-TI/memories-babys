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
        @if(! empty($comment['reply_to_name']))
            <p class="samuel-wall-comments__reply-to">
                Respondiendo a <strong>{{ $comment['reply_to_name'] }}</strong>
            </p>
        @endif
        <p class="samuel-wall-comments__body">{{ $comment['body'] }}</p>
        <button
            type="button"
            class="samuel-wall-comments__reply"
            data-samuel-comment-reply
            data-comment-id="{{ $comment['id'] }}"
            data-author-name="{{ $comment['author']['name'] }}"
        >
            Responder
        </button>
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
@if(! empty($comment['replies']))
    @foreach($comment['replies'] as $reply)
        <li class="samuel-wall-comments__item samuel-wall-comments__item--reply" data-samuel-comment-id="{{ $reply['id'] }}">
            <div class="samuel-wall-comments__avatar" aria-hidden="true">
                @if(! empty($reply['author']['photo_url']))
                    <img src="{{ $reply['author']['photo_url'] }}" alt="" class="samuel-member-avatar samuel-member-avatar--xs samuel-member-avatar--photo" loading="lazy">
                @else
                    <span class="samuel-member-avatar samuel-member-avatar--xs samuel-member-avatar--emoji">{{ $reply['author']['emoji'] }}</span>
                @endif
            </div>
            <div class="samuel-wall-comments__bubble">
                <p class="samuel-wall-comments__meta">
                    <span class="samuel-wall-comments__name">{{ $reply['author']['name'] }}</span>
                    <span class="samuel-wall-comments__when">{{ $reply['created_at_human'] }}</span>
                </p>
                @if(! empty($reply['reply_to_name']))
                    <p class="samuel-wall-comments__reply-to">
                        Respondiendo a <strong>{{ $reply['reply_to_name'] }}</strong>
                    </p>
                @endif
                <p class="samuel-wall-comments__body">{{ $reply['body'] }}</p>
                <button
                    type="button"
                    class="samuel-wall-comments__reply"
                    data-samuel-comment-reply
                    data-comment-id="{{ $reply['id'] }}"
                    data-author-name="{{ $reply['author']['name'] }}"
                >
                    Responder
                </button>
            </div>
            @if($reply['can_delete'])
                <button
                    type="button"
                    class="samuel-wall-comments__delete"
                    data-samuel-comment-delete
                    data-comment-id="{{ $reply['id'] }}"
                    aria-label="Eliminar tu comentario"
                >
                    <i class="ri-close-line" aria-hidden="true"></i>
                </button>
            @endif
        </li>
    @endforeach
@endif
