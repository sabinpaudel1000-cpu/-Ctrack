<?php

namespace App\Http\Controllers;

use App\Enums\DriverStatus;
use App\Http\Requests\StoreDriverRequest;
use App\Http\Requests\UpdateDriverRequest;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Services\Analytics\DriverInsights;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Driver list, detail, and CRUD.
 *
 * The driver row does not store a vehicle. The vehicles table has driver_id.
 * Create and update save the driver first, then point the chosen vehicle at them.
 */
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
            ->when($request->input('assignment') === 'assigned', fn ($query) => $query->whereHas('vehicle'))
            ->when($request->input('assignment') === 'unassigned', fn ($query) => $query->whereDoesntHave('vehicle'))
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

    public function create(): View
    {
        return view('drivers.form', [
            'driver' => new Driver,
            'vehicles' => $this->assignableVehicles(),
            'statuses' => DriverStatus::cases(),
        ]);
    }

    public function store(StoreDriverRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $driver = Driver::query()->create($this->driverAttributes($validated));
        $this->syncAssignedVehicle($driver, $validated['vehicle_id'] ?? null);

        return redirect()
            ->route('drivers.index')
            ->with('status', 'Driver added to the demo fleet.');
    }

    public function show(Driver $driver, DriverInsights $insights): View
    {
        $driver->load(['vehicle.latestRisk', 'alerts']);

        return view('drivers.show', [
            'driver' => $driver,
            'insight' => $insights->forDriver($driver->id),
        ]);
    }

    public function edit(Driver $driver): View
    {
        $driver->load('vehicle');

        return view('drivers.form', [
            'driver' => $driver,
            'vehicles' => $this->assignableVehicles($driver),
            'statuses' => DriverStatus::cases(),
        ]);
    }

    public function update(UpdateDriverRequest $request, Driver $driver): RedirectResponse
    {
        $validated = $request->validated();

        $driver->update($this->driverAttributes($validated));
        $this->syncAssignedVehicle($driver, $validated['vehicle_id'] ?? null);

        return redirect()
            ->route('drivers.show', $driver)
            ->with('status', 'Driver details updated.');
    }

    public function destroy(Driver $driver): RedirectResponse
    {
        $registration = $driver->vehicle?->registration_number;

        // Unassign first so the vehicle stays in the fleet. The foreign key
        // also uses nullOnDelete, but doing it here keeps the demo easy to explain.
        $driver->vehicle()->update(['driver_id' => null]);
        $driver->delete();

        $message = $registration
            ? "Driver removed. Vehicle {$registration} is now unassigned."
            : 'Driver removed from the demo fleet.';

        return redirect()->route('drivers.index')->with('status', $message);
    }

    /**
     * Drop vehicle_id before saving. It is not a column on drivers.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function driverAttributes(array $validated): array
    {
        unset($validated['vehicle_id']);

        return $validated;
    }

    /**
     * One driver, one vehicle. Clear the old link before writing the new one
     * because vehicles.driver_id is unique.
     */
    private function syncAssignedVehicle(Driver $driver, mixed $vehicleId): void
    {
        $vehicleId = $vehicleId ? (int) $vehicleId : null;

        $driver->vehicle()
            ->when($vehicleId, fn ($query) => $query->where('id', '!=', $vehicleId))
            ->update(['driver_id' => null]);

        if ($vehicleId) {
            Vehicle::query()->whereKey($vehicleId)->update(['driver_id' => $driver->id]);
        }
    }

    /**
     * The form lists free vehicles, plus the vehicle this driver already has.
     */
    private function assignableVehicles(?Driver $driver = null)
    {
        return Vehicle::query()
            ->where(function ($query) use ($driver) {
                $query->whereNull('driver_id');

                if ($driver?->exists) {
                    $query->orWhere('driver_id', $driver->id);
                }
            })
            ->orderBy('registration_number')
            ->get();
    }
}
