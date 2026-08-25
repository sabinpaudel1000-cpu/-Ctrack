<?php

namespace App\Services\Risk;

use Carbon\CarbonInterface;

final class RiskInput
{
    public function __construct(
        public readonly int $manufactureYear,
        public readonly int $mileage,
        public readonly ?CarbonInterface $lastMaintenanceDate,
        public readonly ?float $avgEngineTemp7d,
        public readonly float $harshEventsPer100km30d,
        public readonly int $openAbnormalAlertCount,
        public readonly CarbonInterface $now,
    ) {}
}
