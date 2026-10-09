<?php

namespace App\Support;

class ReactionCatalog
{
    /** @return array<string, array{emoji: string, label: string}> */
    public static function all(): array
    {
        return [
            'like' => ['emoji' => '👍', 'label' => 'Me gusta'],
            'love' => ['emoji' => '❤️', 'label' => 'Amor'],
            'cute' => ['emoji' => '🥰', 'label' => 'Tierno'],
            'joy' => ['emoji' => '😂', 'label' => 'Risas'],
            'wow' => ['emoji' => '😮', 'label' => 'Wow'],
            'party' => ['emoji' => '🎉', 'label' => 'Fiesta'],
        ];
    }

    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function isValid(string $emotion): bool
    {
        return isset(self::all()[$emotion]);
    }

    public static function emoji(string $emotion): string
    {
        return self::all()[$emotion]['emoji'] ?? '✨';
    }

    public static function label(string $emotion): string
    {
        return self::all()[$emotion]['label'] ?? $emotion;
    }
}
