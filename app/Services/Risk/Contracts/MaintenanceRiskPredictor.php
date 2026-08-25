<?php

namespace App\Services\Risk\Contracts;

use App\Services\Risk\RiskInput;
use App\Services\Risk\RiskResult;

interface MaintenanceRiskPredictor
{
    public function predict(RiskInput $input): RiskResult;
}
