@php
    $fromMemories = $fromMemories ?? false;
    $showEdit = $showEdit ?? true;
    $adminDelete = $adminDelete ?? false;
    $confirmTitle = $adminDelete
        ? '¿Eliminar publicación? (admin)'
        : '¿Eliminar esta publicación?';
    $confirmText = $adminDelete
        ? 'Como administrador vas a borrar el recuerdo de otra persona. No se puede deshacer.'
        : 'Se borrarán todas las fotos y videos de este momento. Esta acción no se puede deshacer.';
@endphp
<div class="samuel-post-menu" data-post-menu>
    <button type="button"
            class="samuel-post-menu__trigger"
            aria-label="Opciones de la publicación"
            aria-haspopup="true"
            aria-expanded="false"
            data-post-menu-toggle>
        <i class="ri-more-2-fill" aria-hidden="true"></i>
    </button>
    <div class="samuel-post-menu__panel" role="menu" hidden data-post-menu-panel>
        @if($showEdit)
            <a href="{{ route('posts.edit', $post) }}{{ $fromMemories ? '?from=memories' : '' }}"
               class="samuel-post-menu__item" role="menuitem">
                <i class="ri-edit-line" aria-hidden="true"></i>
                <span>Editar</span>
            </a>
        @endif
        <form method="post" action="{{ route('posts.destroy', $post) }}"
              class="samuel-post-menu__form"
              data-samuel-confirm
              data-samuel-confirm-title="{{ $confirmTitle }}"
              data-samuel-confirm-text="{{ $confirmText }}"
              data-samuel-confirm-ok="Sí, eliminar">
            @csrf
            @method('DELETE')
            @if($fromMemories)
                <input type="hidden" name="redirect" value="memories">
            @endif
            <button type="submit" class="samuel-post-menu__item samuel-post-menu__item--danger" role="menuitem">
                <i class="ri-delete-bin-line" aria-hidden="true"></i>
                <span>{{ $adminDelete ? 'Eliminar (admin)' : 'Eliminar' }}</span>
            </button>
        </form>
    </div>
</div>
