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
    case PredictedHighFuel = 'predicted_high_fuel';
    case PredictedDriverSafety = 'predicted_driver_safety';

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
            self::PredictedHighFuel => 'Predicted high fuel use',
            self::PredictedDriverSafety => 'Predicted driver safety risk',
        };
    }

    public function isOperational(): bool
    {
        // The maintenance alert is the score itself, so it is not counted again.
        // Forecast alerts stay out of that score so the existing baseline does not change.
        return match ($this) {
            self::HighMaintenanceRisk, self::PredictedHighFuel, self::PredictedDriverSafety => false,
            default => true,
        };
    }
}
