<?php

namespace App\Http\Controllers;

use App\Enums\RiskLevel;
use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\Driver;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $vehicles = Vehicle::query()
            ->with(['driver', 'latestRisk'])
            ->when($request->string('q')->trim()->isNotEmpty(), function ($query) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('registration_number', 'like', $term)
                        ->orWhere('make', 'like', $term)
                        ->orWhere('model', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($query) => $query->where('vehicle_type', $request->string('type')))
            ->when($request->filled('risk'), function ($query) use ($request) {
                $query->whereHas('latestRisk', fn ($risk) => $risk->where('level', $request->string('risk')));
            })
            ->orderBy('registration_number')
            ->paginate(12)
            ->withQueryString();

        return view('vehicles.index', [
            'vehicles' => $vehicles,
            'statuses' => VehicleStatus::cases(),
            'types' => VehicleType::cases(),
            'riskLevels' => RiskLevel::cases(),
        ]);
    }

    public function create(): View
    {
        return view('vehicles.form', [
            'vehicle' => new Vehicle,
            'drivers' => Driver::query()->orderBy('last_name')->get(),
            'statuses' => VehicleStatus::cases(),
            'types' => VehicleType::cases(),
        ]);
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        Vehicle::query()->create($request->validated());

        return redirect()->route('vehicles.index')->with('status', 'Vehicle added to the demo fleet.');
    }

    public function show(Vehicle $vehicle): View
    {
        $vehicle->load(['driver', 'latestRisk', 'maintenanceRecords', 'alerts']);

        return view('vehicles.show', compact('vehicle'));
    }

    public function edit(Vehicle $vehicle): View
    {
        return view('vehicles.form', [
            'vehicle' => $vehicle,
            'drivers' => Driver::query()->orderBy('last_name')->get(),
            'statuses' => VehicleStatus::cases(),
            'types' => VehicleType::cases(),
        ]);
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($request->validated());

        return redirect()->route('vehicles.show', $vehicle)->with('status', 'Vehicle details updated.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->delete();

        return redirect()->route('vehicles.index')->with('status', 'Vehicle removed from the demo fleet.');
    }
}
