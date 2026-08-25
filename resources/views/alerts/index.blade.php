<x-layouts.app title="Alerts" subtitle="Generated from current telematics, risk and driver scores">
    <form class="mb-4 flex flex-wrap gap-3" method="GET">
        <select name="type" class="rounded-md border px-3 py-2">
            <option value="">All types</option>
            @foreach ($types as $type)
                <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
        <select name="severity" class="rounded-md border px-3 py-2">
            <option value="">All severities</option>
            @foreach ($severities as $severity)
                <option value="{{ $severity->value }}" @selected(request('severity') === $severity->value)>{{ $severity->label() }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-md border px-3 py-2">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <button class="rounded-md bg-slate-900 text-white px-4 py-2">Filter</button>
    </form>
    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">When</th>
                    <th class="px-4 py-2">Type</th>
                    <th class="px-4 py-2">Vehicle</th>
                    <th class="px-4 py-2">Severity</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Message</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($alerts as $alert)
                    <tr class="border-t">
                        <td class="px-4 py-2 whitespace-nowrap">{{ $alert->triggered_at->format('d M H:i') }}</td>
                        <td class="px-4 py-2">{{ $alert->type->label() }}</td>
                        <td class="px-4 py-2">{{ $alert->vehicle?->registration_number ?? '—' }}</td>
                        <td class="px-4 py-2"><x-badge :tone="$alert->severity->tone()">{{ $alert->severity->label() }}</x-badge></td>
                        <td class="px-4 py-2">
                            <form method="POST" action="{{ route('alerts.update', $alert) }}">
                                @csrf @method('PATCH')
                                <select name="status" onchange="this.form.submit()" class="border rounded px-2 py-1">
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status->value }}" @selected($alert->status === $status)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="px-4 py-2">{{ $alert->message }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-slate-500">No alerts match those filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $alerts->links() }}</div>
</x-layouts.app>
