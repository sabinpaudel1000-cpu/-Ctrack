@props(['tone' => 'neutral'])
@php
$classes = match ($tone) {
    'success' => 'bg-emerald-50 text-emerald-700',
    'warning' => 'bg-amber-50 text-amber-700',
    'danger' => 'bg-rose-50 text-rose-700',
    'info' => 'bg-sky-50 text-sky-700',
    default => 'bg-slate-100 text-slate-600',
};
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '.$classes]) }}>
    {{ $slot }}
</span>
