<?php

namespace App\Services\Risk;

use App\Enums\RiskLevel;

final class RiskResult
{
    /**
     * @param  list<array{
     *     key: string,
     *     label: string,
     *     raw_value: mixed,
     *     raw_display: string,
     *     band_score: int,
     *     weight: float,
     *     contribution: float
     * }>  $factors
     */
    public function __construct(
        public readonly float $score,
        public readonly RiskLevel $level,
        public readonly array $factors,
        public readonly string $algorithmVersion,
    ) {}
}
