<?php

namespace App\Support;

use App\Models\FamilyMember;

class FamilySession
{
    public const SESSION_KEY = 'samuel_family_member_id';

    public const TOPBAR_AVATAR_MEMBER_KEY = 'samuel_topbar_avatar_member_id';

    public const TOPBAR_AVATAR_URL_KEY = 'samuel_topbar_avatar_url';

    public static function member(): ?FamilyMember
    {
        $id = session(self::SESSION_KEY);
        if (! $id) {
            return null;
        }

        return FamilyMember::query()
            ->whereKey($id)
            ->where('is_active', true)
            ->first();
    }

    public static function login(FamilyMember $member): void
    {
        session([self::SESSION_KEY => $member->id]);
        self::syncTopbarAvatar($member);
    }

    public static function logout(): void
    {
        session()->forget([
            self::SESSION_KEY,
            self::TOPBAR_AVATAR_MEMBER_KEY,
            self::TOPBAR_AVATAR_URL_KEY,
        ]);
    }

    /** URL estable del avatar en el header durante toda la sesión (solo cambia al actualizar foto). */
    public static function syncTopbarAvatar(FamilyMember $member): void
    {
        session([
            self::TOPBAR_AVATAR_MEMBER_KEY => $member->id,
            self::TOPBAR_AVATAR_URL_KEY => $member->hasAvatarPhoto() ? $member->avatarPhotoUrl() : null,
        ]);
    }

    public static function topbarAvatarUrl(FamilyMember $member): ?string
    {
        if ((int) session(self::TOPBAR_AVATAR_MEMBER_KEY) !== (int) $member->id) {
            self::syncTopbarAvatar($member);

            return session(self::TOPBAR_AVATAR_URL_KEY);
        }

        if (! $member->hasAvatarPhoto()) {
            session([self::TOPBAR_AVATAR_URL_KEY => null]);

            return null;
        }

        $cached = session(self::TOPBAR_AVATAR_URL_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        self::syncTopbarAvatar($member);

        return session(self::TOPBAR_AVATAR_URL_KEY);
    }

    public static function isAdmin(): bool
    {
        $member = self::member();

        return $member?->is_admin === true;
    }
}
