<x-layouts.guest>
    <div class="w-full max-w-md bg-white rounded-2xl p-8 shadow-sm">
        <p class="text-xs uppercase tracking-wider text-teal-600">ICT308 · Synthetic demo</p>
        <h1 class="mt-2 text-2xl font-semibold text-slate-900">Ctrack Fleet BI</h1>
        <p class="mt-1 text-sm text-slate-500">Sign in to the Iteration 1 prototype. This environment does not contain real Ctrack customer data.</p>

        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700">Email</label>
                <input type="email" name="email" value="{{ old('email', 'manager@demo.local') }}" required
                       class="mt-1 w-full rounded-md border-slate-300 border px-3 py-2">
                @error('email') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Password</label>
                <input type="password" name="password" value="password" required
                       class="mt-1 w-full rounded-md border-slate-300 border px-3 py-2">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember"> Remember me
            </label>
            <button class="w-full rounded-md bg-teal-600 text-white py-2.5 font-medium hover:bg-teal-700">Log in</button>
        </form>
        <div class="mt-6 text-xs text-slate-500 space-y-1">
            <p>Fleet manager: manager@demo.local / password</p>
            <p>Admin: admin@demo.local / password</p>
        </div>
    </div>
</x-layouts.guest>
