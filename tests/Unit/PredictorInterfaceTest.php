<?php

namespace Tests\Unit;

use App\Services\Predictions\Contracts\PredictorInterface;
use App\Services\Predictions\DriverSafetyTrend;
use App\Services\Predictions\FuelTrendForecaster;
use PHPUnit\Framework\TestCase;

class PredictorInterfaceTest extends TestCase
{
    public function test_both_predictors_can_be_replaced_through_the_same_interface(): void
    {
        $fuel = new FuelTrendForecaster;
        $driver = new DriverSafetyTrend;

        $this->assertInstanceOf(PredictorInterface::class, $fuel);
        $this->assertInstanceOf(PredictorInterface::class, $driver);

        $forecast = $fuel->predict([
            'daily_litres_per_100km' => [10, 12, 14, 16],
            'fleet_average' => 20,
        ]);

        $this->assertSame(24.0, $forecast['forecast_l_per_100km']);
        $this->assertArrayHasKey('level', $driver->predict([
            'current_score' => 92,
            'earlier_braking' => 0,
            'earlier_acceleration' => 0,
            'earlier_speeding' => 0,
            'recent_braking' => 0,
            'recent_acceleration' => 0,
            'recent_speeding' => 0,
        ]));
    }
}
