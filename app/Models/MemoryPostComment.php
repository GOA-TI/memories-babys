<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemoryPostComment extends Model
{
    protected $fillable = [
        'memory_post_id',
        'family_member_id',
        'parent_comment_id',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_comment_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_comment_id')->orderBy('created_at');
    }

    /** @return array{id: int, parent_id: ?int, reply_to_name: ?string, body: string, created_at_human: string, author: array{name: string, emoji: string, photo_url: ?string}, can_delete: bool, replies: list<array>} */
    public function toWallPayload(?int $viewerMemberId, bool $includeReplies = false): array
    {
        $member = $this->member;
        $replyToName = null;
        if ($this->parent_comment_id && $this->relationLoaded('parent') && $this->parent) {
            $replyToName = $this->parent->member?->name;
        }

        $payload = [
            'id' => $this->id,
            'parent_id' => $this->parent_comment_id,
            'reply_to_name' => $replyToName,
            'body' => $this->body,
            'created_at_human' => $this->created_at->locale('es')->diffForHumans(short: true),
            'author' => [
                'name' => $member?->name ?? 'Familia',
                'emoji' => $member?->avatar_emoji ?: '🙂',
                'photo_url' => ($member && $member->hasAvatarPhoto()) ? $member->avatarPhotoUrl() : null,
            ],
            'can_delete' => $viewerMemberId !== null && $this->family_member_id === $viewerMemberId,
            'replies' => [],
        ];

        if ($includeReplies && $this->relationLoaded('replies')) {
            $payload['replies'] = $this->replies
                ->map(fn (self $reply) => $reply->toWallPayload($viewerMemberId, false))
                ->values()
                ->all();
        }

        return $payload;
    }
}
