<?php

namespace App\Models;

use App\Support\FamilyRelationship;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class FamilyMember extends Model
{
    protected $fillable = [
        'cedula',
        'phone',
        'name',
        'relationship',
        'pin_hash',
        'pin_reveal_encrypted',
        'is_admin',
        'is_active',
        'welcome_seen_at',
        'avatar_emoji',
        'avatar_path',
        'avatar_disk',
    ];

    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'welcome_seen_at' => 'datetime',
        ];
    }

    public function memories(): HasMany
    {
        return $this->hasMany(MemoryMedia::class);
    }

    public function memoryPosts(): HasMany
    {
        return $this->hasMany(MemoryPost::class, 'family_member_id');
    }

    public static function normalizeCedula(string $cedula): string
    {
        return preg_replace('/\D+/', '', $cedula) ?? '';
    }

    public static function normalizePhone(string $phone): string
    {
        return self::normalizeCedula($phone);
    }

    public static function findByLogin(string $login): ?self
    {
        $digits = self::normalizeCedula($login);
        if ($digits === '') {
            return null;
        }

        return self::query()
            ->where('is_active', true)
            ->where(function ($query) use ($digits) {
                $query->where('cedula', $digits)->orWhere('phone', $digits);
            })
            ->first();
    }

    public function relationshipLabel(): ?string
    {
        return FamilyRelationship::label($this->relationship);
    }

    public function relationshipLine(): ?string
    {
        return FamilyRelationship::lineForChild($this->relationship);
    }

    public function hasRelationship(): bool
    {
        return FamilyRelationship::isValid($this->relationship);
    }

    public function setPin(string $pin): void
    {
        $this->pin_hash = Hash::make($pin);
        $this->pin_reveal_encrypted = Crypt::encryptString($pin);
    }

    /** Solo panel admin: PIN guardado cifrado al crear o cambiar clave. */
    public function adminVisiblePin(): ?string
    {
        if (! filled($this->pin_reveal_encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->pin_reveal_encrypted);
        } catch (\Throwable) {
            return null;
        }
    }

    public function verifyPin(string $pin): bool
    {
        return Hash::check($pin, $this->pin_hash);
    }

    public function hasAvatarPhoto(): bool
    {
        return filled($this->avatar_path);
    }

    public function avatarPhotoUrl(): ?string
    {
        if (! $this->hasAvatarPhoto()) {
            return null;
        }

        $version = $this->updated_at?->timestamp ?? 0;
        if ($this->avatar_path) {
            $version ^= crc32($this->avatar_path);
        }

        return route('profile.avatar', $this).'?v='.$version;
    }
}
