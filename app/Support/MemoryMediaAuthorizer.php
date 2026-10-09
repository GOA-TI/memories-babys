<?php

namespace App\Support;

use App\Models\FamilyMember;
use App\Models\MemoryMedia;

class MemoryMediaAuthorizer
{
    /** Solo el autor puede editar o eliminar; el rol admin no da permiso sobre recuerdos ajenos. */
    public static function canManage(MemoryMedia $media, ?FamilyMember $member): bool
    {
        if (! $member) {
            return false;
        }

        return (int) $media->family_member_id === (int) $member->id;
    }

    public static function denyMessage(): string
    {
        return 'Solo puedes editar o eliminar tus propias fotos y videos. Lo que subió otra persona no se puede borrar desde tu cuenta.';
    }
}
