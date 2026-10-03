<?php

namespace App\Support;

use Illuminate\Support\Number;

/**
 * Number formatting for the admin dashboard.
 */
class Format
{
    public static function count(float|int $value): string
    {
        return $value >= 10000 ? Number::abbreviate($value, maxPrecision: 1) : number_format($value);
    }

    public static function duration(float|int $seconds): string
    {
        $seconds = (int) round($seconds);

        return $seconds >= 60 ? intdiv($seconds, 60).'m '.str_pad($seconds % 60, 2, '0', STR_PAD_LEFT).'s' : $seconds.'s';
    }

    public static function change(?float $percent): ?string
    {
        if ($percent === null) {
            return null;
        }

        $rounded = abs($percent) >= 10 ? round($percent) : round($percent, 1);

        return ($rounded > 0 ? '+' : ($rounded < 0 ? '−' : '')).abs($rounded).'%';
    }
}
