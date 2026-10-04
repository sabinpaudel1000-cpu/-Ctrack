<?php

namespace App\Services\Predictions;

use App\Services\Predictions\Contracts\FuelPredictor;

/**
 * Next-7-day fuel rate from daily L/100 km figures.
 * This is a statistical trend model, not deep learning.
 *
 * Two or more days: draw a straight line through the days (oldest first)
 * and average the next 7 points on that line.
 * One day: the moving average is that day, repeated for the next 7 days.
 * A falling line is not allowed to forecast a negative fuel rate.
 */
final class FuelTrendForecaster implements FuelPredictor
{
    /**
     * A vehicle is flagged when its forecast is more than 15% above the fleet average.
     */
    public const ABOVE_FLEET_RATIO = 1.15;

    /**
     * @param  array<string, mixed>  $input  daily_litres_per_100km, fleet_average
     * @return array<string, mixed>
     */
    public function predict(array $input): array
    {
        return $this->forecast(
            $input['daily_litres_per_100km'] ?? [],
            (float) ($input['fleet_average'] ?? 0),
        );
    }

    /**
     * @param  list<float|int>  $dailyLitresPer100km  oldest day first, only days that had trips
     * @return array{
     *     method: string,
     *     forecast_l_per_100km: float,
     *     daily_forecast: list<float>,
     *     flagged: bool
     * }
     */
    public function forecast(array $dailyLitresPer100km, float $fleetAverage): array
    {
        $rates = array_values(array_map(static fn ($value) => (float) $value, $dailyLitresPer100km));
        $count = count($rates);

        if ($count === 0) {
            return [
                'method' => 'none',
                'forecast_l_per_100km' => 0.0,
                'daily_forecast' => [],
                'flagged' => false,
            ];
        }

        if ($count === 1) {
            $day = round(max(0, $rates[0]), 1);
            $daily = array_fill(0, 7, $day);
            $method = 'moving_average';
        } else {
            [$slope, $intercept] = $this->straightLine($rates);
            $daily = [];

            // x = 0 is the oldest day. The next 7 days continue after the last point.
            // max(0, ...) stops a falling line from forecasting negative litres.
            for ($step = 0; $step < 7; $step++) {
                $predicted = $intercept + ($slope * ($count + $step));
                $daily[] = round(max(0, $predicted), 1);
            }

            $method = 'linear_trend';
        }

        $forecast = round(max(0, array_sum($daily) / 7), 1);

        return [
            'method' => $method,
            'forecast_l_per_100km' => $forecast,
            'daily_forecast' => $daily,
            'flagged' => $fleetAverage > 0 && $forecast > ($fleetAverage * self::ABOVE_FLEET_RATIO),
        ];
    }

    /**
     * Ordinary straight line: y = intercept + slope * x.
     *
     * @param  list<float>  $rates
     * @return array{0: float, 1: float}
     */
    private function straightLine(array $rates): array
    {
        $count = count($rates);
        $sumX = 0.0;
        $sumY = 0.0;
        $sumXY = 0.0;
        $sumXSquared = 0.0;

        foreach ($rates as $x => $y) {
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumXSquared += $x * $x;
        }

        $denominator = ($count * $sumXSquared) - ($sumX * $sumX);
        $slope = (($count * $sumXY) - ($sumX * $sumY)) / $denominator;
        $intercept = ($sumY - ($slope * $sumX)) / $count;

        return [$slope, $intercept];
    }
}
