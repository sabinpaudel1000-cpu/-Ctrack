<?php

namespace App\Services\Risk;

use App\Enums\RiskLevel;
use App\Services\Risk\Contracts\MaintenanceRiskPredictor;

/**
 * Iteration 1 explainable baseline. Not a trained machine-learning model.
 *
 * Score = sum(band_score * weight). Bands and weights live in this class
 * so they can be shown on the risk screen and swapped later via the
 * MaintenanceRiskPredictor contract.
 */
final class WeightedMaintenanceRiskScorer implements MaintenanceRiskPredictor
{
    public const VERSION = 'weighted-baseline-v1';

    public const WEIGHTS = [
        'mileage' => 0.20,
        'days_since_maintenance' => 0.20,
        'vehicle_age' => 0.15,
        'engine_temperature' => 0.15,
        'harsh_events' => 0.15,
        'abnormal_alerts' => 0.15,
    ];

    public function predict(RiskInput $input): RiskResult
    {
        $age = $input->now->year - $input->manufactureYear;
        $daysSinceService = $input->lastMaintenanceDate
            ? (int) round($input->lastMaintenanceDate->diffInDays($input->now))
            : null;

        $factors = [
            $this->factor(
                key: 'mileage',
                label: 'Vehicle mileage',
                rawValue: $input->mileage,
                rawDisplay: number_format($input->mileage).' km',
                bandScore: $this->mileageScore($input->mileage),
            ),
            $this->factor(
                key: 'days_since_maintenance',
                label: 'Days since last maintenance',
                rawValue: $daysSinceService,
                rawDisplay: $daysSinceService === null ? 'No service recorded' : $daysSinceService.' days',
                bandScore: $this->maintenanceScore($daysSinceService),
            ),
            $this->factor(
                key: 'vehicle_age',
                label: 'Vehicle age',
                rawValue: $age,
                rawDisplay: $age.' years',
                bandScore: $this->ageScore($age),
            ),
            $this->factor(
                key: 'engine_temperature',
                label: '7-day average engine temperature',
                rawValue: $input->avgEngineTemp7d,
                rawDisplay: $input->avgEngineTemp7d === null
                    ? 'No recent telematics'
                    : round($input->avgEngineTemp7d, 1).' °C',
                bandScore: $this->temperatureScore($input->avgEngineTemp7d),
            ),
            $this->factor(
                key: 'harsh_events',
                label: 'Harsh events per 100 km (30 days)',
                rawValue: $input->harshEventsPer100km30d,
                rawDisplay: round($input->harshEventsPer100km30d, 1).' / 100 km',
                bandScore: $this->harshScore($input->harshEventsPer100km30d),
            ),
            $this->factor(
                key: 'abnormal_alerts',
                label: 'Open operational alerts',
                rawValue: $input->openAbnormalAlertCount,
                rawDisplay: $input->openAbnormalAlertCount.' open',
                bandScore: $this->alertScore($input->openAbnormalAlertCount),
            ),
        ];

        $score = round(array_sum(array_column($factors, 'contribution')), 1);

        return new RiskResult(
            score: $score,
            level: RiskLevel::fromScore($score),
            factors: $factors,
            algorithmVersion: self::VERSION,
        );
    }

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     raw_value: mixed,
     *     raw_display: string,
     *     band_score: int,
     *     weight: float,
     *     contribution: float
     * }
     */
    private function factor(string $key, string $label, mixed $rawValue, string $rawDisplay, int $bandScore): array
    {
        $weight = self::WEIGHTS[$key];

        return [
            'key' => $key,
            'label' => $label,
            'raw_value' => $rawValue,
            'raw_display' => $rawDisplay,
            'band_score' => $bandScore,
            'weight' => $weight,
            'contribution' => round($bandScore * $weight, 1),
        ];
    }

    private function mileageScore(int $mileage): int
    {
        return match (true) {
            $mileage < 50_000 => 0,
            $mileage < 150_000 => 40,
            $mileage < 250_000 => 70,
            default => 100,
        };
    }

    private function maintenanceScore(?int $days): int
    {
        if ($days === null) {
            return 100;
        }

        return match (true) {
            $days < 90 => 0,
            $days < 180 => 40,
            $days < 365 => 70,
            default => 100,
        };
    }

    private function ageScore(int $years): int
    {
        return match (true) {
            $years <= 3 => 0,
            $years <= 7 => 40,
            $years <= 12 => 70,
            default => 100,
        };
    }

    private function temperatureScore(?float $celsius): int
    {
        if ($celsius === null) {
            return 0;
        }

        return match (true) {
            $celsius < 90 => 0,
            $celsius < 100 => 40,
            $celsius < 110 => 70,
            default => 100,
        };
    }

    private function harshScore(float $eventsPer100km): int
    {
        return match (true) {
            $eventsPer100km <= 2 => 0,
            $eventsPer100km <= 5 => 40,
            $eventsPer100km <= 10 => 70,
            default => 100,
        };
    }

    private function alertScore(int $count): int
    {
        return match (true) {
            $count <= 0 => 0,
            $count <= 2 => 40,
            default => 100,
        };
    }
}
