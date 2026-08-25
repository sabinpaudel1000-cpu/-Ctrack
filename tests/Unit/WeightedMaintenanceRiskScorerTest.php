<?php

namespace Tests\Unit;

use App\Enums\RiskLevel;
use App\Services\Risk\RiskInput;
use App\Services\Risk\WeightedMaintenanceRiskScorer;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class WeightedMaintenanceRiskScorerTest extends TestCase
{
    public function test_worked_example_is_high_risk(): void
    {
        $now = Carbon::parse('2026-08-24');
        $result = (new WeightedMaintenanceRiskScorer)->predict(new RiskInput(
            manufactureYear: 2014,
            mileage: 268000,
            lastMaintenanceDate: $now->copy()->subDays(400),
            avgEngineTemp7d: 108.0,
            harshEventsPer100km30d: 6.0,
            openAbnormalAlertCount: 1,
            now: $now,
        ));

        $this->assertSame(77.5, $result->score);
        $this->assertSame(RiskLevel::High, $result->level);
        $this->assertSame('weighted-baseline-v1', $result->algorithmVersion);
        $this->assertCount(6, $result->factors);
    }

    public function test_new_well_maintained_vehicle_is_low_risk(): void
    {
        $now = Carbon::parse('2026-08-24');
        $result = (new WeightedMaintenanceRiskScorer)->predict(new RiskInput(
            manufactureYear: 2024,
            mileage: 12000,
            lastMaintenanceDate: $now->copy()->subDays(20),
            avgEngineTemp7d: 88.0,
            harshEventsPer100km30d: 0.4,
            openAbnormalAlertCount: 0,
            now: $now,
        ));

        $this->assertSame(0.0, $result->score);
        $this->assertSame(RiskLevel::Low, $result->level);
    }
}
