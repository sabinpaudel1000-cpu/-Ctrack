<?php

namespace App\Http\Controllers;

use App\Services\Analytics\FleetAnalytics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    /** @var list<int> */
    private const PERIODS = [7, 30, 90];

    public function __invoke(Request $request, FleetAnalytics $analytics): View
    {
        $days = $request->integer('days', 30);
        if (! in_array($days, self::PERIODS, true)) {
            $days = 30;
        }

        return view('analytics.index', [
            'analytics' => $analytics->summary($days),
            'periods' => self::PERIODS,
        ]);
    }
}
