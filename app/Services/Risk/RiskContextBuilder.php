<?php

namespace App\Services\Risk;

use App\Enums\AlertStatus;
use App\Enums\AlertType;
use App\Models\Alert;
use App\Models\TelematicsRecord;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;

final class RiskContextBuilder
{
    public function build(Vehicle $vehicle, Carbon $now = null, bool $includeAlerts = true): RiskInput
    {
        $now ??= now();

        $last7 = TelematicsRecord::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('recorded_at', '>=', $now->copy()->subDays(7))
            ->avg('engine_temperature');

        $last30 = TelematicsRecord::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('recorded_at', '>=', $now->copy()->subDays(30))
            ->selectRaw('COALESCE(SUM(distance_km), 0) as distance_km')
            ->selectRaw('COALESCE(SUM(CASE WHEN harsh_acceleration = 1 THEN 1 ELSE 0 END), 0) as harsh_accel')
            ->selectRaw('COALESCE(SUM(CASE WHEN harsh_braking = 1 THEN 1 ELSE 0 END), 0) as harsh_brake')
            ->first();

        $distance = (float) ($last30->distance_km ?? 0);
        $harshCount = (int) ($last30->harsh_accel ?? 0) + (int) ($last30->harsh_brake ?? 0);
        $harshPer100 = $distance > 0 ? ($harshCount / $distance) * 100 : ($harshCount > 0 ? 100.0 : 0.0);

        $alertCount = 0;
        if ($includeAlerts) {
            $operationalTypes = array_map(
                fn (AlertType $type) => $type->value,
                array_filter(AlertType::cases(), fn (AlertType $type) => $type->isOperational()),
            );

            $alertCount = Alert::query()
                ->where('vehicle_id', $vehicle->id)
                ->where('status', AlertStatus::Open)
                ->whereIn('type', $operationalTypes)
                ->count();
        }

        return new RiskInput(
            manufactureYear: (int) $vehicle->manufacture_year,
            mileage: (int) $vehicle->mileage,
            lastMaintenanceDate: $vehicle->last_maintenance_date,
            avgEngineTemp7d: $last7 !== null ? (float) $last7 : null,
            harshEventsPer100km30d: $harshPer100,
            openAbnormalAlertCount: $alertCount,
            now: $now,
        );
    }
}
