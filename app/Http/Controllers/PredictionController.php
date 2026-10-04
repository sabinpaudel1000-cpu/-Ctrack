<?php

namespace App\Http\Controllers;

use App\Services\Predictions\PredictionReport;
use Illuminate\View\View;

class PredictionController extends Controller
{
    public function __invoke(PredictionReport $report): View
    {
        return view('predictions.index', [
            'report' => $report->build(),
        ]);
    }
}
