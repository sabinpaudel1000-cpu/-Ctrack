<x-layouts.app :title="'Risk · '.$vehicle->registration_number" subtitle="Transparent factor contributions">
    <div class="bg-white border rounded-xl p-6 mb-6">
        <p class="text-sm text-slate-500">Weighted maintenance baseline (not a trained ML model)</p>
        <p class="text-4xl font-semibold">{{ $vehicle->latestRisk?->score ?? '—' }}</p>
        @if ($vehicle->latestRisk)
            <x-badge :tone="$vehicle->latestRisk->level->tone()">{{ $vehicle->latestRisk->level->label() }}</x-badge>
            <p class="mt-2 text-xs text-slate-500">{{ $vehicle->latestRisk->algorithm_version }} · LOW 0–39 · MEDIUM 40–69 · HIGH 70–100</p>
        @else
            <p class="mt-2 text-sm text-slate-500">No risk snapshot for this vehicle yet.</p>
        @endif
    </div>
    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Factor</th>
                    <th class="px-4 py-2">Observed</th>
                    <th class="px-4 py-2">Band score</th>
                    <th class="px-4 py-2">Weight</th>
                    <th class="px-4 py-2">Contribution</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($vehicle->latestRisk->factors_json ?? [] as $factor)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $factor['label'] }}</td>
                        <td class="px-4 py-2">{{ $factor['raw_display'] }}</td>
                        <td class="px-4 py-2">{{ $factor['band_score'] }}</td>
                        <td class="px-4 py-2">{{ $factor['weight'] * 100 }}%</td>
                        <td class="px-4 py-2 font-semibold">{{ $factor['contribution'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-slate-500">No factor breakdown available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
