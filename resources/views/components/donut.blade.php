@props(['present' => 0, 'late' => 0, 'absent' => 0, 'size' => 168])
@php
    $r = 42; $c = 2 * M_PI * $r;
    $total = max(1, $present + $late + $absent);
    $segments = [[$present, '--emerald-500'], [$late, '--amber-400'], [$absent, '--rose-500']];
    $offset = 0;
@endphp
<div class="relative" style="width: {{ $size }}px; height: {{ $size }}px">
    <svg viewBox="0 0 100 100" class="-rotate-90" role="img" aria-label="{{ __('present') }} {{ $present }}, {{ __('late') }} {{ $late }}, {{ __('absent') }} {{ $absent }}">
        <circle cx="50" cy="50" r="{{ $r }}" fill="none" stroke="rgb(var(--slate-100))" stroke-width="11"/>
        @foreach ($segments as [$v, $color])
            @if ($v > 0)
                @php $len = $v / $total * $c; @endphp
                <circle cx="50" cy="50" r="{{ $r }}" fill="none" stroke="rgb(var({{ $color }}))" stroke-width="11" stroke-linecap="butt"
                        stroke-dasharray="{{ max(0, $len - 1.2) }} {{ $c }}" stroke-dashoffset="{{ -$offset }}"/>
                @php $offset += $len; @endphp
            @endif
        @endforeach
    </svg>
    <div class="absolute inset-0 grid place-items-center text-center">
        <div><div class="font-display text-3xl font-bold text-slate-900 font-num">{{ $present + $late + $absent ? round(($present + $late) / $total * 100) : 0 }}%</div>
            <div class="text-[11px] font-semibold text-slate-500">{{ __('Attendance today') }}</div></div>
    </div>
</div>
