<x-layouts.app title="Telematics" subtitle="Synthetic GPS and vehicle pings — not live Ctrack feeds">
    <form class="mb-4 flex flex-wrap gap-3" method="GET">
        <select name="vehicle_id" class="rounded-md border px-3 py-2">
            <option value="">All vehicles</option>
            @foreach ($vehicles as $vehicle)
                <option value="{{ $vehicle->id }}" @selected(request('vehicle_id') == $vehicle->id)>{{ $vehicle->registration_number }}</option>
            @endforeach
        </select>
        <select name="driver_id" class="rounded-md border px-3 py-2">
            <option value="">All drivers</option>
            @foreach ($drivers as $driver)
                <option value="{{ $driver->id }}" @selected(request('driver_id') == $driver->id)>{{ $driver->fullName() }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="rounded-md border px-3 py-2">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded-md border px-3 py-2">
        <select name="event" class="rounded-md border px-3 py-2">
            <option value="">All events</option>
            <option value="harsh_braking" @selected(request('event')==='harsh_braking')>Harsh braking</option>
            <option value="harsh_acceleration" @selected(request('event')==='harsh_acceleration')>Harsh acceleration</option>
            <option value="speeding" @selected(request('event')==='speeding')>Speeding</option>
        </select>
        <button class="rounded-md bg-slate-900 text-white px-4">Filter</button>
    </form>
    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-3 py-2">Time</th>
                    <th class="px-3 py-2">Vehicle</th>
                    <th class="px-3 py-2">Driver</th>
                    <th class="px-3 py-2">Speed</th>
                    <th class="px-3 py-2">Distance</th>
                    <th class="px-3 py-2">Fuel L</th>
                    <th class="px-3 py-2">Temp</th>
                    <th class="px-3 py-2">Events</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($records as $record)
                    <tr class="border-t">
                        <td class="px-3 py-2 whitespace-nowrap">{{ $record->recorded_at->format('d M H:i') }}</td>
                        <td class="px-3 py-2">{{ $record->vehicle?->registration_number }}</td>
                        <td class="px-3 py-2">{{ $record->driver?->fullName() ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $record->speed }} km/h</td>
                        <td class="px-3 py-2">{{ $record->distance_km }}</td>
                        <td class="px-3 py-2">{{ $record->fuel_consumed_l }}</td>
                        <td class="px-3 py-2">{{ $record->engine_temperature }}°C</td>
                        <td class="px-3 py-2">
                            @if ($record->harsh_braking) <x-badge tone="warning">Brake</x-badge> @endif
                            @if ($record->harsh_acceleration) <x-badge tone="warning">Accel</x-badge> @endif
                            @if ($record->speeding) <x-badge tone="danger">Speed</x-badge> @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $records->links() }}</div>
</x-layouts.app>
