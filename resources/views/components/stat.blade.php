@props(['label', 'value', 'icon' => 'home', 'tone' => 'brand', 'note' => null])
@php
    $tones = [
        'brand' => 'bg-brand-100 text-brand-700', 'ok' => 'bg-emerald-100 text-emerald-700',
        'warn' => 'bg-amber-100 text-amber-800', 'bad' => 'bg-rose-100 text-rose-700',
    ];
@endphp
<div {{ $attributes->merge(['class' => 'card flex flex-col gap-3 !p-4 sm:flex-row sm:items-start sm:gap-4 sm:!p-5']) }}>
    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl {{ $tones[$tone] ?? $tones['brand'] }}"><x-icon :name="$icon" class="h-5 w-5" /></span>
    <div class="min-w-0">
        <div class="text-xs font-semibold text-slate-500">{{ $label }}</div>
        <div class="font-display text-3xl font-bold leading-tight text-slate-900 font-num">{{ $value }}</div>
        @if ($note)<div class="mt-0.5 text-xs text-slate-500">{{ $note }}</div>@endif
    </div>
</div>
