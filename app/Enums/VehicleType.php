<?php

namespace App\Enums;

enum VehicleType: string
{
    case Truck = 'truck';
    case Van = 'van';
    case Ute = 'ute';
    case Car = 'car';

    public function label(): string
    {
        return match ($this) {
            self::Truck => 'Truck',
            self::Van => 'Van',
            self::Ute => 'Ute',
            self::Car => 'Car',
        };
    }
}
