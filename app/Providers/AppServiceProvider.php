<?php

namespace App\Providers;

use App\Services\Risk\Contracts\MaintenanceRiskPredictor;
use App\Services\Risk\WeightedMaintenanceRiskScorer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MaintenanceRiskPredictor::class, WeightedMaintenanceRiskScorer::class);
    }

    public function boot(): void
    {
        //
    }
}
