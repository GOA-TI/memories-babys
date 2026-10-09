<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Album extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'emoji',
        'color',
        'description',
        'drive_account_id',
        'drive_folder_id',
        'sort_order',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    public function driveAccount(): BelongsTo
    {
        return $this->belongsTo(DriveAccount::class);
    }

    public function memories(): HasMany
    {
        return $this->hasMany(MemoryMedia::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(MemoryPost::class);
    }

    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'album';
        $slug = $base;
        $i = 2;
        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
