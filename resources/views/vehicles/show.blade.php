<x-layouts.app :title="$vehicle->displayName()" subtitle="Vehicle record and latest maintenance risk">
    <div class="flex gap-3 mb-4">
        <a href="{{ route('vehicles.edit', $vehicle) }}" class="rounded-md bg-slate-900 text-white px-4 py-2 text-sm">Edit</a>
        <a href="{{ route('risks.show', $vehicle) }}" class="rounded-md border px-4 py-2 text-sm">Maintenance risk</a>
        <form method="POST" action="{{ route('vehicles.destroy', $vehicle) }}" onsubmit="return confirm('Delete this demo vehicle?')">
            @csrf @method('DELETE')
            <button class="rounded-md border border-rose-300 text-rose-700 px-4 py-2 text-sm">Delete</button>
        </form>
    </div>
    <div class="grid md:grid-cols-2 gap-4">
        <div class="bg-white border rounded-xl p-4 space-y-2 text-sm">
            <p><span class="text-slate-500">Type:</span> {{ $vehicle->vehicle_type->label() }}</p>
            <p><span class="text-slate-500">Year:</span> {{ $vehicle->manufacture_year }} ({{ $vehicle->ageInYears() }} years)</p>
            <p><span class="text-slate-500">Mileage:</span> {{ number_format($vehicle->mileage) }} km</p>
            <p><span class="text-slate-500">Status:</span> {{ $vehicle->status->label() }}</p>
            <p><span class="text-slate-500">Driver:</span> {{ $vehicle->driver?->fullName() ?? 'Unassigned' }}</p>
            <p><span class="text-slate-500">Last service:</span> {{ optional($vehicle->last_maintenance_date)->format('d M Y') ?? 'None' }}</p>
        </div>
        <div class="bg-white border rounded-xl p-4">
            @if ($vehicle->latestRisk)
                <p class="text-sm text-slate-500">Latest baseline score</p>
                <p class="text-3xl font-semibold">{{ $vehicle->latestRisk->score }}</p>
                <x-badge :tone="$vehicle->latestRisk->level->tone()">{{ $vehicle->latestRisk->level->label() }}</x-badge>
                <p class="mt-2 text-xs text-slate-500">{{ $vehicle->latestRisk->algorithm_version }} · not a trained ML model</p>
            @else
                <p class="text-slate-500">No risk snapshot yet. Run php artisan fleet:recalculate</p>
            @endif
        </div>
    </div>
</x-layouts.app>
