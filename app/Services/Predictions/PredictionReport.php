<?php

namespace App\Services\Predictions;

use App\Enums\RiskLevel;
use App\Models\Driver;
use App\Models\TelematicsRecord;
use App\Models\Vehicle;
use App\Services\Analytics\DriverInsights;
use App\Services\Predictions\Contracts\DriverSafetyPredictor;
use App\Services\Predictions\Contracts\FuelPredictor;
use Illuminate\Support\Carbon;

/**
 * Builds the Predictions page from the last 30 days of telematics.
 * The maths lives in FuelTrendForecaster and DriverSafetyTrend.
 */
final class PredictionReport
{
    /**
     * Fewer than this many days with trips is not a trend. The page says "not enough data".
     */
    public const MINIMUM_DAYS = 15;

    public function __construct(
        private readonly FuelPredictor $fuelForecaster,
        private readonly DriverSafetyPredictor $driverTrend,
        private readonly DriverInsights $driverInsights,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $from = now()->subDays(30);
        $midpoint = now()->subDays(15);

        $records = TelematicsRecord::query()
            ->where('recorded_at', '>=', $from)
            ->orderBy('recorded_at')
            ->get(['vehicle_id', 'driver_id', 'recorded_at', 'distance_km', 'fuel_consumed_l', 'harsh_braking', 'harsh_acceleration', 'speeding']);

        // Total litres ÷ total kilometres, then × 100. A long trip counts for more than a short one.
        $distance = (float) $records->sum('distance_km');
        $fuel = (float) $records->sum('fuel_consumed_l');
        $fleetAverage = $distance > 0 ? round(($fuel / $distance) * 100, 1) : 0.0;

        $needsMore = collect();
        $vehicles = Vehicle::query()->orderBy('registration_number')->get();
        $vehicleRows = $vehicles->map(function (Vehicle $vehicle) use ($records, $fleetAverage, $needsMore) {
            $rates = $this->dailyRates($records->where('vehicle_id', $vehicle->id));

            if (count($rates) < self::MINIMUM_DAYS) {
                $needsMore->push(['name' => $vehicle->registration_number]);

                return null;
            }

            $forecast = $this->fuelForecaster->predict([
                'daily_litres_per_100km' => $rates,
                'fleet_average' => $fleetAverage,
            ]);
            $percentAbove = $fleetAverage > 0
                ? round((($forecast['forecast_l_per_100km'] - $fleetAverage) / $fleetAverage) * 100, 1)
                : 0.0;

            return [
                'vehicle' => $vehicle,
                'method' => $forecast['method'],
                'forecast' => $forecast['forecast_l_per_100km'],
                'fleet_average' => $fleetAverage,
                'percent_above' => $percentAbove,
                'flagged' => $forecast['flagged'],
                'message' => $vehicle->registration_number
                    ." is forecast at {$forecast['forecast_l_per_100km']} L/100 km for the next 7 days, "
                    ."which is more than 15% above the fleet average of {$fleetAverage} L/100 km.",
            ];
        })->filter()->values();

        $flagged = $vehicleRows
            ->filter(fn (array $row) => $row['flagged'])
            ->sortByDesc('percent_above')
            ->values();

        $drivers = Driver::query()->with('vehicle')->orderBy('last_name')->get();
        $insights = $this->driverInsights->forDrivers($drivers->pluck('id')->all());

        $driverRows = $drivers->map(function (Driver $driver) use ($records, $insights, $midpoint, $needsMore) {
            $trips = $records->where('driver_id', $driver->id);

            if ($this->dayCount($trips) < self::MINIMUM_DAYS) {
                $needsMore->push(['name' => $driver->fullName()]);

                return null;
            }

            $earlier = $trips->filter(fn (TelematicsRecord $record) => $record->recorded_at->lt($midpoint));
            $recent = $trips->filter(fn (TelematicsRecord $record) => $record->recorded_at->gte($midpoint));
            $score = (int) ($insights[$driver->id]['score'] ?? $this->driverInsights->forDriver($driver->id)['score']);

            $prediction = $this->driverTrend->predict([
                'current_score' => $score,
                'earlier_braking' => $this->eventCount($earlier, 'harsh_braking'),
                'earlier_acceleration' => $this->eventCount($earlier, 'harsh_acceleration'),
                'earlier_speeding' => $this->eventCount($earlier, 'speeding'),
                'recent_braking' => $this->eventCount($recent, 'harsh_braking'),
                'recent_acceleration' => $this->eventCount($recent, 'harsh_acceleration'),
                'recent_speeding' => $this->eventCount($recent, 'speeding'),
            ]);

            return [
                'driver' => $driver,
                'score' => $score,
                'level' => $prediction['level'],
                'outlook_score' => $prediction['outlook_score'],
                'reason' => $prediction['reason'],
            ];
        })->filter()->values();

        $atRisk = $driverRows
            ->filter(fn (array $row) => $row['level'] !== RiskLevel::Low)
            ->sortBy('outlook_score')
            ->values();

        return [
            'fleet_average' => $fleetAverage,
            'flagged_vehicles' => $flagged,
            'drivers_at_risk' => $atRisk,
            'needs_more_data' => $needsMore->values(),
            'chart' => $this->chart($records, $fleetAverage),
        ];
    }

