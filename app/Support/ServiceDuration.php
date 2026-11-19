<?php

namespace App\Support;

class ServiceDuration
{
    public static function human(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        $parts = [];

        if ($hours > 0) {
            $parts[] = "{$hours} h";
        }

        if ($minutes > 0 || $hours > 0) {
            $parts[] = sprintf('%02d min', $minutes);
        }

        $parts[] = sprintf('%02d s', $remainingSeconds);

        return implode(' ', $parts);
    }
}
