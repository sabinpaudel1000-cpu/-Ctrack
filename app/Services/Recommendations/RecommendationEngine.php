<?php

namespace App\Services\Recommendations;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Enums\RecommendationPriority;
use App\Enums\RecommendationStatus;
use App\Models\Alert;
use App\Models\Recommendation;
use Illuminate\Support\Collection;

final class RecommendationEngine
{
    /**
     * Map current alerts to fleet-manager actions. Static copy is never used.
     */
    public function regenerate(): Collection
    {
        Recommendation::query()->delete();

        $created = collect();

        foreach (Alert::query()->with(['vehicle', 'driver'])->get() as $alert) {
            $mapped = $this->map($alert);
            if ($mapped === null) {
                continue;
            }

            $created->push(Recommendation::query()->create([
                'vehicle_id' => $alert->vehicle_id,
                'driver_id' => $alert->driver_id,
                'alert_id' => $alert->id,
                'title' => $mapped['title'],
                'rationale' => $mapped['rationale'],
                'priority' => $mapped['priority'],
                'status' => RecommendationStatus::Pending,
                'generated_at' => now(),
            ]));
        }

        return $created;
    }

    /**
     * @return array{title: string, rationale: string, priority: RecommendationPriority}|null
     */
    private function map(Alert $alert): ?array
    {
        $subject = $alert->vehicle?->registration_number
            ?? $alert->driver?->fullName()
            ?? 'this asset';

        return match ($alert->type) {
            AlertType::HighMaintenanceRisk => $this->maintenanceRecommendation($alert, $subject),
            AlertType::HighFuelConsumption => [
                'title' => "Investigate high fuel consumption on {$subject}",
                'rationale' => $alert->message.' Check tyre pressure, idling, routing and driver behaviour.',
                'priority' => RecommendationPriority::Medium,
            ],
            AlertType::PoorDriverSafety => [
                'title' => 'Review driver behaviour and coaching plan',
                'rationale' => $alert->message.' Arrange a coaching session and monitor the next 14 days of trips.',
                'priority' => RecommendationPriority::High,
            ],
            AlertType::ExcessiveHarshBraking => [
                'title' => "Review braking behaviour for {$subject}",
                'rationale' => $alert->message.' Confirm following distance and fatigue on assigned routes.',
                'priority' => RecommendationPriority::Medium,
            ],
            AlertType::ExcessiveHarshAcceleration => [
                'title' => "Review acceleration behaviour for {$subject}",
                'rationale' => $alert->message.' Coach smoother throttle use to reduce wear and fuel use.',
                'priority' => RecommendationPriority::Medium,
            ],
            AlertType::HighEngineTemperature => [
                'title' => "Inspect the engine cooling system on {$subject}",
                'rationale' => $alert->message.' Check coolant, fans and load before the vehicle returns to service.',
                'priority' => RecommendationPriority::High,
            ],
            AlertType::Speeding => [
                'title' => "Monitor speeding risk on {$subject}",
                'rationale' => $alert->message.' Confirm speed-limit compliance and consider a temporary speed advisory.',
                'priority' => RecommendationPriority::High,
            ],
            default => null,
        };
    }

    /**
     * @return array{title: string, rationale: string, priority: RecommendationPriority}
     */
    private function maintenanceRecommendation(Alert $alert, string $subject): array
    {
        if ($alert->severity === AlertSeverity::High) {
            return [
                'title' => "Schedule a maintenance inspection for {$subject}",
                'rationale' => $alert->message.' Book a workshop inspection before the next dispatch.',
                'priority' => RecommendationPriority::High,
            ];
        }

        return [
            'title' => "Plan a scheduled service for {$subject}",
            'rationale' => $alert->message.' Fit this vehicle into the next service window so the score does not rise to HIGH.',
            'priority' => RecommendationPriority::Medium,
        ];
    }
}
