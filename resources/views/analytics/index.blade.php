<x-layouts.app title="Analytics" :subtitle="$analytics['days'].'-day rollups from telematics_records'">
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach ($periods as $period)
            <a href="{{ route('analytics.index', ['days' => $period]) }}"
               class="rounded-md px-4 py-2 text-sm {{ (int) $analytics['days'] === $period ? 'bg-slate-900 text-white' : 'border border-slate-300 bg-white' }}">
                {{ $period }} days
            </a>
        @endforeach
    </div>

    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <x-kpi label="Distance" :value="$analytics['totals']['distance_km'].' km'" />
        <x-kpi label="Fuel used" :value="$analytics['totals']['fuel_consumed_l'].' L'" />
        <x-kpi label="Fleet L/100 km" :value="$analytics['totals']['l_per_100km']" hint="Fuel ÷ distance × 100" />
        <x-kpi label="Average speed" :value="$analytics['totals']['avg_speed'].' km/h'" />
        <x-kpi label="Avg safety score" :value="$analytics['totals']['average_safety_score']" hint="100 minus weighted events" />
        <x-kpi label="Harsh braking" :value="$analytics['totals']['harsh_braking']" />
        <x-kpi label="Harsh acceleration" :value="$analytics['totals']['harsh_acceleration']" />
        <x-kpi label="Speeding" :value="$analytics['totals']['speeding']" />
    </div>
    <div class="bg-white border rounded-xl p-4 mb-6">
        <h2 class="font-semibold mb-3">Fuel trend</h2>
        @if ($analytics['fuel_trend']->isEmpty())
            <p class="text-sm text-slate-500 py-10 text-center">No telematics in this period.</p>
        @else
            <canvas id="fuelTrend" height="140"></canvas>
        @endif
    </div>
    <div class="bg-white border rounded-xl overflow-x-auto mb-6">
        <div class="px-4 py-3 font-semibold border-b">Vehicle performance</div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500"><tr>
                <th class="px-4 py-2">Vehicle</th><th class="px-4 py-2">km</th><th class="px-4 py-2">L/100km</th><th class="px-4 py-2">Avg speed</th><th class="px-4 py-2">Events</th>
            </tr></thead>
            <tbody>
            @forelse ($analytics['vehicles'] as $row)
                <tr class="border-t">
                    <td class="px-4 py-2">
                        <a class="text-teal-700" href="{{ route('vehicles.show', $row['vehicle']) }}">{{ $row['vehicle']->registration_number }}</a>
                    </td>
                    <td class="px-4 py-2">{{ $row['distance_km'] }}</td>
                    <td class="px-4 py-2">{{ $row['l_per_100km'] }}</td>
                    <td class="px-4 py-2">{{ $row['avg_speed'] }}</td>
                    <td class="px-4 py-2">B {{ $row['harsh_braking'] }} / A {{ $row['harsh_acceleration'] }} / S {{ $row['speeding'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-slate-500">No vehicles to analyse yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="bg-white border rounded-xl overflow-x-auto">
        <div class="px-4 py-3 font-semibold border-b">Driver safety</div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500"><tr>
                <th class="px-4 py-2">Driver</th><th class="px-4 py-2">Score</th><th class="px-4 py-2">Distance</th><th class="px-4 py-2">Harsh brake</th><th class="px-4 py-2">Harsh accel</th><th class="px-4 py-2">Speeding</th>
            </tr></thead>
            <tbody>
            @forelse ($analytics['drivers'] as $row)
                <tr class="border-t">
                    <td class="px-4 py-2">
                        <a class="text-teal-700" href="{{ route('drivers.show', $row['driver']) }}">{{ $row['driver']->fullName() }}</a>
                    </td>
                    <td class="px-4 py-2 font-semibold">{{ $row['score'] }}</td>
                    <td class="px-4 py-2">{{ $row['distance_km'] }} km</td>
                    <td class="px-4 py-2">{{ $row['harsh_braking'] }}</td>
                    <td class="px-4 py-2">{{ $row['harsh_acceleration'] }}</td>
                    <td class="px-4 py-2">{{ $row['speeding'] }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-slate-500">No drivers to analyse yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @push('scripts')
    <script>
        const trend = @json($analytics['fuel_trend']);
        const el = document.getElementById('fuelTrend');
        if (el) {
            new Chart(el, {
                type: 'line',
                data: { labels: (trend || []).map(r => r.label), datasets: [{ label: 'L/100km', data: (trend || []).map(r => r.value), borderColor: '#0d9488', tension: .3 }] },
                options: { plugins: { legend: { display: false } } }
            });
        }
    </script>
    @endpush
</x-layouts.app>
