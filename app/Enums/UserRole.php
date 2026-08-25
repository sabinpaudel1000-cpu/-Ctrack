<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case FleetManager = 'fleet_manager';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::FleetManager => 'Fleet Manager',
        };
    }
}
