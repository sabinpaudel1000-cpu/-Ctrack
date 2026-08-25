<?php

namespace App\Enums;

enum AlertType: string
{
    case HighMaintenanceRisk = 'high_maintenance_risk';
    case HighFuelConsumption = 'high_fuel_consumption';
    case PoorDriverSafety = 'poor_driver_safety';
    case ExcessiveHarshBraking = 'excessive_harsh_braking';
    case ExcessiveHarshAcceleration = 'excessive_harsh_acceleration';
    case HighEngineTemperature = 'high_engine_temperature';
    case Speeding = 'speeding';

    public function label(): string
    {
        return match ($this) {
            self::HighMaintenanceRisk => 'High maintenance risk',
            self::HighFuelConsumption => 'High fuel consumption',
            self::PoorDriverSafety => 'Poor driver safety',
            self::ExcessiveHarshBraking => 'Excessive harsh braking',
            self::ExcessiveHarshAcceleration => 'Excessive harsh acceleration',
            self::HighEngineTemperature => 'High engine temperature',
            self::Speeding => 'Speeding',
        };
    }

    public function isOperational(): bool
    {
        return $this !== self::HighMaintenanceRisk;
    }
}
