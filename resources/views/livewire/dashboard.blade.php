<div class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $cards = [
                [__('Active students'), $stats['students'], 'text-indigo-600', null],
                [__('Present today'), $stats['present'], 'text-emerald-600', null],
                [__('Absent today'), $stats['absent'], 'text-rose-600', null],
            ];
            if (! auth()->user()->hasRole('teacher')) {
                $cards[] = [__('Outstanding fees'), number_format($stats['outstanding'], 2), 'text-amber-600', $stats['overdue_count'].' '.__('overdue')];
            }
        @endphp
        @foreach ($cards as [$label, $value, $color, $note])
            <div class="card">
                <div class="text-sm text-slate-500">{{ $label }}</div>
                <div class="mt-1 text-2xl font-bold {{ $color }}">{{ $value }}</div>
                @if ($note)<div class="text-xs text-slate-500">{{ $note }}</div>@endif
            </div>
        @endforeach
    </div>


    @if ($trend)
        @php $tmax = max(1, max(array_map(fn ($d) => $d['present'] + $d['absent'], $trend))); @endphp
        <div class="grid gap-4 lg:grid-cols-2">
            <div class="card">
                <div class="mb-1 flex items-center justify-between"><h2 class="font-bold">{{ __('Attendance, last 7 days') }}</h2>
                    <div class="flex gap-3 text-xs text-slate-500"><span><i class="inline-block h-2 w-2 rounded-full bg-emerald-500"></i> {{ __('present') }}</span><span><i class="inline-block h-2 w-2 rounded-full bg-rose-500"></i> {{ __('absent') }}</span></div></div>
                <div class="flex h-44 gap-2" role="img" aria-label="{{ __('Attendance, last 7 days') }}">
                    @foreach ($trend as $d)
                        <div class="flex flex-1 flex-col items-stretch justify-end" title="{{ $d['date'] }}: {{ $d['present'] }} / {{ $d['absent'] }}">
                            <div class="flex w-full flex-col justify-end overflow-hidden rounded-t" style="height: {{ ($d['present'] + $d['absent']) / $tmax * 100 }}%">
                                <div class="bg-rose-500" style="flex: {{ $d['absent'] }}"></div><div class="bg-emerald-500" style="flex: {{ $d['present'] }}"></div></div>
                            <span class="mt-1 text-center text-xs text-slate-400" dir="ltr">{{ substr($d['date'], 5) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            @if ($collections)
                @php $cmax = max(1, max($collections) ?: 1); @endphp
                <div class="card">
                    <h2 class="mb-1 font-bold">{{ __('Collections, last 6 months') }}</h2>
                    <div class="flex h-44 gap-3" role="img" aria-label="{{ __('Collections, last 6 months') }}">
                        @foreach ($collections as $m => $v)
                            <div class="flex flex-1 flex-col items-stretch justify-end gap-1" title="{{ $m }}: {{ number_format($v, 2) }}">
                                <span class="text-center text-xs text-slate-500" dir="ltr">{{ $v ? number_format($v, 0) : '' }}</span>
                                <div class="w-full rounded-t bg-indigo-500" style="height: {{ max(2, $v / $cmax * 100) }}%"></div>
                                <span class="text-center text-xs text-slate-400" dir="ltr">{{ substr($m, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    @if ($children->isNotEmpty())
        <h2 class="text-lg font-bold">{{ __('My children') }}</h2>
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($children as $child)
                @php $att = $child->attendances->first(); @endphp
                <div class="card flex items-center justify-between">
                    <div>
                        <div class="font-bold">{{ $child->name }}</div>
                        <div class="text-sm text-slate-500">{{ $child->schoolClass->name }} {{ $child->schoolClass->section }}</div>
                    </div>
                    <div class="text-end">
                        <x-status-badge :status="$att?->status" />
                        <a class="mt-2 block text-sm text-indigo-600 hover:underline" href="{{ route('report-card', $child) }}" wire:navigate>{{ __('Report card') }}</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
