<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\TelematicsRecord;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TelematicsController extends Controller
{
    public function index(Request $request): View
    {
        $records = TelematicsRecord::query()
            ->with(['vehicle', 'driver'])
            ->when($request->filled('vehicle_id'), fn ($query) => $query->where('vehicle_id', $request->integer('vehicle_id')))
            ->when($request->filled('driver_id'), fn ($query) => $query->where('driver_id', $request->integer('driver_id')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('recorded_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('recorded_at', '<=', $request->date('to')))
            ->when($request->string('event') === 'harsh_braking', fn ($query) => $query->where('harsh_braking', true))
            ->when($request->string('event') === 'harsh_acceleration', fn ($query) => $query->where('harsh_acceleration', true))
            ->when($request->string('event') === 'speeding', fn ($query) => $query->where('speeding', true))
            ->latest('recorded_at')
            ->paginate(25)
            ->withQueryString();

        return view('telematics.index', [
            'records' => $records,
            'vehicles' => Vehicle::query()->orderBy('registration_number')->get(),
            'drivers' => Driver::query()->orderBy('last_name')->get(),
        ]);
    }
}
