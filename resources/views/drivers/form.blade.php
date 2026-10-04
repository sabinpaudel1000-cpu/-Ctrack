{{-- One form for create and edit. The action switches to PUT when the driver already exists. --}}
<x-layouts.app :title="$driver->exists ? 'Edit driver' : 'Add driver'">
    @if ($errors->any())
        <div class="mb-4 max-w-3xl rounded-md bg-rose-50 text-rose-800 px-4 py-3 text-sm">Please correct the highlighted fields.</div>
    @endif
    <form method="POST" action="{{ $driver->exists ? route('drivers.update', $driver) : route('drivers.store') }}"
          class="max-w-3xl bg-white border border-slate-200 rounded-xl p-6 grid sm:grid-cols-2 gap-4">
        @csrf
        @if ($driver->exists) @method('PUT') @endif
        @php
            $v = fn($key, $default = '') => old($key, $driver->$key ?? $default);
            $inputClass = fn($key) => 'mt-1 w-full rounded-md border px-3 py-2'.($errors->has($key) ? ' border-rose-500' : ' border-slate-300');
        @endphp

        <div>
            <label class="text-sm font-medium">Employee code</label>
            <input name="employee_code" value="{{ $v('employee_code') }}" class="{{ $inputClass('employee_code') }}" required>
            <p class="mt-1 text-xs text-slate-500">Stored in uppercase. Must be unique.</p>
            @error('employee_code') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium">Status</label>
            <select name="status" class="{{ $inputClass('status') }}">
                @foreach ($statuses as $status)
                    {{-- status is an enum on a saved driver, so compare the stored value, not the object. --}}
                    <option value="{{ $status->value }}" @selected(old('status', $driver->status?->value) === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            @error('status') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium">First name</label>
            <input name="first_name" value="{{ $v('first_name') }}" class="{{ $inputClass('first_name') }}" required>
            @error('first_name') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium">Last name</label>
            <input name="last_name" value="{{ $v('last_name') }}" class="{{ $inputClass('last_name') }}" required>
            @error('last_name') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium">Email</label>
            <input type="email" name="email" value="{{ $v('email') }}" class="{{ $inputClass('email') }}">
            @error('email') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium">Phone</label>
            <input name="phone" value="{{ $v('phone') }}" class="{{ $inputClass('phone') }}">
            @error('phone') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium">Licence number</label>
            <input name="licence_number" value="{{ $v('licence_number') }}" class="{{ $inputClass('licence_number') }}" required>
            @error('licence_number') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium">Licence expiry</label>
            <input type="date" name="licence_expiry" value="{{ old('licence_expiry', optional($driver->licence_expiry)->format('Y-m-d')) }}" class="{{ $inputClass('licence_expiry') }}">
            @error('licence_expiry') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium">Hire date</label>
            <input type="date" name="hire_date" max="{{ now()->toDateString() }}" value="{{ old('hire_date', optional($driver->hire_date)->format('Y-m-d')) }}" class="{{ $inputClass('hire_date') }}">
            @error('hire_date') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium">Assigned vehicle</label>
            <select name="vehicle_id" class="{{ $inputClass('vehicle_id') }}">
                <option value="">Unassigned</option>
                @foreach ($vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}" @selected((string) old('vehicle_id', $driver->vehicle?->id) === (string) $vehicle->id)>{{ $vehicle->displayName() }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-slate-500">Only unassigned vehicles are listed. Inactive or suspended drivers cannot be assigned a vehicle.</p>
            @error('vehicle_id') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div class="sm:col-span-2 flex gap-3">
            <button class="rounded-md bg-teal-600 text-white px-4 py-2">Save</button>
            <a href="{{ $driver->exists ? route('drivers.show', $driver) : route('drivers.index') }}" class="rounded-md border px-4 py-2">Cancel</a>
        </div>
    </form>
</x-layouts.app>
