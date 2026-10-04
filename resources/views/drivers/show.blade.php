<x-layouts.app :title="$driver->fullName()" subtitle="Driver behaviour from synthetic telematics">
    <div class="flex gap-3 mb-4">
        <a href="{{ route('drivers.edit', $driver) }}" class="rounded-md bg-slate-900 text-white px-4 py-2 text-sm">Edit</a>
        {{-- Delete clears the vehicle assignment in the controller, then removes the driver. --}}
        <form method="POST" action="{{ route('drivers.destroy', $driver) }}" onsubmit="return confirm('Delete this driver? Any assigned vehicle will stay in the fleet and become unassigned.')">
            @csrf @method('DELETE')
            <button class="rounded-md border border-rose-300 text-rose-700 px-4 py-2 text-sm">Delete</button>
        </form>
    </div>
    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <x-kpi label="Safety score" :value="$insight['score']" hint="100 minus weighted events (30 days)" />
        <x-kpi label="Harsh braking" :value="$insight['harsh_braking']" />
        <x-kpi label="Harsh acceleration" :value="$insight['harsh_acceleration']" />
        <x-kpi label="Speeding incidents" :value="$insight['speeding']" />
    </div>
    <div class="bg-white border rounded-xl p-4 text-sm space-y-2">
        <p><span class="text-slate-500">Employee code:</span> {{ $driver->employee_code }}</p>
        <p><span class="text-slate-500">Email:</span> {{ $driver->email ?? '—' }}</p>
        <p><span class="text-slate-500">Phone:</span> {{ $driver->phone ?? '—' }}</p>
        <p>
            <span class="text-slate-500">Licence:</span> {{ $driver->licence_number }}
            (exp {{ optional($driver->licence_expiry)->format('d M Y') ?? 'not set' }})
            @if ($driver->licence_expiry && $driver->licence_expiry->endOfDay()->isPast())
                <span class="ml-2 font-medium text-rose-700">Licence expired</span>
            @endif
        </p>
        <p><span class="text-slate-500">Status:</span> {{ $driver->status->label() }}</p>
        <p><span class="text-slate-500">Assigned vehicle:</span> {{ $driver->vehicle?->displayName() ?? 'None' }}</p>
        <p><span class="text-slate-500">Distance 30d:</span> {{ $insight['distance_km'] }} km · avg speed {{ $insight['avg_speed'] }} km/h</p>
    </div>
</x-layouts.app>
