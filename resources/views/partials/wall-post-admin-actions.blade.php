@if($familyMember?->is_admin && (int) $post->family_member_id !== (int) $familyMember->id)
    @include('partials.post-context-menu', [
        'post' => $post,
        'fromMemories' => false,
        'showEdit' => false,
        'adminDelete' => true,
    ])
@endif
