@props(['title' => 'Dashboard', 'subtitle' => 'Proactive fleet decisions from telematics'])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} · Ctrack Fleet BI Demo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50: '#f0fdfa', 500: '#14b8a6', 600: '#0d9488', 700: '#0f766e' }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-100 text-slate-800">
    <div class="min-h-screen lg:flex">
        <aside class="lg:w-64 bg-slate-900 text-slate-200">
            <div class="px-5 py-6 border-b border-slate-800">
                <p class="text-xs uppercase tracking-wider text-teal-400">ICT308 Iteration 1</p>
                <p class="mt-1 text-lg font-semibold text-white">Ctrack Fleet BI</p>
                <p class="text-xs text-slate-400">Synthetic demo data only</p>
            </div>
            @php
                $nav = [
                    ['dashboard', 'Dashboard', 'dashboard'],
                    ['vehicles.index', 'Vehicles', 'vehicles.*'],
                    ['drivers.index', 'Drivers', 'drivers.*'],
                    ['telematics.index', 'Telematics', 'telematics.*'],
                    ['analytics.index', 'Analytics', 'analytics.*'],
                    ['risks.index', 'Maintenance Risk', 'risks.*'],
                    ['alerts.index', 'Alerts', 'alerts.*'],
                    ['recommendations.index', 'Recommendations', 'recommendations.*'],
                ];
            @endphp
            <nav class="p-3 space-y-1">
                @foreach ($nav as [$route, $label, $pattern])
                    <a href="{{ route($route) }}"
                       class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs($pattern) ? 'bg-teal-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </aside>
        <div class="flex-1 min-w-0">
            <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">{{ $title ?? 'Dashboard' }}</h1>
                    <p class="text-sm text-slate-500">{{ $subtitle ?? 'Proactive fleet decisions from telematics' }}</p>
                </div>
                <div class="flex items-center gap-4 text-sm">
                    <div class="text-right">
                        <p class="font-medium text-slate-800">{{ auth()->user()->name }}</p>
                        <p class="text-slate-500">{{ auth()->user()->role->label() }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50">Log out</button>
                    </form>
                </div>
            </header>
            <main class="p-6">
                @if (session('status'))
                    <div class="mb-4 rounded-md bg-teal-50 text-teal-800 px-4 py-3 text-sm">{{ session('status') }}</div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
