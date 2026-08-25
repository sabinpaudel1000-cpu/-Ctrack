<x-layouts.app :title="$vehicle->exists ? 'Edit vehicle' : 'Add vehicle'">
    <form method="POST" action="{{ $vehicle->exists ? route('vehicles.update', $vehicle) : route('vehicles.store') }}"
          class="max-w-3xl bg-white border border-slate-200 rounded-xl p-6 grid sm:grid-cols-2 gap-4">
        @csrf
        @if ($vehicle->exists) @method('PUT') @endif
        @php $v = fn($key, $default = '') => old($key, $vehicle->$key ?? $default); @endphp

        <div>
            <label class="text-sm font-medium">Registration</label>
            <input name="registration_number" value="{{ $v('registration_number') }}" class="mt-1 w-full rounded-md border px-3 py-2" required>
            @error('registration_number') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium">Make</label>
            <input name="make" value="{{ $v('make') }}" class="mt-1 w-full rounded-md border px-3 py-2" required>
        </div>
        <div>
            <label class="text-sm font-medium">Model</label>
            <input name="model" value="{{ $v('model') }}" class="mt-1 w-full rounded-md border px-3 py-2" required>
        </div>
        <div>
            <label class="text-sm font-medium">Type</label>
            <select name="vehicle_type" class="mt-1 w-full rounded-md border px-3 py-2">
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected((string) $v('vehicle_type') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm font-medium">Manufacture year</label>
            <input type="number" name="manufacture_year" value="{{ $v('manufacture_year', 2020) }}" class="mt-1 w-full rounded-md border px-3 py-2" required>
        </div>
        <div>
            <label class="text-sm font-medium">Mileage (km)</label>
            <input type="number" name="mileage" value="{{ $v('mileage', 0) }}" class="mt-1 w-full rounded-md border px-3 py-2" required>
        </div>
        <div>
            <label class="text-sm font-medium">Status</label>
            <select name="status" class="mt-1 w-full rounded-md border px-3 py-2">
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected((string) $v('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm font-medium">Assigned driver</label>
            <select name="driver_id" class="mt-1 w-full rounded-md border px-3 py-2">
                <option value="">Unassigned</option>
                @foreach ($drivers as $driver)
                    <option value="{{ $driver->id }}" @selected((string) $v('driver_id') === (string) $driver->id)>{{ $driver->fullName() }}</option>
                @endforeach
            </select>
            @error('driver_id') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div class="sm:col-span-2">
            <label class="text-sm font-medium">Last maintenance date</label>
            <input type="date" name="last_maintenance_date" value="{{ old('last_maintenance_date', optional($vehicle->last_maintenance_date)->format('Y-m-d')) }}" class="mt-1 w-full rounded-md border px-3 py-2">
        </div>
        <div class="sm:col-span-2 flex gap-3">
            <button class="rounded-md bg-teal-600 text-white px-4 py-2">Save</button>
            <a href="{{ route('vehicles.index') }}" class="rounded-md border px-4 py-2">Cancel</a>
        </div>
    </form>
</x-layouts.app>
