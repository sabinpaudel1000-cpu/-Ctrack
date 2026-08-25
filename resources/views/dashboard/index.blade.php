<x-layouts.app title="Fleet dashboard" subtitle="KPIs calculated from seeded telematics records">
    <div class="grid grid-cols-2 xl:grid-cols-5 gap-4">
        <x-kpi label="Total vehicles" :value="$metrics['total_vehicles']" />
        <x-kpi label="Active" :value="$metrics['active_vehicles']" />
        <x-kpi label="Inactive" :value="$metrics['inactive_vehicles']" />
        <x-kpi label="Requiring attention" :value="$metrics['vehicles_requiring_attention']" hint="HIGH risk, workshop, or high-severity alert" />
        <x-kpi label="Open alerts" :value="$metrics['open_alerts']" hint="Current open alert count" />
        <x-kpi label="Avg fuel" :value="$metrics['average_fuel_l_per_100km'].' L/100km'" hint="All stored telematics" />
        <x-kpi label="Avg safety score" :value="$metrics['average_driver_safety_score']" hint="30-day driver events" />
        <x-kpi label="High risk" :value="$metrics['high_risk_vehicles']" />
        <x-kpi label="Medium risk" :value="$metrics['medium_risk_vehicles']" />
        <x-kpi label="Low risk" :value="$metrics['low_risk_vehicles']" />
    </div>

    <div class="mt-6 grid lg:grid-cols-2 gap-6">
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <h2 class="font-semibold mb-3">Fuel consumption trend (L/100 km)</h2>
            @if ($metrics['charts']['fuel_trend']->isEmpty())
                <p class="text-sm text-slate-500 py-10 text-center">No telematics in the last 14 days.</p>
            @else
                <canvas id="fuelChart" height="180"></canvas>
            @endif
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <h2 class="font-semibold mb-3">Vehicle risk distribution</h2>
            @if ($metrics['total_vehicles'] === 0)
                <p class="text-sm text-slate-500 py-10 text-center">No vehicles in the demo fleet yet.</p>
            @else
                <canvas id="riskChart" height="180"></canvas>
            @endif
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <h2 class="font-semibold mb-3">Driver safety scores</h2>
            @if ($metrics['charts']['driver_safety']->isEmpty())
                <p class="text-sm text-slate-500 py-10 text-center">No drivers to score yet.</p>
            @else
                <canvas id="safetyChart" height="180"></canvas>
            @endif
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <h2 class="font-semibold mb-3">Vehicle performance (km, 30 days)</h2>
            @if ($metrics['charts']['vehicle_performance']->isEmpty())
                <p class="text-sm text-slate-500 py-10 text-center">No active-vehicle distance in the last 30 days.</p>
            @else
                <canvas id="perfChart" height="180"></canvas>
            @endif
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4 lg:col-span-2">
            <h2 class="font-semibold mb-3">Alerts by category</h2>
            @if ($metrics['charts']['alerts_by_category']->isEmpty())
                <p class="text-sm text-slate-500 py-10 text-center">No alerts generated yet.</p>
            @else
                <canvas id="alertChart" height="120"></canvas>
            @endif
        </div>
    </div>

    <div class="mt-6 bg-white border border-slate-200 rounded-xl">
        <div class="px-4 py-3 border-b border-slate-200 font-semibold">Vehicles requiring attention</div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Vehicle</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Risk</th>
                    <th class="px-4 py-2">Why</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($metrics['attention_vehicles'] as $row)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-2">
                            <a class="text-teal-700" href="{{ route('vehicles.show', $row['vehicle']) }}">{{ $row['vehicle']->registration_number }}</a>
                        </td>
                        <td class="px-4 py-2"><x-badge :tone="$row['vehicle']->status->tone()">{{ $row['vehicle']->status->label() }}</x-badge></td>
                        <td class="px-4 py-2">
                            @if ($row['vehicle']->latestRisk)
                                <x-badge :tone="$row['vehicle']->latestRisk->level->tone()">{{ $row['vehicle']->latestRisk->level->label() }}</x-badge>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-2">{{ implode(', ', $row['reasons']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-slate-500">No vehicles currently require attention.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6 bg-white border border-slate-200 rounded-xl">
        <div class="px-4 py-3 border-b border-slate-200 font-semibold">Recent alerts</div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Time</th>
                    <th class="px-4 py-2">Type</th>
                    <th class="px-4 py-2">Vehicle</th>
                    <th class="px-4 py-2">Severity</th>
                    <th class="px-4 py-2">Message</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($metrics['recent_alerts'] as $alert)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-2 whitespace-nowrap">{{ $alert->triggered_at->format('d M H:i') }}</td>
                        <td class="px-4 py-2">{{ $alert->type->label() }}</td>
                        <td class="px-4 py-2">
                            @if ($alert->vehicle)
                                <a class="text-teal-700" href="{{ route('vehicles.show', $alert->vehicle) }}">{{ $alert->vehicle->registration_number }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-2"><x-badge :tone="$alert->severity->tone()">{{ $alert->severity->label() }}</x-badge></td>
                        <td class="px-4 py-2">{{ $alert->message }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-slate-500">No alerts generated yet. Seed the database.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @push('scripts')
    <script>
        const charts = @json($metrics['charts']);
        const make = (id, type, labels, data, extra = {}) => {
            const el = document.getElementById(id);
            if (!el) return;
            new Chart(el, {
                type,
                data: { labels, datasets: [{ data, backgroundColor: ['#0d9488','#f59e0b','#e11d48','#0ea5e9','#6366f1','#84cc16','#f97316'], borderColor: '#0d9488', tension: 0.3 }] },
                options: { plugins: { legend: { display: type === 'doughnut' } }, ...extra }
            });
        };
        make('fuelChart', 'line', (charts.fuel_trend || []).map(r => r.label), (charts.fuel_trend || []).map(r => r.value));
        make('riskChart', 'doughnut', (charts.risk_distribution || []).map(r => r.label), (charts.risk_distribution || []).map(r => r.value));
        make('safetyChart', 'bar', (charts.driver_safety || []).map(r => r.label), (charts.driver_safety || []).map(r => r.value));
        make('perfChart', 'bar', (charts.vehicle_performance || []).map(r => r.label), (charts.vehicle_performance || []).map(r => r.value));
        make('alertChart', 'bar', (charts.alerts_by_category || []).map(r => r.label), (charts.alerts_by_category || []).map(r => r.value));
    </script>
    @endpush
</x-layouts.app>
