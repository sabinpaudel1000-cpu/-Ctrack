<?php

namespace App\Http\Controllers;

use App\Services\Analytics\FleetAnalytics;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __invoke(FleetAnalytics $analytics): View
    {
        return view('analytics.index', [
            'analytics' => $analytics->summary(30),
        ]);
    }
}