    /**
     * Fleet chart: actual daily L/100 km, then the next 7 forecast days.
     *
     * @return array{labels: list<string>, actual: list<float|null>, forecast: list<float|null>}
     */
    private function chart($records, float $fleetAverage): array
    {
        $rates = [];
        $labels = [];

        $byDay = $records->groupBy(fn (TelematicsRecord $record) => $record->recorded_at->toDateString())->sortKeys();

        foreach ($byDay as $day => $dayRows) {
            $dayDistance = (float) $dayRows->sum('distance_km');
            if ($dayDistance <= 0) {
                continue;
            }

            $rates[] = round(((float) $dayRows->sum('fuel_consumed_l') / $dayDistance) * 100, 1);
            $labels[] = Carbon::parse($day)->format('j M');
        }

        if (count($rates) < self::MINIMUM_DAYS) {
            return ['labels' => [], 'actual' => [], 'forecast' => []];
        }

        $projection = $this->fuelForecaster->predict([
            'daily_litres_per_100km' => $rates,
            'fleet_average' => $fleetAverage,
        ]);
        $actual = $rates;
        $forecast = array_fill(0, count($rates), null);

        // Start the forecast line on the last actual day so the two lines meet.
        if ($forecast !== []) {
            $forecast[array_key_last($forecast)] = $actual[array_key_last($actual)];
        }

        foreach ($projection['daily_forecast'] as $index => $value) {
            $labels[] = '+'.($index + 1).' day';
            $actual[] = null;
            $forecast[] = $value;
        }

        return [
            'labels' => $labels,
            'actual' => $actual,
            'forecast' => $forecast,
        ];
    }

    /**
     * One L/100 km figure per day that had distance, oldest first.
     *
     * @return list<float>
     */
    private function dailyRates($records): array
    {
        $rates = [];

        $byDay = $records->groupBy(fn (TelematicsRecord $record) => $record->recorded_at->toDateString())->sortKeys();

        foreach ($byDay as $dayRows) {
            $dayDistance = (float) $dayRows->sum('distance_km');
            if ($dayDistance <= 0) {
                continue;
            }

            $rates[] = round(((float) $dayRows->sum('fuel_consumed_l') / $dayDistance) * 100, 1);
        }

        return $rates;
    }

    private function eventCount($records, string $column): int
    {
        return $records->where($column, true)->count();
    }

    private function dayCount($records): int
    {
        return $records
            ->map(fn (TelematicsRecord $record) => $record->recorded_at->toDateString())
            ->unique()
            ->count();
    }
}
