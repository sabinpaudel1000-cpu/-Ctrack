{{-- Statistical trend model. The chart is the fleet. The tables are the vehicles and drivers the rules flag. --}}
<x-layouts.app title="Predictions" subtitle="Statistical trend model, not deep learning">
    <p class="mb-4 text-sm text-slate-600">These figures come from a statistical trend model. They are not deep learning. Fuel uses the last 30 days to project the next 7 days. Driver risk uses the safety score and whether harsh events are rising.</p>

    <div class="grid md:grid-cols-3 gap-4 mb-6">
        <x-kpi label="Fleet average" :value="$report['fleet_average'].' L/100 km'" hint="Last 30 days" />
        <x-kpi label="Flagged vehicles" :value="$report['flagged_vehicles']->count()" hint="Forecast more than 15% above the fleet" />
        <x-kpi label="Drivers at risk" :value="$report['drivers_at_risk']->count()" hint="Predicted medium or high for the next 30 days" />
    </div>

    <div class="bg-white border rounded-xl p-4 mb-6">
        <h2 class="font-semibold mb-3">Fleet fuel: actual vs forecast (L/100 km)</h2>
        @if (count($report['chart']['labels']) === 0)
            <p class="text-sm text-slate-500">not enough data</p>
        @else
            <canvas id="fuelForecastChart" height="120"></canvas>
        @endif
    </div>

    <div class="bg-white border rounded-xl p-4 mb-6">
        <h2 class="font-semibold mb-2">Not enough history</h2>
        <p class="mb-2 text-xs text-slate-500">A forecast needs 15 days with trips. Fewer than that is not a trend.</p>
        @forelse ($report['needs_more_data'] as $item)
            <p class="text-sm text-slate-700">{{ $item['name'] }}: not enough data</p>
        @empty
            <p class="text-sm text-slate-500">No vehicle or driver is waiting on more days.</p>
        @endforelse
    </div>

    <div class="bg-white border rounded-xl overflow-x-auto mb-6">
        <div class="px-4 py-3 font-semibold border-b">Flagged vehicles</div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Vehicle</th>
                    <th class="px-4 py-2">Method</th>
                    <th class="px-4 py-2">Next 7 days</th>
                    <th class="px-4 py-2">Vs fleet</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['flagged_vehicles'] as $row)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $row['vehicle']->registration_number }}</td>
                        <td class="px-4 py-2">{{ $row['method'] === 'linear_trend' ? 'Linear trend' : 'Moving average' }}</td>
                        <td class="px-4 py-2">{{ $row['forecast'] }} L/100 km</td>
                        <td class="px-4 py-2">{{ $row['percent_above'] }}% above {{ $row['fleet_average'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-slate-500">No vehicles are more than 15% above the fleet average.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-white border rounded-xl overflow-x-auto">
        <div class="px-4 py-3 font-semibold border-b">Drivers at risk</div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Driver</th>
                    <th class="px-4 py-2">Safety score</th>
                    <th class="px-4 py-2">Outlook</th>
                    <th class="px-4 py-2">Next 30 days</th>
                    <th class="px-4 py-2">Reason</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['drivers_at_risk'] as $row)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $row['driver']->fullName() }}</td>
                        <td class="px-4 py-2">{{ $row['score'] }}</td>
                        <td class="px-4 py-2">{{ $row['outlook_score'] }}</td>
                        <td class="px-4 py-2"><x-badge :tone="$row['level']->tone()">{{ $row['level']->label() }}</x-badge></td>
                        <td class="px-4 py-2">{{ $row['reason'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-slate-500">No drivers are at medium or high risk.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (count($report['chart']['labels']) > 0)
    @push('scripts')
    <script>
        const chart = @json($report['chart']);
        new Chart(document.getElementById('fuelForecastChart'), {
            type: 'line',
            data: {
                labels: chart.labels,
                datasets: [
                    { label: 'Actual', data: chart.actual, borderColor: '#0d9488', backgroundColor: '#0d9488', tension: 0.3, spanGaps: false },
                    { label: 'Forecast', data: chart.forecast, borderColor: '#f59e0b', backgroundColor: '#f59e0b', borderDash: [6, 4], tension: 0.3, spanGaps: false }
                ]
            },
            options: { plugins: { legend: { display: true } } }
        });
    </script>
    @endpush
    @endif
</x-layouts.app>
