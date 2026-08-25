<?php

namespace App\Http\Controllers;

use App\Enums\DriverStatus;
use App\Models\Driver;
use App\Services\Analytics\DriverInsights;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DriverController extends Controller
{
    public function index(Request $request, DriverInsights $insights): View
    {
        $drivers = Driver::query()
            ->with('vehicle')
            ->when($request->string('q')->trim()->isNotEmpty(), function ($query) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('employee_code', 'like', $term)
                        ->orWhere('licence_number', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderBy('last_name')
            ->paginate(12)
            ->withQueryString();

        $scores = $insights->forDrivers($drivers->pluck('id')->all());

        return view('drivers.index', [
            'drivers' => $drivers,
            'scores' => $scores,
            'statuses' => DriverStatus::cases(),
        ]);
    }

    public function show(Driver $driver, DriverInsights $insights): View
    {
        $driver->load(['vehicle.latestRisk', 'alerts']);

        return view('drivers.show', [
            'driver' => $driver,
            'insight' => $insights->forDriver($driver->id),
        ]);
    }
}
