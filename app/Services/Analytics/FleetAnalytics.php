<?php

namespace App\Services\Analytics;

use App\Models\Driver;
use App\Models\TelematicsRecord;
use App\Models\Vehicle;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class FleetAnalytics
{
    public function __construct(
        private readonly DriverInsights $driverInsights,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(int $days = 30): array
    {
        $from = now()->subDays($days);

        // toBase() so SUM(harsh_braking) stays a number. The model would turn it into true/false.
        $totals = TelematicsRecord::query()
            ->where('recorded_at', '>=', $from)
            ->selectRaw('COALESCE(SUM(distance_km), 0) as distance_km')
            ->selectRaw('COALESCE(SUM(fuel_consumed_l), 0) as fuel_consumed_l')
            ->selectRaw('COALESCE(AVG(speed), 0) as avg_speed')
            ->selectRaw('COALESCE(SUM(CASE WHEN harsh_braking = 1 THEN 1 ELSE 0 END), 0) as harsh_braking')
            ->selectRaw('COALESCE(SUM(CASE WHEN harsh_acceleration = 1 THEN 1 ELSE 0 END), 0) as harsh_acceleration')
            ->selectRaw('COALESCE(SUM(CASE WHEN speeding = 1 THEN 1 ELSE 0 END), 0) as speeding')
            ->toBase()
            ->first();

        $distance = (float) $totals->distance_km;
        $fuel = (float) $totals->fuel_consumed_l;

        $byVehicle = TelematicsRecord::query()
            ->where('recorded_at', '>=', $from)
            ->selectRaw('vehicle_id')
            ->selectRaw('COALESCE(SUM(distance_km), 0) as distance_km')
            ->selectRaw('COALESCE(SUM(fuel_consumed_l), 0) as fuel_consumed_l')
            ->selectRaw('COALESCE(AVG(speed), 0) as avg_speed')
            ->selectRaw('COALESCE(SUM(CASE WHEN harsh_braking = 1 THEN 1 ELSE 0 END), 0) as harsh_braking')
            ->selectRaw('COALESCE(SUM(CASE WHEN harsh_acceleration = 1 THEN 1 ELSE 0 END), 0) as harsh_acceleration')
            ->selectRaw('COALESCE(SUM(CASE WHEN speeding = 1 THEN 1 ELSE 0 END), 0) as speeding')
            ->groupBy('vehicle_id')
            ->toBase()
            ->get()
            ->keyBy('vehicle_id');

        $vehicles = Vehicle::query()->with('driver')->orderBy('registration_number')->get();

        $vehicleRows = $vehicles->map(function (Vehicle $vehicle) use ($byVehicle) {
            $row = $byVehicle->get($vehicle->id);
            $distanceKm = (float) ($row->distance_km ?? 0);
            $fuelL = (float) ($row->fuel_consumed_l ?? 0);

            return [
                'vehicle' => $vehicle,
                'distance_km' => round($distanceKm, 1),
                'fuel_consumed_l' => round($fuelL, 1),
                'l_per_100km' => $distanceKm > 0 ? round(($fuelL / $distanceKm) * 100, 1) : 0,
                'avg_speed' => round((float) ($row->avg_speed ?? 0), 1),
                'harsh_braking' => (int) ($row->harsh_braking ?? 0),
                'harsh_acceleration' => (int) ($row->harsh_acceleration ?? 0),
                'speeding' => (int) ($row->speeding ?? 0),
            ];
        });

        $driverIds = Driver::query()->pluck('id')->all();
        $driverInsights = $this->driverInsights->forDrivers($driverIds, now(), $days);
        $drivers = Driver::query()->with('vehicle')->orderBy('last_name')->get();

        $driverRows = $drivers->map(function (Driver $driver) use ($driverInsights, $days) {
            $insight = $driverInsights[$driver->id] ?? $this->driverInsights->forDriver($driver->id, now(), $days);
            $insight['driver'] = $driver;

            return $insight;
        });

        $fuelTrend = $this->dailyFuelTrend($from);

        return [
            'days' => $days,
            'totals' => [
                'distance_km' => round($distance, 1),
                'fuel_consumed_l' => round($fuel, 1),
                'l_per_100km' => $distance > 0 ? round(($fuel / $distance) * 100, 1) : 0,
                'avg_speed' => round((float) $totals->avg_speed, 1),
                'harsh_braking' => (int) $totals->harsh_braking,
                'harsh_acceleration' => (int) $totals->harsh_acceleration,
                'speeding' => (int) $totals->speeding,
            ],
            'vehicles' => $vehicleRows,
            'drivers' => $driverRows,
            'fuel_trend' => $fuelTrend,
        ];
    }

    /**
     * @return Collection<int, array{label: string, value: float}>
     */
    public function dailyFuelTrend(CarbonInterface|\DateTimeInterface $from): Collection
    {
        $rows = TelematicsRecord::query()
            ->where('recorded_at', '>=', $from)
            ->get(['recorded_at', 'fuel_consumed_l', 'distance_km']);

        return $rows
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
    }
}
