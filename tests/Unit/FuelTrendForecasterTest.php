<?php

namespace Tests\Unit;

use App\Services\Predictions\FuelTrendForecaster;
use PHPUnit\Framework\TestCase;

class FuelTrendForecasterTest extends TestCase
{
    public function test_rising_line_forecasts_24_and_is_flagged_against_a_fleet_of_20(): void
    {
        // Days 10, 12, 14, 16 sit on the line y = 10 + 2x.
        // The next 7 points are 18, 20, 22, 24, 26, 28, 30. Their average is 24.
        // 24 is more than 15% above a fleet average of 20 (threshold 23).
        $result = (new FuelTrendForecaster)->forecast([10, 12, 14, 16], 20);

        $this->assertSame('linear_trend', $result['method']);
        $this->assertSame(24.0, $result['forecast_l_per_100km']);
        $this->assertSame([18.0, 20.0, 22.0, 24.0, 26.0, 28.0, 30.0], $result['daily_forecast']);
        $this->assertTrue($result['flagged']);
    }

    public function test_same_forecast_is_not_flagged_when_it_is_within_15_percent(): void
    {
        // 22 * 1.15 = 25.3, and 24 is not above that.
        $result = (new FuelTrendForecaster)->forecast([10, 12, 14, 16], 22);

        $this->assertSame(24.0, $result['forecast_l_per_100km']);
        $this->assertFalse($result['flagged']);
    }

    public function test_one_day_uses_a_moving_average(): void
    {
        $result = (new FuelTrendForecaster)->forecast([18], 10);

        $this->assertSame('moving_average', $result['method']);
        $this->assertSame(18.0, $result['forecast_l_per_100km']);
        $this->assertSame([18.0, 18.0, 18.0, 18.0, 18.0, 18.0, 18.0], $result['daily_forecast']);
        $this->assertTrue($result['flagged']);
    }

    public function test_a_falling_line_is_clamped_at_zero(): void
    {
        // 30 then 10 is a steep drop. The straight line would go negative. Fuel use cannot.
        $result = (new FuelTrendForecaster)->forecast([30, 10], 10);

        $this->assertSame('linear_trend', $result['method']);
        $this->assertSame(0.0, $result['forecast_l_per_100km']);
        $this->assertSame([0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0], $result['daily_forecast']);
        $this->assertFalse($result['flagged']);
    }

    public function test_no_days_has_no_forecast(): void
    {
        $result = (new FuelTrendForecaster)->forecast([], 10);

        $this->assertSame('none', $result['method']);
        $this->assertSame(0.0, $result['forecast_l_per_100km']);
        $this->assertSame([], $result['daily_forecast']);
        $this->assertFalse($result['flagged']);
    }
}
