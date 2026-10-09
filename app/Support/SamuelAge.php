<?php

namespace App\Support;

use Carbon\CarbonInterface;

final class SamuelAge
{
    public static function describe(CarbonInterface $birthAt, CarbonInterface $at): ?string
    {
        if ($at->lt($birthAt)) {
            return null;
        }

        if ($at->isSameDay($birthAt)) {
            $hours = (int) $birthAt->diffInHours($at, false);
            if ($hours < 1) {
                return 'Recién nacido';
            }

            return $hours === 1 ? '1 hora' : "{$hours} horas";
        }

        $diff = $birthAt->copy()->startOfDay()->diff($at->copy()->startOfDay());
        $years = (int) $diff->y;
        $months = (int) $diff->m;
        $days = (int) $diff->d;

        if ($years >= 1) {
            $parts = [];
            $parts[] = $years === 1 ? '1 año' : "{$years} años";
            if ($months > 0) {
                $parts[] = $months === 1 ? '1 mes' : "{$months} meses";
            }

            return implode(' y ', $parts);
        }

        if ($months >= 1) {
            $parts = [];
            $parts[] = $months === 1 ? '1 mes' : "{$months} meses";
            if ($days > 0) {
                $parts[] = $days === 1 ? '1 día' : "{$days} días";
            }

            return implode(' y ', $parts);
        }

        if ($days >= 1) {
            return $days === 1 ? '1 día' : "{$days} días";
        }

        return 'Recién nacido';
    }
}
