<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DriveAccount extends Model
{
    protected $fillable = [
        'key',
        'label',
        'google_client_id',
        'google_client_secret',
        'refresh_token',
        'root_folder_id',
        'owner_email',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function albums(): HasMany
    {
        return $this->hasMany(Album::class);
    }

    public function memories(): HasMany
    {
        return $this->hasMany(MemoryMedia::class);
    }

    public function isConfigured(): bool
    {
        return filled($this->google_client_id)
            && filled($this->google_client_secret)
            && filled($this->refresh_token);
    }

    public static function defaultAccount(): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->first();
    }
}
