<?php

namespace App\Providers;

use App\Services\Predictions\Contracts\DriverSafetyPredictor;
use App\Services\Predictions\Contracts\FuelPredictor;
use App\Services\Predictions\DriverSafetyTrend;
use App\Services\Predictions\FuelTrendForecaster;
use App\Services\Risk\Contracts\MaintenanceRiskPredictor;
use App\Services\Risk\WeightedMaintenanceRiskScorer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MaintenanceRiskPredictor::class, WeightedMaintenanceRiskScorer::class);

        // Swap either class for an ML model that implements the same PredictorInterface.
        $this->app->bind(FuelPredictor::class, FuelTrendForecaster::class);
        $this->app->bind(DriverSafetyPredictor::class, DriverSafetyTrend::class);
    }

    public function boot(): void
    {
        //
    }
}
