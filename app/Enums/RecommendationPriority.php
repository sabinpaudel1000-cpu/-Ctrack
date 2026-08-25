<?php

namespace App\Enums;

enum RecommendationPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Low => 'info',
            self::Medium => 'warning',
            self::High => 'danger',
        };
    }
}
