<?php

namespace App\Services\Predictions;

use App\Enums\RiskLevel;
use App\Services\Predictions\Contracts\DriverSafetyPredictor;

/**
 * Next-30-day driver risk from the current safety score and the event trend.
 * This is a statistical trend model, not deep learning.
 *
 * Split the last 30 days in half. Weight events the same way as DriverInsights:
 * harsh braking 1.5, harsh acceleration 1.5, speeding 2.5.
 * Subtract only the amount the latest 15 days got worse. A fall in events is not added back.
 * Outlook 75+ is LOW, 50–74 is MEDIUM, below 50 is HIGH.
 */
final class DriverSafetyTrend implements DriverSafetyPredictor
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{level: RiskLevel, outlook_score: int, reason: string}
     */
    public function predict(array $input): array
    {
        return $this->fromCounts(
            (int) ($input['current_score'] ?? 0),
            (int) ($input['earlier_braking'] ?? 0),
            (int) ($input['earlier_acceleration'] ?? 0),
            (int) ($input['earlier_speeding'] ?? 0),
            (int) ($input['recent_braking'] ?? 0),
            (int) ($input['recent_acceleration'] ?? 0),
            (int) ($input['recent_speeding'] ?? 0),
        );
    }

    /**
     * @return array{level: RiskLevel, outlook_score: int, reason: string}
     */
    public function fromCounts(
        int $currentScore,
        int $earlierBraking,
        int $earlierAcceleration,
        int $earlierSpeeding,
        int $recentBraking,
        int $recentAcceleration,
        int $recentSpeeding,
    ): array {
        $worsening = max(0, $this->weight($recentBraking, $recentAcceleration, $recentSpeeding)
            - $this->weight($earlierBraking, $earlierAcceleration, $earlierSpeeding));

        $outlook = (int) round(max(0, min(100, $currentScore - $worsening)));

        $level = match (true) {
            $outlook >= 75 => RiskLevel::Low,
            $outlook >= 50 => RiskLevel::Medium,
            default => RiskLevel::High,
        };

        return [
            'level' => $level,
            'outlook_score' => $outlook,
            'reason' => $this->reason(
                $currentScore,
                $level,
                $earlierBraking,
                $earlierAcceleration,
                $earlierSpeeding,
                $recentBraking,
                $recentAcceleration,
                $recentSpeeding,
            ),
        ];
    }

    private function weight(int $braking, int $acceleration, int $speeding): float
    {
        return ($braking * 1.5) + ($acceleration * 1.5) + ($speeding * 2.5);
    }

    private function reason(
        int $currentScore,
        RiskLevel $level,
        int $earlierBraking,
        int $earlierAcceleration,
        int $earlierSpeeding,
        int $recentBraking,
        int $recentAcceleration,
        int $recentSpeeding,
    ): string {
        $changes = [];

        if ($recentBraking > $earlierBraking) {
            $changes[] = "harsh braking rose from {$earlierBraking} to {$recentBraking}";
        }

        if ($recentAcceleration > $earlierAcceleration) {
            $changes[] = "harsh acceleration rose from {$earlierAcceleration} to {$recentAcceleration}";
        }

        if ($recentSpeeding > $earlierSpeeding) {
            $changes[] = "speeding rose from {$earlierSpeeding} to {$recentSpeeding}";
        }

        $levelWord = strtolower($level->label());

        if ($changes === []) {
            return "Safety score is {$currentScore} and harsh events are not rising, so the next 30 days look {$levelWord} risk.";
        }

        return "Safety score is {$currentScore}, but ".implode(', ', $changes).". The next 30 days look {$levelWord} risk.";
    }
}
