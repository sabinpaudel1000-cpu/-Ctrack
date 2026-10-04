<?php

namespace App\Services\Analytics;

use App\Models\TelematicsRecord;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class DriverInsights
{
    /**
     * Safety score from the last $days of telematics (30 unless the analytics page asks for 7).
     * Starts at 100 and subtracts weighted event counts. Not a trained model.
     *
     * @return array{
     *     score: int,
     *     distance_km: float,
     *     avg_speed: float,
     *     harsh_braking: int,
     *     harsh_acceleration: int,
     *     speeding: int
     * }
     */
    public function forDriver(int $driverId, ?CarbonInterface $now = null, int $days = 30): array
    {
        $now ??= now();

        // toBase() keeps the SUM as a number. On the model, harsh_braking is a true/false cast,
        // so two events would become true and then count as 1.
        $row = TelematicsRecord::query()
            ->where('driver_id', $driverId)
            ->where('recorded_at', '>=', $now->copy()->subDays($days))
            ->selectRaw('COALESCE(SUM(distance_km), 0) as distance_km')
            ->selectRaw('COALESCE(AVG(speed), 0) as avg_speed')
            ->selectRaw('COALESCE(SUM(CASE WHEN harsh_braking = 1 THEN 1 ELSE 0 END), 0) as harsh_braking')
            ->selectRaw('COALESCE(SUM(CASE WHEN harsh_acceleration = 1 THEN 1 ELSE 0 END), 0) as harsh_acceleration')
            ->selectRaw('COALESCE(SUM(CASE WHEN speeding = 1 THEN 1 ELSE 0 END), 0) as speeding')
            ->toBase()
            ->first();

        return $this->summarise($row);
    }

    /**
     * @return Collection<int, array{driver_id: int, score: int, harsh_braking: int, harsh_acceleration: int, speeding: int, distance_km: float}>
     */
    public function forDrivers(array $driverIds, ?CarbonInterface $now = null, int $days = 30): Collection
    {
        $now ??= now();

        if ($driverIds === []) {
            return collect();
        }

        $rows = TelematicsRecord::query()
            ->whereIn('driver_id', $driverIds)
            ->where('recorded_at', '>=', $now->copy()->subDays($days))
            ->selectRaw('driver_id')
            ->selectRaw('COALESCE(SUM(distance_km), 0) as distance_km')
            ->selectRaw('COALESCE(AVG(speed), 0) as avg_speed')
            ->selectRaw('COALESCE(SUM(CASE WHEN harsh_braking = 1 THEN 1 ELSE 0 END), 0) as harsh_braking')
            ->selectRaw('COALESCE(SUM(CASE WHEN harsh_acceleration = 1 THEN 1 ELSE 0 END), 0) as harsh_acceleration')
            ->selectRaw('COALESCE(SUM(CASE WHEN speeding = 1 THEN 1 ELSE 0 END), 0) as speeding')
            ->groupBy('driver_id')
            ->toBase()
            ->get()
            ->keyBy('driver_id');

        return collect($driverIds)->mapWithKeys(function (int $id) use ($rows) {
            $summary = $this->summarise($rows->get($id));
            $summary['driver_id'] = $id;

            return [$id => $summary];
        });
    }

    public function scoreFromCounts(int $harshBraking, int $harshAcceleration, int $speeding): int
    {
        $raw = 100 - ($harshBraking * 1.5) - ($harshAcceleration * 1.5) - ($speeding * 2.5);

        return (int) max(0, min(100, round($raw)));
    }

    private function summarise(mixed $row): array
    {
        $harshBraking = (int) ($row->harsh_braking ?? 0);
        $harshAcceleration = (int) ($row->harsh_acceleration ?? 0);
        $speeding = (int) ($row->speeding ?? 0);

        return [
            'score' => $this->scoreFromCounts($harshBraking, $harshAcceleration, $speeding),
            'distance_km' => round((float) ($row->distance_km ?? 0), 1),
            'avg_speed' => round((float) ($row->avg_speed ?? 0), 1),
            'harsh_braking' => $harshBraking,
            'harsh_acceleration' => $harshAcceleration,
            'speeding' => $speeding,
        ];
    }
}
