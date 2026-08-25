<?php

namespace App\Http\Controllers;

use App\Services\Analytics\DashboardMetrics;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardMetrics $metrics): View
    {
        return view('dashboard.index', [
            'metrics' => $metrics->snapshot(),
        ]);
    }
}
