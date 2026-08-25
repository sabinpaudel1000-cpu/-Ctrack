@props(['label', 'value', 'hint' => null])
<div class="rounded-xl bg-white border border-slate-200 p-4">
    <p class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</p>
    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
