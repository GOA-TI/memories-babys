<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyNotification extends Model
{
    public const TYPE_NEW_POST = 'new_post';

    protected $fillable = [
        'family_member_id',
        'actor_family_member_id',
        'memory_post_id',
        'type',
        'title',
        'body',
        'url',
        'meta',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'family_member_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'actor_family_member_id');
    }

    public function memoryPost(): BelongsTo
    {
        return $this->belongsTo(MemoryPost::class, 'memory_post_id');
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
