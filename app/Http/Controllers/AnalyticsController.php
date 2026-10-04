<?php

namespace App\Http\Controllers;

use App\Services\Analytics\FleetAnalytics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request, FleetAnalytics $analytics): View
    {
        // The page only supports these two windows. Any other ?days= value uses 30.
        $days = (int) $request->input('days', 30);
        if (! in_array($days, [7, 30], true)) {
            $days = 30;
        }

        return view('analytics.index', [
            'analytics' => $analytics->summary($days),
        ]);
    }
}
