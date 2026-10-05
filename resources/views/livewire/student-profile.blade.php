@php
    $class = $enrollment?->schoolClass ?? $student->schoolClass;
    $admin = auth()->user()->hasRole('admin');
    $billed = $fees->sum(fn ($f) => (float) $f->amount); $paid = $fees->sum(fn ($f) => (float) $f->paid);
@endphp
<div class="space-y-6">
    {{-- Header --}}
    <section class="card flex flex-wrap items-center gap-5">
        <x-avatar :name="$student->name" size="h-20 w-20 text-2xl" />
        <div class="min-w-0 flex-1 space-y-1">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="font-display text-2xl font-bold text-slate-900">{{ $student->name }}</h2>
                @unless ($student->active)<span class="badge-slate">{{ $student->graduated_at ? __('Graduated') : __('Inactive') }}</span>@endunless
            </div>
            <div class="flex flex-wrap gap-x-5 gap-y-1 text-sm text-slate-500">
                <span>{{ $class->name }} {{ $class->section }}</span>
                <span dir="ltr" class="font-num">#{{ $student->student_no }}</span>
                @if ($student->parent)<span>{{ __('Parent') }}: {{ $student->parent->name }}</span>@endif
                @if ($student->parent_phone)<span dir="ltr" class="font-num">{{ $student->parent_phone }}</span>@endif
            </div>
        </div>
        <div class="flex flex-wrap gap-2 no-print">
            <a class="btn-soft" href="{{ route('report-card', $student) }}" wire:navigate>{{ __('Report card') }}</a>
            @if ($admin)<a class="btn-ghost" target="_blank" href="{{ route('student-card', $student) }}">{{ __('ID card') }}</a>@endif
        </div>
    </section>

    <div class="stagger grid gap-4 sm:grid-cols-3">
        <x-stat :label="__('Attendance rate')" :value="$rate !== null ? $rate.'%' : '—'" icon="attendance" tone="ok" :note="$year?->name" />
        <x-stat :label="__('Overall')" :value="$overall !== null ? $overall.'%' : '—'" icon="grades" tone="brand" :note="$year?->name" />
        @if ($canSeeFees)
            <x-stat :label="__('Balance due')" :value="number_format($billed - $paid, 2)" icon="fees" :tone="$billed - $paid > 0 ? 'warn' : 'ok'" :note="$year?->name" />
        @else
            <x-stat :label="__('Absent')" :value="$attendance['absent'] ?? 0" icon="announce" tone="bad" :note="$year?->name" />
        @endif
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        {{-- Marks by subject --}}
        <section class="card lg:col-span-2">
            <h2 class="mb-4 text-base font-bold text-slate-900">{{ __('Marks by subject') }}</h2>
            <div class="space-y-3">
                @forelse ($bySubject as $s)
                    <div>
                        <div class="mb-1 flex justify-between text-sm"><span class="font-medium">{{ $s['subject'] }}</span><span class="font-bold font-num">{{ $s['pct'] }}%</span></div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $s['pct'] >= 85 ? 'bg-emerald-500' : ($s['pct'] >= 60 ? 'bg-brand-500' : 'bg-amber-500') }}" style="width: {{ $s['pct'] }}%"></div></div>
                    </div>
                @empty
                    <x-empty icon="grades" :title="__('No marks yet.')" class="!py-6" />
                @endforelse
            </div>
        </section>

        {{-- Class history --}}
        <section class="card">
            <h2 class="mb-4 text-base font-bold text-slate-900">{{ __('School journey') }}</h2>
            <ol class="relative space-y-4 border-s-2 border-slate-200 ps-5">
                @foreach ($history as $h)
                    <li class="relative">
                        <span class="absolute -start-[1.65rem] top-1 h-3 w-3 rounded-full ring-4 ring-surface {{ $h->academic_year_id === $year?->id ? 'bg-brand-500' : 'bg-slate-300' }}"></span>
                        <div class="text-sm font-semibold font-num">{{ $h->academicYear->name }}</div>
                        <div class="text-sm text-slate-500">{{ $h->schoolClass->name }} {{ $h->schoolClass->section }}</div>
                    </li>
                @endforeach
                @if ($student->graduated_at)
                    <li class="relative"><span class="absolute -start-[1.65rem] top-1 h-3 w-3 rounded-full bg-saffron ring-4 ring-surface"></span><div class="text-sm font-semibold">{{ __('Graduated') }}</div></li>
                @endif
            </ol>
        </section>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="card-flat overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="text-base font-bold text-slate-900">{{ __('Recent attendance') }}</h2></div>
            @forelse ($recent as $a)
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-2.5 text-sm last:border-0">
                    <span class="font-num" dir="ltr">{{ \Illuminate\Support\Carbon::parse($a->date)->translatedFormat('D j M') }}</span>
                    <span class="flex items-center gap-3"><span class="text-xs text-slate-400">{{ $a->checked_in_at?->format('H:i') }}</span><x-status-badge :status="$a->status" /></span>
                </div>
            @empty
                <x-empty icon="attendance" :title="__('No check-ins yet.')" class="!py-8" />
            @endforelse
        </section>

        @if ($canSeeFees)
            <section class="card-flat overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><h2 class="text-base font-bold text-slate-900">{{ __('Fees') }}</h2>
                    <a class="link text-sm" href="{{ route('fees', ['studentId' => $student->id]) }}" wire:navigate>{{ __('Open statement') }}</a></div>
                @forelse ($fees as $f)
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-2.5 text-sm last:border-0">
                        <span class="min-w-0 flex-1 truncate">{{ $f->title }}</span>
                        <span class="font-num" dir="ltr">{{ $f->balance }}</span><x-status-badge :status="$f->status" />
                    </div>
                @empty
                    <x-empty icon="fees" :title="__('No instalments yet.')" class="!py-8" />
                @endforelse
            </section>
        @endif
    </div>
</div>
