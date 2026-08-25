<?php

namespace App\Console\Commands;

use App\Services\FleetInsightRecalculator;
use Illuminate\Console\Command;

class RecalculateFleetInsights extends Command
{
    protected $signature = 'fleet:recalculate';

    protected $description = 'Recalculate maintenance risk, alerts and recommendations from current telematics data.';

    public function handle(FleetInsightRecalculator $recalculator): int
    {
        $this->info('Recalculating fleet insights from synthetic telematics...');

        $result = $recalculator->recalculate();

        $this->line("Vehicles scored: {$result['vehicles']}");
        $this->line("Alerts generated: {$result['alerts']}");
        $this->line("Recommendations generated: {$result['recommendations']}");

        return self::SUCCESS;
    }
}
