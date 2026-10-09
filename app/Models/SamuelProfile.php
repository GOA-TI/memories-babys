<?php

namespace App\Models;

use App\Support\AppTheme;
use App\Support\SamuelAge;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class SamuelProfile extends Model
{
    protected $fillable = [
        'display_name',
        'emoji',
        'birth_at',
        'theme_primary',
        'theme_secondary',
        'theme_background',
    ];

    protected function casts(): array
    {
        return [
            'birth_at' => 'datetime',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'emoji' => '👶',
        ]);
    }

    public function isConfigured(): bool
    {
        return $this->birth_at !== null;
    }

    public function displayName(): string
    {
        return filled($this->display_name)
            ? (string) $this->display_name
            : (string) config('samuel.app_name', 'Samuel');
    }

    public function emoji(): string
    {
        return $this->emoji ?: '👶';
    }

    public function birthAtLocal(): ?Carbon
    {
        if (! $this->birth_at) {
            return null;
        }

        return $this->birth_at->copy()->timezone(config('app.timezone'));
    }

    public function ageAt(CarbonInterface $at): ?string
    {
        $birth = $this->birthAtLocal();
        if (! $birth) {
            return null;
        }

        return SamuelAge::describe($birth, $at->copy()->timezone(config('app.timezone')));
    }

    public function currentAge(): ?string
    {
        return $this->ageAt(now());
    }

    public function formattedBirthdayLong(): ?string
    {
        $birth = $this->birthAtLocal();
        if (! $birth) {
            return null;
        }

        return $birth->copy()->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
    }

    public function formattedBirthdayShort(): ?string
    {
        $birth = $this->birthAtLocal();
        if (! $birth) {
            return null;
        }

        return $birth->copy()->locale('es')->isoFormat('D [de] MMMM');
    }

    public function formattedBirthTime(): ?string
    {
        $birth = $this->birthAtLocal();
        if (! $birth || ! $this->hasBirthTime()) {
            return null;
        }

        return $birth->copy()->locale('es')->isoFormat('h:mm A');
    }

    public function hasBirthTime(): bool
    {
        $birth = $this->birthAtLocal();
        if (! $birth) {
            return false;
        }

        return ! ($birth->hour === 0 && $birth->minute === 0 && $birth->second === 0);
    }

    public function isBirthdayToday(): bool
    {
        $birth = $this->birthAtLocal();
        if (! $birth) {
            return false;
        }

        $today = now()->timezone(config('app.timezone'));

        return $birth->month === $today->month && $birth->day === $today->day;
    }

    public function daysUntilNextBirthday(): ?int
    {
        $birth = $this->birthAtLocal();
        if (! $birth) {
            return null;
        }

        $today = now()->timezone(config('app.timezone'))->startOfDay();
        $next = $birth->copy()->year($today->year);
        if ($next->lt($today)) {
            $next->addYear();
        }

        return (int) $today->diffInDays($next, false);
    }

    public function ageTurningOnNextBirthday(): ?string
    {
        $birth = $this->birthAtLocal();
        if (! $birth) {
            return null;
        }

        $today = now()->timezone(config('app.timezone'))->startOfDay();
        $next = $birth->copy()->year($today->year);
        if ($next->lt($today)) {
            $next->addYear();
        }

        return $this->ageAt($next->endOfDay());
    }

    public function themePrimary(): string
    {
        return AppTheme::normalizeHex($this->theme_primary)
            ?? (string) config('samuel.theme.primary', '#f472b6');
    }

    public function themeSecondary(): string
    {
        return AppTheme::normalizeHex($this->theme_secondary)
            ?? (string) config('samuel.theme.secondary', '#a78bfa');
    }

    public function themeBackground(): string
    {
        return AppTheme::normalizeHex($this->theme_background)
            ?? (string) config('samuel.theme.background', '#F3FAF0');
    }

    /** @return array{primary: string, secondary: string, background: string} */
    public function themeColors(): array
    {
        return [
            'primary' => $this->themePrimary(),
            'secondary' => $this->themeSecondary(),
            'background' => $this->themeBackground(),
        ];
    }
}
