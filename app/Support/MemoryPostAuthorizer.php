<?php

namespace App\Support;

use App\Models\FamilyMember;
use App\Models\MemoryPost;

class MemoryPostAuthorizer
{
    public static function canManage(MemoryPost $post, ?FamilyMember $member): bool
    {
        if (! $member) {
            return false;
        }

        return (int) $post->family_member_id === (int) $member->id;
    }

    public static function canDelete(MemoryPost $post, ?FamilyMember $member): bool
    {
        if (! $member) {
            return false;
        }

        if ((int) $post->family_member_id === (int) $member->id) {
            return true;
        }

        return $member->is_admin === true;
    }

    public static function denyMessage(): string
    {
        return 'Solo puedes editar tus propias publicaciones.';
    }

    public static function denyDeleteMessage(): string
    {
        return 'No tienes permiso para eliminar esta publicación.';
    }
}
