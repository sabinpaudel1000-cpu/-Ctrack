<?php

namespace App\Services\Analytics;

use App\Enums\AlertStatus;
use App\Enums\RiskLevel;
use App\Enums\VehicleStatus;
use App\Models\Alert;
use App\Models\Driver;
use App\Models\TelematicsRecord;
use App\Models\Vehicle;
use Illuminate\Support\Collection;

final class DashboardMetrics
{
    public function __construct(
        private readonly DriverInsights $driverInsights,
    ) {}

    /**
     * All dashboard numbers are queried from the database. Nothing is hard-coded.
     *
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $vehicles = Vehicle::query()->with('latestRisk')->get();
        $total = $vehicles->count();
        $active = $vehicles->where('status', VehicleStatus::Active)->count();
        $inactive = $vehicles->where('status', VehicleStatus::Inactive)->count();

        $highRisk = $vehicles->filter(fn (Vehicle $v) => $v->latestRisk?->level === RiskLevel::High)->count();
        $mediumRisk = $vehicles->filter(fn (Vehicle $v) => $v->latestRisk?->level === RiskLevel::Medium)->count();
        $lowRisk = $vehicles->filter(fn (Vehicle $v) => $v->latestRisk?->level === RiskLevel::Low)->count();

        $openHighAlerts = Alert::query()
            ->where('status', AlertStatus::Open)
            ->whereIn('severity', ['high', 'critical'])
            ->pluck('vehicle_id')
            ->filter()
            ->unique();

        $requiringAttention = $vehicles->filter(function (Vehicle $vehicle) use ($openHighAlerts) {
            return $vehicle->status === VehicleStatus::Maintenance
                || $vehicle->latestRisk?->level === RiskLevel::High
                || $openHighAlerts->contains($vehicle->id);
        })->count();

        $fuel = TelematicsRecord::query()
            ->selectRaw('COALESCE(SUM(fuel_consumed_l), 0) as fuel')
            ->selectRaw('COALESCE(SUM(distance_km), 0) as distance')
            ->first();

        $avgFuel = ((float) $fuel->distance) > 0
            ? round(((float) $fuel->fuel / (float) $fuel->distance) * 100, 1)
            : 0.0;

        $driverIds = Driver::query()->pluck('id')->all();
        $insights = $this->driverInsights->forDrivers($driverIds);
        $avgSafety = $insights->isEmpty()
            ? 0
            : (int) round($insights->avg(fn (array $row) => $row['score']));

        $recentAlerts = Alert::query()
            ->with(['vehicle', 'driver'])
            ->latest('triggered_at')
            ->limit(8)
            ->get();

        $openAlerts = Alert::query()->open()->count();

        $attentionVehicles = $vehicles
            ->filter(function (Vehicle $vehicle) use ($openHighAlerts) {
                return $vehicle->status === VehicleStatus::Maintenance
                    || $vehicle->latestRisk?->level === RiskLevel::High
                    || $openHighAlerts->contains($vehicle->id);
            })
            ->map(function (Vehicle $vehicle) use ($openHighAlerts) {
                $reasons = [];
                if ($vehicle->status === VehicleStatus::Maintenance) {
                    $reasons[] = 'Workshop';
                }
                if ($vehicle->latestRisk?->level === RiskLevel::High) {
                    $reasons[] = 'HIGH risk';
                }
                if ($openHighAlerts->contains($vehicle->id)) {
                    $reasons[] = 'High-severity alert';
                }

                return [
                    'vehicle' => $vehicle,
                    'reasons' => $reasons,
                ];
            })
            ->values();

        return [
            'total_vehicles' => $total,
            'active_vehicles' => $active,
            'inactive_vehicles' => $inactive,
            'vehicles_requiring_attention' => $requiringAttention,
            'open_alerts' => $openAlerts,
            'average_fuel_l_per_100km' => $avgFuel,
            'average_driver_safety_score' => $avgSafety,
            'high_risk_vehicles' => $highRisk,
            'medium_risk_vehicles' => $mediumRisk,
            'low_risk_vehicles' => $lowRisk,
            'attention_vehicles' => $attentionVehicles,
            'recent_alerts' => $recentAlerts,
            'charts' => $this->charts($vehicles, $insights),
        ];
    }

    /**
     * @param  Collection<int, Vehicle>  $vehicles
     * @param  Collection<int, array>  $insights
     * @return array<string, mixed>
     */
    private function charts(Collection $vehicles, Collection $insights): array
    {
        $fuelTrend = TelematicsRecord::query()
            ->where('recorded_at', '>=', now()->subDays(14))
            ->get(['recorded_at', 'fuel_consumed_l', 'distance_km'])
            ->groupBy(fn (TelematicsRecord $record) => $record->recorded_at->toDateString())
            ->sortKeys()
            ->map(function (Collection $dayRows, string $day) {
                $fuel = (float) $dayRows->sum('fuel_consumed_l');
                $distance = (float) $dayRows->sum('distance_km');

                return [
                    'label' => $day,
                    'value' => $distance > 0 ? round(($fuel / $distance) * 100, 1) : 0,
                ];
            })
            ->values();

        $driverScores = Driver::query()
            ->orderBy('last_name')
            ->get()
            ->map(fn (Driver $driver) => [
                'label' => $driver->first_name.' '.$driver->last_name,
                'value' => $insights[$driver->id]['score'] ?? 0,
            ]);

        $distanceByVehicle = TelematicsRecord::query()
            ->where('recorded_at', '>=', now()->subDays(30))
            ->selectRaw('vehicle_id, COALESCE(SUM(distance_km), 0) as distance_km')
            ->groupBy('vehicle_id')
            ->pluck('distance_km', 'vehicle_id');

        $performance = $vehicles
            ->filter(fn (Vehicle $v) => $v->status === VehicleStatus::Active)
            ->map(fn (Vehicle $vehicle) => [
                'label' => $vehicle->registration_number,
                'value' => round((float) ($distanceByVehicle[$vehicle->id] ?? 0), 0),
            ])
            ->sortByDesc('value')
            ->take(8)
            ->values();

        $alertsByType = Alert::query()
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->type instanceof \App\Enums\AlertType
                    ? $row->type->label()
                    : \App\Enums\AlertType::from($row->type)->label(),
                'value' => (int) $row->total,
            ]);

        return [
            'fuel_trend' => $fuelTrend,
            'risk_distribution' => [
                ['label' => 'High', 'value' => $vehicles->filter(fn ($v) => $v->latestRisk?->level === RiskLevel::High)->count()],
                ['label' => 'Medium', 'value' => $vehicles->filter(fn ($v) => $v->latestRisk?->level === RiskLevel::Medium)->count()],
                ['label' => 'Low', 'value' => $vehicles->filter(fn ($v) => $v->latestRisk?->level === RiskLevel::Low)->count()],
            ],
            'driver_safety' => $driverScores,
            'vehicle_performance' => $performance,
            'alerts_by_category' => $alertsByType,
        ];
    }
}
