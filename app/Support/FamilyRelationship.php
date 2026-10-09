<?php

namespace App\Support;

class FamilyRelationship
{
    /** @return array<string, string> key => etiqueta visible */
    public static function options(): array
    {
        return config('samuel.relationships', []);
    }

    public static function label(?string $key): ?string
    {
        if (! $key) {
            return null;
        }

        return self::options()[$key] ?? null;
    }

    public static function isValid(?string $key): bool
    {
        return $key !== null && $key !== '' && array_key_exists($key, self::options());
    }

    public static function lineForChild(?string $key, ?string $childName = null): ?string
    {
        $label = self::label($key);
        if (! $label) {
            return null;
        }

        $child = $childName ?: (string) config('samuel.app_name', 'Samuel');

        return $label.' de '.$child;
    }
}
