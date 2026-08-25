<?php

namespace App\Services\Alerts;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\AlertType;
use App\Enums\RiskLevel;
use App\Models\Alert;
use App\Models\Driver;
use App\Models\TelematicsRecord;
use App\Models\Vehicle;
use App\Services\Analytics\DriverInsights;
use Illuminate\Support\Collection;

final class AlertGenerator
{
    public function __construct(
        private readonly DriverInsights $driverInsights,
    ) {}

    /**
     * Replace system-generated alerts with a fresh pass over current data.
     */
    public function regenerate(): Collection
    {
        Alert::query()->delete();

        $created = collect();
        $now = now();
        $from = $now->copy()->subDays(30);

        $fleetFuel = $this->fleetLitresPer100($from);
        $vehicles = Vehicle::query()->with(['driver', 'latestRisk'])->get();
        $driverIds = Driver::query()->pluck('id')->all();
        $driverInsights = $this->driverInsights->forDrivers($driverIds);

        foreach ($vehicles as $vehicle) {
            $stats = $this->vehicleStats($vehicle->id, $from);

            if ($vehicle->latestRisk?->level === RiskLevel::High) {
                $created->push($this->make(
                    $vehicle,
                    $vehicle->driver_id,
                    AlertType::HighMaintenanceRisk,
                    AlertSeverity::High,
                    "{$vehicle->registration_number} scored {$vehicle->latestRisk->score}/100 (HIGH) on the weighted maintenance baseline.",
                ));
            } elseif ($vehicle->latestRisk?->level === RiskLevel::Medium) {
                $created->push($this->make(
                    $vehicle,
                    $vehicle->driver_id,
                    AlertType::HighMaintenanceRisk,
                    AlertSeverity::Medium,
                    "{$vehicle->registration_number} scored {$vehicle->latestRisk->score}/100 (MEDIUM) on the weighted maintenance baseline. Plan service before it reaches HIGH.",
                ));
            }

            if ($stats['l_per_100km'] > 0 && $fleetFuel > 0 && $stats['l_per_100km'] > ($fleetFuel * 1.25)) {
                $created->push($this->make(
                    $vehicle,
                    $vehicle->driver_id,
                    AlertType::HighFuelConsumption,
                    AlertSeverity::Medium,
                    "{$vehicle->registration_number} used {$stats['l_per_100km']} L/100 km versus fleet average {$fleetFuel} L/100 km.",
                ));
            }

            if ($stats['avg_temp'] >= 110) {
                $created->push($this->make(
                    $vehicle,
                    $vehicle->driver_id,
                    AlertType::HighEngineTemperature,
                    AlertSeverity::High,
                    "{$vehicle->registration_number} averaged {$stats['avg_temp']} °C engine temperature over 30 days.",
                ));
            }

            if ($stats['harsh_braking'] >= 12) {
                $created->push($this->make(
                    $vehicle,
                    $vehicle->driver_id,
                    AlertType::ExcessiveHarshBraking,
                    AlertSeverity::Medium,
                    "{$vehicle->registration_number} recorded {$stats['harsh_braking']} harsh braking events in 30 days.",
                ));
            }

            if ($stats['harsh_acceleration'] >= 12) {
                $created->push($this->make(
                    $vehicle,
                    $vehicle->driver_id,
                    AlertType::ExcessiveHarshAcceleration,
                    AlertSeverity::Medium,
                    "{$vehicle->registration_number} recorded {$stats['harsh_acceleration']} harsh acceleration events in 30 days.",
                ));
            }

            if ($stats['speeding'] >= 8) {
                $created->push($this->make(
                    $vehicle,
                    $vehicle->driver_id,
                    AlertType::Speeding,
                    AlertSeverity::High,
                    "{$vehicle->registration_number} recorded {$stats['speeding']} speeding events in 30 days.",
                ));
            }
        }

        foreach (Driver::query()->get() as $driver) {
            $insight = $driverInsights[$driver->id] ?? null;
            if (! $insight || $insight['score'] >= 60) {
                continue;
            }

            $created->push(Alert::query()->create([
                'vehicle_id' => $driver->vehicle?->id,
                'driver_id' => $driver->id,
                'type' => AlertType::PoorDriverSafety,
                'severity' => $insight['score'] < 45 ? AlertSeverity::High : AlertSeverity::Medium,
                'message' => "{$driver->fullName()} has a 30-day safety score of {$insight['score']}/100.",
                'status' => AlertStatus::Open,
                'triggered_at' => $now,
            ]));
        }

        return $created;
    }

    private function make(Vehicle $vehicle, ?int $driverId, AlertType $type, AlertSeverity $severity, string $message): Alert
    {
        return Alert::query()->create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driverId,
            'type' => $type,
            'severity' => $severity,
            'message' => $message,
            'status' => AlertStatus::Open,
            'triggered_at' => now(),
        ]);
    }

    /**
     * @return array{distance: float, fuel: float, l_per_100km: float, avg_temp: float, harsh_braking: int, harsh_acceleration: int, speeding: int}
     */
    private function vehicleStats(int $vehicleId, $from): array
    {
        $row = TelematicsRecord::query()
            ->where('vehicle_id', $vehicleId)
            ->where('recorded_at', '>=', $from)
            ->selectRaw('COALESCE(SUM(distance_km), 0) as distance')
            ->selectRaw('COALESCE(SUM(fuel_consumed_l), 0) as fuel')
            ->selectRaw('COALESCE(AVG(engine_temperature), 0) as avg_temp')
            ->selectRaw('COALESCE(SUM(CASE WHEN harsh_braking = 1 THEN 1 ELSE 0 END), 0) as harsh_braking')
            ->selectRaw('COALESCE(SUM(CASE WHEN harsh_acceleration = 1 THEN 1 ELSE 0 END), 0) as harsh_acceleration')
            ->selectRaw('COALESCE(SUM(CASE WHEN speeding = 1 THEN 1 ELSE 0 END), 0) as speeding')
            ->first();

        $distance = (float) $row->distance;
        $fuel = (float) $row->fuel;

        return [
            'distance' => $distance,
            'fuel' => $fuel,
            'l_per_100km' => $distance > 0 ? round(($fuel / $distance) * 100, 1) : 0,
            'avg_temp' => round((float) $row->avg_temp, 1),
            'harsh_braking' => (int) $row->harsh_braking,
            'harsh_acceleration' => (int) $row->harsh_acceleration,
            'speeding' => (int) $row->speeding,
        ];
    }

    private function fleetLitresPer100($from): float
    {
        $row = TelematicsRecord::query()
            ->where('recorded_at', '>=', $from)
            ->selectRaw('COALESCE(SUM(distance_km), 0) as distance')
            ->selectRaw('COALESCE(SUM(fuel_consumed_l), 0) as fuel')
            ->first();

        $distance = (float) $row->distance;

        return $distance > 0 ? round(((float) $row->fuel / $distance) * 100, 1) : 0;
    }
}
