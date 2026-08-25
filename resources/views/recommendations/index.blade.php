<x-layouts.app title="Recommendations" subtitle="Actions mapped from current alerts — not static copy">
    <form class="mb-4 flex gap-3" method="GET">
        <select name="priority" class="rounded-md border px-3 py-2">
            <option value="">All priorities</option>
            @foreach ($priorities as $priority)
                <option value="{{ $priority->value }}" @selected(request('priority') === $priority->value)>{{ $priority->label() }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-md border px-3 py-2">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <button class="rounded-md bg-slate-900 text-white px-4">Filter</button>
    </form>
    <div class="space-y-3">
        @foreach ($recommendations as $recommendation)
            <article class="bg-white border rounded-xl p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="font-semibold">{{ $recommendation->title }}</h2>
                        <p class="text-sm text-slate-600 mt-1">{{ $recommendation->rationale }}</p>
                        <p class="text-xs text-slate-500 mt-2">
                            {{ $recommendation->vehicle?->registration_number }}
                            · {{ $recommendation->generated_at->format('d M Y H:i') }}
                        </p>
                    </div>
                    <div class="text-right space-y-2">
                        <x-badge :tone="$recommendation->priority->tone()">{{ $recommendation->priority->label() }}</x-badge>
                        <form method="POST" action="{{ route('recommendations.update', $recommendation) }}">
                            @csrf @method('PATCH')
                            <select name="status" onchange="this.form.submit()" class="border rounded px-2 py-1 text-sm">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}" @selected($recommendation->status === $status)>{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
    <div class="mt-4">{{ $recommendations->links() }}</div>
</x-layouts.app>
