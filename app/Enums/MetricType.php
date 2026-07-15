<?php

namespace App\Enums;

enum MetricType: int
{
    case View = 1;
    case Download = 2;

    public function label(): string
    {
        return match ($this) {
            self::View => 'view',
            self::Download => 'download',
        };
    }
}
