<x-layouts.app title="Drivers" subtitle="Safety indicators calculated from 30-day telematics">
    <form class="mb-4 flex flex-wrap gap-3" method="GET">
        <input name="q" value="{{ request('q') }}" placeholder="Search name or employee code" class="rounded-md border px-3 py-2 w-64">
        <select name="status" class="rounded-md border px-3 py-2">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <select name="assignment" class="rounded-md border px-3 py-2">
            <option value="">All assignments</option>
            <option value="assigned" @selected(request('assignment') === 'assigned')>Assigned</option>
            <option value="unassigned" @selected(request('assignment') === 'unassigned')>Unassigned</option>
        </select>
        <button class="rounded-md bg-slate-900 text-white px-4">Filter</button>
        <a href="{{ route('drivers.create') }}" class="rounded-md bg-teal-600 text-white px-4 py-2">Add driver</a>
    </form>
    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Driver</th>
                    <th class="px-4 py-2">Vehicle</th>
                    <th class="px-4 py-2">Safety score</th>
                    <th class="px-4 py-2">Harsh brake</th>
                    <th class="px-4 py-2">Harsh accel</th>
                    <th class="px-4 py-2">Speeding</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($drivers as $driver)
                    @php $s = $scores[$driver->id] ?? ['score'=>0,'harsh_braking'=>0,'harsh_acceleration'=>0,'speeding'=>0]; @endphp
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $driver->fullName() }} <span class="text-slate-400">{{ $driver->employee_code }}</span></td>
                        <td class="px-4 py-2">{{ $driver->vehicle?->registration_number ?? '—' }}</td>
                        <td class="px-4 py-2 font-semibold">{{ $s['score'] }}</td>
                        <td class="px-4 py-2">{{ $s['harsh_braking'] }}</td>
                        <td class="px-4 py-2">{{ $s['harsh_acceleration'] }}</td>
                        <td class="px-4 py-2">{{ $s['speeding'] }}</td>
                        <td class="px-4 py-2 text-right space-x-2">
                            <a class="text-teal-700" href="{{ route('drivers.show', $driver) }}">View</a>
                            <a class="text-slate-700" href="{{ route('drivers.edit', $driver) }}">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-slate-500">No drivers match those filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $drivers->links() }}</div>
</x-layouts.app>
