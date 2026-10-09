<?php

namespace App\Support;

final class AppTheme
{
    /** @return array{primary: string, secondary: string, background: string} */
    public static function presets(): array
    {
        return [
            'dino' => [
                'label' => 'Verde dinosaurio',
                'primary' => '#5EAD65',
                'secondary' => '#A8D08D',
                'background' => '#F3FAF0',
            ],
            'rosa' => [
                'label' => 'Rosa memorias',
                'primary' => '#f472b6',
                'secondary' => '#a78bfa',
                'background' => '#fff7fb',
            ],
            'cielo' => [
                'label' => 'Cielo suave',
                'primary' => '#38bdf8',
                'secondary' => '#818cf8',
                'background' => '#f0f9ff',
            ],
        ];
    }

    public static function normalizeHex(?string $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $value = trim($value);
        if (! str_starts_with($value, '#')) {
            $value = '#'.$value;
        }

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? strtolower($value) : null;
    }
}
