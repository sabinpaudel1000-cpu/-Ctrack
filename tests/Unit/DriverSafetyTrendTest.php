<?php

namespace Tests\Unit;

use App\Enums\RiskLevel;
use App\Services\Predictions\DriverSafetyTrend;
use PHPUnit\Framework\TestCase;

class DriverSafetyTrendTest extends TestCase
{
    public function test_steady_high_score_is_low_risk(): void
    {
        $result = (new DriverSafetyTrend)->predict($this->input(92, 0, 0, 0, 0, 0, 0));

        $this->assertSame(RiskLevel::Low, $result['level']);
        $this->assertSame(92, $result['outlook_score']);
        $this->assertSame(
            'Safety score is 92 and harsh events are not rising, so the next 30 days look low risk.',
            $result['reason'],
        );
    }

    public function test_rising_braking_drops_the_outlook_to_medium(): void
    {
        // 8 harsh brakes * 1.5 = 12. Outlook = 70 - 12 = 58, which is MEDIUM.
        $result = (new DriverSafetyTrend)->predict($this->input(70, 0, 0, 0, 8, 0, 0));

        $this->assertSame(RiskLevel::Medium, $result['level']);
        $this->assertSame(58, $result['outlook_score']);
        $this->assertSame(
            'Safety score is 70, but harsh braking rose from 0 to 8. The next 30 days look medium risk.',
            $result['reason'],
        );
    }

    public function test_rising_speeding_on_a_weak_score_is_high_risk(): void
    {
        // 4 speeding events * 2.5 = 10. Outlook = 40 - 10 = 30, which is HIGH.
        $result = (new DriverSafetyTrend)->predict($this->input(40, 0, 0, 0, 0, 0, 4));

        $this->assertSame(RiskLevel::High, $result['level']);
        $this->assertSame(30, $result['outlook_score']);
        $this->assertSame(
            'Safety score is 40, but speeding rose from 0 to 4. The next 30 days look high risk.',
            $result['reason'],
        );
    }

    public function test_a_weak_score_stays_high_risk_when_events_are_flat(): void
    {
        $result = (new DriverSafetyTrend)->predict($this->input(30, 1, 1, 1, 1, 1, 1));

        $this->assertSame(RiskLevel::High, $result['level']);
        $this->assertSame(30, $result['outlook_score']);
        $this->assertSame(
            'Safety score is 30 and harsh events are not rising, so the next 30 days look high risk.',
            $result['reason'],
        );
    }

    public function test_falling_events_are_not_added_back_onto_the_score(): void
    {
        // Earlier speeding was worse. The outlook stays at the current score of 60 (MEDIUM).
        $result = (new DriverSafetyTrend)->predict($this->input(60, 0, 0, 8, 0, 0, 0));

        $this->assertSame(RiskLevel::Medium, $result['level']);
        $this->assertSame(60, $result['outlook_score']);
    }

    /**
     * @return array<string, int>
     */
    private function input(
        int $score,
        int $earlierBraking,
        int $earlierAcceleration,
        int $earlierSpeeding,
        int $recentBraking,
        int $recentAcceleration,
        int $recentSpeeding,
    ): array {
        return [
            'current_score' => $score,
            'earlier_braking' => $earlierBraking,
            'earlier_acceleration' => $earlierAcceleration,
            'earlier_speeding' => $earlierSpeeding,
            'recent_braking' => $recentBraking,
            'recent_acceleration' => $recentAcceleration,
            'recent_speeding' => $recentSpeeding,
        ];
    }
}
