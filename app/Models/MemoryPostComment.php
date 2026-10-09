<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemoryPostComment extends Model
{
    protected $fillable = [
        'memory_post_id',
        'family_member_id',
        'body',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(MemoryPost::class, 'memory_post_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'family_member_id');
    }

    /** @return array{id: int, body: string, created_at_human: string, author: array{name: string, emoji: string, photo_url: ?string}, can_delete: bool} */
    public function toWallPayload(?int $viewerMemberId): array
    {
        $member = $this->member;

        return [
            'id' => $this->id,
            'body' => $this->body,
            'created_at_human' => $this->created_at->locale('es')->diffForHumans(short: true),
            'author' => [
                'name' => $member?->name ?? 'Familia',
                'emoji' => $member?->avatar_emoji ?: '🙂',
                'photo_url' => ($member && $member->hasAvatarPhoto()) ? $member->avatarPhotoUrl() : null,
            ],
            'can_delete' => $viewerMemberId !== null && $this->family_member_id === $viewerMemberId,
        ];
    }
}
