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
