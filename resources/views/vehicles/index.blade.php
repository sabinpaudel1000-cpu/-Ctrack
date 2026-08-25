<x-layouts.app title="Vehicles" subtitle="Search, filter and manage the demo fleet">
    <form class="mb-4 flex flex-wrap gap-3" method="GET">
        <input name="q" value="{{ request('q') }}" placeholder="Search registration, make, model"
               class="rounded-md border border-slate-300 px-3 py-2 w-64">
        <select name="status" class="rounded-md border border-slate-300 px-3 py-2">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <select name="type" class="rounded-md border border-slate-300 px-3 py-2">
            <option value="">All types</option>
            @foreach ($types as $type)
                <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
        <select name="risk" class="rounded-md border border-slate-300 px-3 py-2">
            <option value="">All risk levels</option>
            @foreach ($riskLevels as $level)
                <option value="{{ $level->value }}" @selected(request('risk') === $level->value)>{{ $level->label() }}</option>
            @endforeach
        </select>
        <button class="rounded-md bg-slate-900 text-white px-4">Filter</button>
        <a href="{{ route('vehicles.create') }}" class="rounded-md bg-teal-600 text-white px-4 py-2">Add vehicle</a>
    </form>

    <div class="bg-white border border-slate-200 rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Registration</th>
                    <th class="px-4 py-2">Vehicle</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Driver</th>
                    <th class="px-4 py-2">Mileage</th>
                    <th class="px-4 py-2">Risk</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($vehicles as $vehicle)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-2 font-medium">{{ $vehicle->registration_number }}</td>
                        <td class="px-4 py-2">{{ $vehicle->make }} {{ $vehicle->model }} · {{ $vehicle->vehicle_type->label() }}</td>
                        <td class="px-4 py-2"><x-badge :tone="$vehicle->status->tone()">{{ $vehicle->status->label() }}</x-badge></td>
                        <td class="px-4 py-2">{{ $vehicle->driver?->fullName() ?? 'Unassigned' }}</td>
                        <td class="px-4 py-2">{{ number_format($vehicle->mileage) }} km</td>
                        <td class="px-4 py-2">
                            @if ($vehicle->latestRisk)
                                <x-badge :tone="$vehicle->latestRisk->level->tone()">{{ $vehicle->latestRisk->level->label() }} ({{ $vehicle->latestRisk->score }})</x-badge>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right space-x-2">
                            <a class="text-teal-700" href="{{ route('vehicles.show', $vehicle) }}">View</a>
                            <a class="text-slate-700" href="{{ route('vehicles.edit', $vehicle) }}">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-slate-500">No vehicles match those filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $vehicles->links() }}</div>
</x-layouts.app>
