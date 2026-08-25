<?php

namespace App\Http\Controllers;

use App\Enums\RiskLevel;
use App\Models\MaintenanceRisk;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenanceRiskController extends Controller
{
    public function index(Request $request): View
    {
        $vehicles = Vehicle::query()
            ->with(['driver', 'latestRisk'])
            ->whereHas('latestRisk', function ($query) use ($request) {
                if ($request->filled('level')) {
                    $query->where('level', $request->string('level'));
                }
            })
            ->get()
            ->sortByDesc(fn (Vehicle $vehicle) => $vehicle->latestRisk?->score)
            ->values();

        return view('risks.index', [
            'vehicles' => $vehicles,
            'levels' => RiskLevel::cases(),
        ]);
    }

    public function show(Vehicle $vehicle): View
    {
        $vehicle->load(['driver', 'latestRisk', 'maintenanceRecords']);

        $history = MaintenanceRisk::query()
            ->where('vehicle_id', $vehicle->id)
            ->latest('calculated_at')
            ->limit(6)
            ->get();

        return view('risks.show', [
            'vehicle' => $vehicle,
            'history' => $history,
        ]);
    }
}
