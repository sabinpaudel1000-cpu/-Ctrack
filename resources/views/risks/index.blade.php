<x-layouts.app title="Maintenance risk" subtitle="Weighted baseline v1 — explainable, not a trained model">
    <form class="mb-4" method="GET">
        <select name="level" onchange="this.form.submit()" class="rounded-md border px-3 py-2">
            <option value="">All levels</option>
            @foreach ($levels as $level)
                <option value="{{ $level->value }}" @selected(request('level') === $level->value)>{{ $level->label() }}</option>
            @endforeach
        </select>
    </form>
    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr><th class="px-4 py-2">Vehicle</th><th class="px-4 py-2">Score</th><th class="px-4 py-2">Level</th><th class="px-4 py-2">Top factor</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($vehicles as $vehicle)
                    @php $top = collect($vehicle->latestRisk->factors_json ?? [])->sortByDesc('contribution')->first(); @endphp
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $vehicle->displayName() }}</td>
                        <td class="px-4 py-2 font-semibold">{{ $vehicle->latestRisk?->score }}</td>
                        <td class="px-4 py-2"><x-badge :tone="$vehicle->latestRisk?->level->tone() ?? 'neutral'">{{ $vehicle->latestRisk?->level->label() }}</x-badge></td>
                        <td class="px-4 py-2">{{ $top['label'] ?? '—' }} ({{ $top['contribution'] ?? 0 }})</td>
                        <td class="px-4 py-2"><a class="text-teal-700" href="{{ route('risks.show', $vehicle) }}">Factors</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-slate-500">No maintenance-risk snapshots yet. Run php artisan fleet:recalculate.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
