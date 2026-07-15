<?php

namespace App\Support;

class Time
{
    /**
     * Format a number of seconds as a compact "M:SS" / "H:MM:SS" timestamp.
     */
    public static function hms(float|int $seconds): string
    {
        $seconds = (int) floor($seconds);
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        if ($h > 0) {
            return sprintf('%d:%02d:%02d', $h, $m, $s);
        }

        return sprintf('%d:%02d', $m, $s);
    }
}
