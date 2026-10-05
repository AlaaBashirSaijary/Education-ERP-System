@php
    $user = auth()->user();
    $isParent = $user->hasRole('parent');
    $firstName = explode(' ', trim($user->name))[0];
    $subjectTone = fn ($id) => 'tone-'.($id % 8);
    $periodTimes = fn ($e) => substr($e->starts_at, 0, 5).'–'.substr($e->ends_at, 0, 5);
    $pct = $billed > 0 ? round($collected / $billed * 100) : 0;
@endphp
<div class="space-y-6">
    {{-- Greeting --}}
    <section class="relative overflow-hidden rounded-3xl bg-sidebar p-6 text-white shadow-card ring-1 ring-white/10 sm:p-8">
        <div class="absolute inset-y-0 end-0 w-2/3 text-white/[.07] [mask-image:linear-gradient(to_left,black,transparent)] rtl:[mask-image:linear-gradient(to_right,black,transparent)]"><x-pattern /></div>
        <div class="relative flex flex-wrap items-end justify-between gap-6">
            <div class="space-y-2">
                <div class="text-sm text-[#A9C2C6]">{{ now()->translatedFormat(app()->getLocale() === 'ar' ? 'l، j F Y' : 'l, j F Y') }}</div>
                <h2 class="font-display text-3xl font-bold sm:text-4xl">{{ $greeting }}{{ app()->getLocale() === 'ar' ? '،' : ',' }} {{ $firstName }}</h2>
                <p class="max-w-xl text-[#A9C2C6]">
                    @if ($isParent) {{ __('Here is how your children are doing today.') }}
                    @else {{ __(':n students are checked in so far today.', ['n' => $today['present'] + $today['late']]) }} @endif
                </p>
            </div>
            @unless ($isParent)
                <div class="flex flex-wrap gap-2">
                    @if ($user->hasRole('admin', 'teacher'))
                        <a href="{{ route('gate') }}" wire:navigate class="btn bg-saffron text-slate-900 hover:brightness-95"><x-icon name="gate" class="h-4 w-4" /> {{ __('Gate scanner') }}</a>
                        <a href="{{ route('attendance') }}" wire:navigate class="btn bg-white/10 text-white hover:bg-white/20"><x-icon name="attendance" class="h-4 w-4" /> {{ __('Take attendance') }}</a>
                    @endif
                    @if ($user->hasRole('accountant'))
                        <a href="{{ route('fees') }}" wire:navigate class="btn bg-saffron text-slate-900 hover:brightness-95"><x-icon name="fees" class="h-4 w-4" /> {{ __('Receive payment') }}</a>
                    @endif
                </div>
            @endunless
        </div>
    </section>

    {{-- KPIs --}}
    @unless ($isParent)
    <div class="stagger grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        @unless ($isParent)
            <x-stat :label="__('Active students')" :value="$active" icon="students" tone="brand" />
            <x-stat :label="__('Present today')" :value="$today['present'] + $today['late']" icon="attendance" tone="ok" :note="$today['late'] ? __(':n late', ['n' => $today['late']]) : null" />
            <x-stat :label="__('Absent today')" :value="$today['absent']" icon="announce" tone="bad" :note="$today['pending'] ? __(':n not recorded yet', ['n' => $today['pending']]) : null" />
        @endunless
        @if (! auth()->user()->hasRole('teacher'))
            <x-stat :label="__('Outstanding fees')" :value="number_format($outstanding, 2)" icon="fees" tone="warn" :note="$overdue->count().' '.__('overdue')" />
        @endif
        @if ($user->hasRole('teacher'))
            <x-stat :label="__('Your lessons today')" :value="$lessons->count()" icon="timetable" tone="brand" />
        @endif
    </div>
    @endunless

    {{-- Parent: children --}}
    @if ($isParent)
        <section class="space-y-3">
            <h2 class="text-lg font-bold text-slate-900">{{ __('My children') }}</h2>
            <div class="stagger grid gap-4 md:grid-cols-2">
                @foreach ($children as $child)
                    @php $att = $child->attendances->first(); $due = $balances[$child->id] ?? 0; @endphp
                    <article class="card space-y-4">
                        <div class="flex items-center gap-3">
                            <x-avatar :name="$child->name" size="h-12 w-12 text-base" />
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('student-profile', $child) }}" wire:navigate class="block truncate text-base font-bold text-slate-900 hover:text-brand-600">{{ $child->name }}</a>
                                <div class="text-sm text-slate-500">{{ $child->schoolClass->name }} {{ $child->schoolClass->section }}</div>
                            </div>
                            <x-status-badge :status="$att?->status" />
                        </div>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-xl bg-slate-50 p-3"><div class="text-xs text-slate-500">{{ __('Balance due') }}</div><div class="text-base font-bold font-num {{ $due > 0 ? 'text-amber-700' : 'text-emerald-700' }}">{{ number_format($due, 2) }}</div></div>
                            <div class="rounded-xl bg-slate-50 p-3"><div class="text-xs text-slate-500">{{ __('Student number') }}</div><div class="text-base font-bold font-num">{{ $child->student_no }}</div></div>
                        </div>
                        <div class="flex gap-3 text-sm">
                            <a class="link" href="{{ route('report-card', $child) }}" wire:navigate>{{ __('Report card') }}</a>
                            <a class="link" href="{{ route('fees', ['studentId' => $child->id]) }}" wire:navigate>{{ __('Fees') }}</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Staff: attendance + collections --}}
    @unless ($isParent)
        <div class="grid gap-4 lg:grid-cols-3">
            <section class="card flex flex-col items-center gap-4 lg:col-span-1">
                <h2 class="self-start text-base font-bold text-slate-900">{{ __('Today') }}</h2>
                <x-donut :present="$today['present']" :late="$today['late']" :absent="$today['absent']" />
                <div class="grid w-full grid-cols-3 gap-2 text-center text-xs">
                    <div class="rounded-xl bg-emerald-50 py-2"><div class="text-lg font-bold text-emerald-700 font-num">{{ $today['present'] }}</div><div class="text-emerald-700">{{ __('present') }}</div></div>
                    <div class="rounded-xl bg-amber-50 py-2"><div class="text-lg font-bold text-amber-700 font-num">{{ $today['late'] }}</div><div class="text-amber-700">{{ __('late') }}</div></div>
                    <div class="rounded-xl bg-rose-50 py-2"><div class="text-lg font-bold text-rose-700 font-num">{{ $today['absent'] }}</div><div class="text-rose-700">{{ __('absent') }}</div></div>
                </div>
            </section>

            <section class="card lg:col-span-2">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900">{{ __('Attendance, last 7 days') }}</h2>
                    <div class="flex gap-3 text-xs text-slate-500"><span class="flex items-center gap-1.5"><i class="h-2 w-2 rounded-full bg-emerald-500"></i>{{ __('present') }}</span><span class="flex items-center gap-1.5"><i class="h-2 w-2 rounded-full bg-rose-500"></i>{{ __('absent') }}</span></div>
                </div>
                @php $tmax = max(1, max(array_map(fn ($d) => $d['present'] + $d['absent'], $trend))); @endphp
                <div class="flex h-48 gap-2" role="img" aria-label="{{ __('Attendance, last 7 days') }}">
                    @foreach ($trend as $d)
                        <div class="group flex flex-1 flex-col items-stretch justify-end gap-1" title="{{ $d['date'] }}: {{ $d['present'] }} / {{ $d['absent'] }}">
                            <div class="flex flex-col justify-end overflow-hidden rounded-lg transition group-hover:opacity-80" style="height: {{ ($d['present'] + $d['absent']) / $tmax * 100 }}%">
                                <div class="bg-rose-500" style="flex: {{ $d['absent'] }}"></div><div class="bg-emerald-500" style="flex: {{ $d['present'] }}"></div>
                            </div>
                            <span class="text-center text-[11px] text-slate-400 font-num" dir="ltr">{{ substr($d['date'], 5) }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        @if ($collections)
            @php $cmax = max(1, max($collections) ?: 1); @endphp
            <div class="grid gap-4 lg:grid-cols-3">
                <section class="card lg:col-span-2">
                    <h2 class="mb-3 text-base font-bold text-slate-900">{{ __('Collections, last 6 months') }}</h2>
                    <div class="flex h-48 gap-3" role="img" aria-label="{{ __('Collections, last 6 months') }}">
                        @foreach ($collections as $m => $v)
                            <div class="flex flex-1 flex-col items-stretch justify-end gap-1" title="{{ $m }}: {{ number_format($v, 2) }}">
                                <span class="text-center text-xs font-semibold text-slate-500 font-num" dir="ltr">{{ $v ? number_format($v, 0) : '' }}</span>
                                <div class="rounded-t-lg bg-gradient-to-t from-brand-600 to-brand-400" style="height: {{ max(2, $v / $cmax * 100) }}%"></div>
                                <span class="text-center text-[11px] text-slate-400 font-num" dir="ltr">{{ substr($m, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>
                <section class="card flex flex-col justify-between gap-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">{{ __('Fees') }} · <span class="font-num">{{ $year?->name }}</span></h2>
                        <p class="text-sm text-slate-500">{{ __('Collected of billed this year') }}</p>
                    </div>
                    <div>
                        <div class="mb-2 flex items-end justify-between"><span class="font-display text-4xl font-bold text-brand-600 font-num">{{ $pct }}%</span>
                            <span class="text-xs text-slate-500 font-num" dir="ltr">{{ number_format($collected, 0) }} / {{ number_format($billed, 0) }}</span></div>
                        <div class="h-3 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-brand-400" style="width: {{ $pct }}%"></div></div>
                    </div>
                    <a href="{{ route('finance-report') }}" wire:navigate class="link text-sm">{{ __('Financial report') }} →</a>
                </section>
            </div>
        @endif
    @endunless

    {{-- Lists --}}
    <div class="grid gap-4 lg:grid-cols-2">
        @if (($user->hasRole('teacher') || $isParent))
            <section class="card-flat overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4"><h2 class="text-base font-bold text-slate-900">{{ $isParent ? __('Lessons today') : __('Your lessons today') }}</h2></div>
                @forelse ($lessons as $e)
                    <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-3 last:border-0">
                        <span class="grid h-10 w-10 place-items-center rounded-xl font-display font-bold {{ $subjectTone($e->subject_id) }}">{{ $e->period }}</span>
                        <div class="min-w-0 flex-1"><div class="truncate font-semibold">{{ $e->subject->name }}</div>
                            <div class="truncate text-xs text-slate-500">{{ $isParent ? $e->teacher->name : $e->schoolClass->name.' '.$e->schoolClass->section }}@if($e->room) · {{ $e->room }}@endif</div></div>
                        <span class="text-xs text-slate-500 font-num" dir="ltr">{{ $periodTimes($e) }}</span>
                    </div>
                @empty
                    <x-empty icon="timetable" :title="__('No lessons today')" />
                @endforelse
            </section>
        @endif

        @if ($overdue->isNotEmpty() || $user->hasRole('admin', 'accountant'))
            <section class="card-flat overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="text-base font-bold text-slate-900">{{ __('Overdue instalments') }}</h2>
                    @if ($user->hasRole('admin', 'accountant'))<a class="link text-sm" href="{{ route('fees') }}" wire:navigate>{{ __('View all') }}</a>@endif
                </div>
                @forelse ($overdue->take(5) as $f)
                    <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-3 last:border-0">
                        <x-avatar :name="$f->student->name" />
                        <div class="min-w-0 flex-1"><div class="truncate font-semibold">{{ $f->student->name }}</div><div class="truncate text-xs text-slate-500">{{ $f->title }} · <span class="font-num" dir="ltr">{{ $f->due_date->toDateString() }}</span></div></div>
                        <span class="font-display font-bold text-rose-600 font-num" dir="ltr">{{ $f->balance }}</span>
                    </div>
                @empty
                    <x-empty icon="fees" :title="__('Nothing overdue 🎉')" />
                @endforelse
            </section>
        @endif

        @if ($messages->isNotEmpty())
            <section class="card-flat overflow-hidden lg:col-span-2">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><h2 class="text-base font-bold text-slate-900">{{ __('Latest messages to parents') }}</h2><a class="link text-sm" href="{{ route('messages') }}" wire:navigate>{{ __('View all') }}</a></div>
                @foreach ($messages as $m)
                    <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-3 last:border-0">
                        <span class="badge-slate shrink-0">{{ __('msg.'.$m->type) }}</span>
                        <span class="min-w-0 flex-1 truncate text-sm">{{ $m->message }}</span>
                        <span class="{{ ['sent' => 'badge-green', 'failed' => 'badge-red'][$m->status] ?? 'badge-amber' }} shrink-0">{{ __($m->status) }}</span>
                    </div>
                @endforeach
            </section>
        @endif
    </div>
</div>
