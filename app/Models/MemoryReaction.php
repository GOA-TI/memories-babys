<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemoryReaction extends Model
{
    protected $fillable = [
        'memory_post_id',
        'family_member_id',
        'emotion',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(MemoryPost::class, 'memory_post_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'family_member_id');
    }
}
