<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Home · Ctrack Fleet BI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-4xl mx-auto px-6 py-4 flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-wider text-teal-600">ICT308 Iteration 1</p>
                <p class="font-semibold text-slate-900">Ctrack Fleet BI</p>
            </div>
            <a href="{{ route('login') }}" class="rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">Sign in</a>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-6 py-16">
        <h1 class="text-4xl font-semibold text-slate-900">Fleet business intelligence</h1>
        <p class="mt-4 text-lg text-slate-600 max-w-2xl">A simple dashboard for fleet managers: vehicles, drivers, telematics, maintenance risk, alerts, and recommendations. All demo data is synthetic.</p>
        <a href="{{ route('login') }}" class="inline-block mt-8 rounded-md bg-teal-600 px-5 py-2.5 text-white font-medium hover:bg-teal-700">Sign in to continue</a>

        <ul class="mt-12 grid sm:grid-cols-2 gap-3 text-sm text-slate-700">
            <li class="rounded-lg border border-slate-200 bg-white px-4 py-3">Dashboard</li>
            <li class="rounded-lg border border-slate-200 bg-white px-4 py-3">Vehicles and drivers</li>
            <li class="rounded-lg border border-slate-200 bg-white px-4 py-3">Telematics and analytics</li>
            <li class="rounded-lg border border-slate-200 bg-white px-4 py-3">Maintenance risk, alerts, recommendations</li>
        </ul>

        <p class="mt-10 text-sm text-slate-500">Demo login: <code>manager@demo.local</code> / <code>password</code></p>
    </main>
</body>
</html>
