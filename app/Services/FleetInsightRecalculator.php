<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Services\Alerts\AlertGenerator;
use App\Services\Recommendations\RecommendationEngine;
use App\Services\Risk\Contracts\MaintenanceRiskPredictor;
use App\Services\Risk\RiskContextBuilder;

final class FleetInsightRecalculator
{
    public function __construct(
        private readonly RiskContextBuilder $contextBuilder,
        private readonly MaintenanceRiskPredictor $predictor,
        private readonly AlertGenerator $alertGenerator,
        private readonly RecommendationEngine $recommendationEngine,
    ) {}

    /**
     * Two-pass scoring avoids a circular dependency:
     * pass 1 scores without alert counts, pass 2 includes operational alerts.
     *
     * @return array{vehicles: int, alerts: int, recommendations: int}
     */
    public function recalculate(): array
    {
        $this->persistRisks(includeAlerts: false);
        $this->alertGenerator->regenerate();
        $this->persistRisks(includeAlerts: true);
        $alerts = $this->alertGenerator->regenerate();
        $recommendations = $this->recommendationEngine->regenerate();

        return [
            'vehicles' => Vehicle::query()->count(),
            'alerts' => $alerts->count(),
            'recommendations' => $recommendations->count(),
        ];
    }

    private function persistRisks(bool $includeAlerts): void
    {
        $now = now();

        foreach (Vehicle::query()->get() as $vehicle) {
            $input = $this->contextBuilder->build($vehicle, $now, $includeAlerts);
            $result = $this->predictor->predict($input);

            $vehicle->maintenanceRisks()->create([
                'score' => $result->score,
                'level' => $result->level,
                'factors_json' => $result->factors,
                'algorithm_version' => $result->algorithmVersion,
                'calculated_at' => $now,
            ]);
        }
    }
}
