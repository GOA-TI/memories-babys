<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyPushSubscription extends Model
{
    protected $fillable = [
        'family_member_id',
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
        'user_agent',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'family_member_id');
    }
}
