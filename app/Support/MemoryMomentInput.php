<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

final class MemoryMomentInput
{
    public static function fromRequest(Request $request): Carbon
    {
        $taken = $request->input('memory_taken', 'today');
        if ($taken === 'today') {
            return now();
        }

        $date = $request->input('memory_date');
        if (! filled($date)) {
            return now();
        }

        $tz = config('app.timezone');
        $time = filled($request->input('memory_time')) ? $request->input('memory_time') : '12:00';

        return Carbon::createFromFormat('Y-m-d H:i', $date.' '.$time, $tz);
    }

    public static function validationRules(): array
    {
        return [
            'memory_taken' => ['nullable', 'in:today,other'],
            'memory_date' => ['required_if:memory_taken,other', 'nullable', 'date', 'before_or_equal:today'],
            'memory_time' => ['nullable', 'date_format:H:i'],
        ];
    }

    public static function defaultTakenMode(?Carbon $existingMoment = null): string
    {
        if (old('memory_taken')) {
            return (string) old('memory_taken');
        }

        if (! $existingMoment) {
            return 'today';
        }

        return $existingMoment->isToday() ? 'today' : 'other';
    }
}
