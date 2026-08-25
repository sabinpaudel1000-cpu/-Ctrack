<?php

namespace App\Enums;

enum MaintenanceServiceType: string
{
    case Scheduled = 'scheduled';
    case Repair = 'repair';
    case Inspection = 'inspection';
    case Tyres = 'tyres';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled service',
            self::Repair => 'Repair',
            self::Inspection => 'Inspection',
            self::Tyres => 'Tyres',
            self::Other => 'Other',
        };
    }
}
