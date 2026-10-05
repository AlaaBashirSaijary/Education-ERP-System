@php
    $grade = fn ($p) => $p >= 90 ? ['Excellent', 'text-emerald-600'] : ($p >= 80 ? ['Very good', 'text-brand-600'] : ($p >= 70 ? ['Good', 'text-brand-600'] : ($p >= 60 ? ['Pass', 'text-amber-600'] : ['Needs improvement', 'text-rose-600'])));
    $cls = $enrollment?->schoolClass ?? $student->schoolClass;
@endphp
<div class="mx-auto max-w-4xl space-y-4">
    <x-flash />
    <div class="no-print flex flex-wrap items-center justify-between gap-3">
        <div class="seg">
            <label class="{{ ! $termId ? 'on-present' : '' }}"><input type="radio" class="sr-only" wire:click="$set('termId', null)"> {{ __('Whole year') }}</label>
            @foreach ($terms as $t)<label class="{{ $termId === $t->id ? 'on-present' : '' }}"><input type="radio" class="sr-only" wire:click="$set('termId', {{ $t->id }})"> {{ $t->name }}</label>@endforeach
        </div>
        <div class="flex gap-2">
            <button class="btn-ghost" onclick="window.print()">🖨 {{ __('Print') }}</button>
            @if (auth()->user()->hasRole('admin', 'teacher'))<button class="btn-primary" wire:click="sendToParent">{{ __('Send to parent') }}</button>@endif
        </div>
    </div>

    <article class="relative overflow-hidden rounded-3xl bg-surface p-6 shadow-card ring-1 ring-slate-200/80 sm:p-10">
        <div class="absolute inset-x-0 top-0 h-2 bg-gradient-to-r from-brand-600 via-brand-400 to-saffron"></div>
        <div class="absolute -top-10 end-0 h-48 w-48 text-brand-600/[.07]"><x-pattern /></div>
        <header class="relative flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-6">
            <div class="flex items-center gap-3"><x-emblem class="h-12 w-12 text-brand-600" />
                <div><div class="text-lg font-bold text-slate-900">{{ config('school.name') }}</div><div class="text-sm text-slate-500">{{ __('Report card') }}</div></div></div>
            <div class="text-end"><div class="eyebrow">{{ __('Academic year') }}</div><div class="text-lg font-bold font-num" dir="ltr">{{ $year?->name }}</div>
                @if ($termId)<div class="text-sm text-slate-500">{{ $terms->firstWhere('id', $termId)?->name }}</div>@endif</div>
        </header>

        <section class="relative grid gap-6 py-6 sm:grid-cols-[1fr_auto] sm:items-center">
            <div class="flex items-center gap-4"><x-avatar :name="$student->name" size="h-16 w-16 text-xl" />
                <div><h1 class="font-display text-2xl font-bold text-slate-900">{{ $student->name }}</h1>
                    <div class="text-sm text-slate-500">{{ $cls->name }} {{ $cls->section }} · <span class="font-num" dir="ltr">#{{ $student->student_no }}</span></div></div></div>
            <div class="flex items-center gap-4 rounded-2xl bg-slate-50 px-5 py-3">
                <div class="font-display text-4xl font-bold text-brand-600 font-num">{{ $overall !== null ? $overall.'%' : '—' }}</div>
                <div class="text-sm"><div class="eyebrow">{{ __('Overall') }}</div>@if ($overall !== null)<div class="font-bold {{ $grade($overall)[1] }}">{{ __($grade($overall)[0]) }}</div>@endif</div>
            </div>
        </section>

        <div class="relative space-y-4">
            @forelse ($subjects as $s)
                @php [$gl, $gc] = $grade($s['percentage']); @endphp
                <div>
                    <div class="mb-1.5 flex items-baseline justify-between gap-3">
                        <span class="font-semibold text-slate-900">{{ $s['subject'] }}</span>
                        <span class="flex items-baseline gap-3"><span class="text-xs font-semibold {{ $gc }}">{{ __($gl) }}</span><span class="text-base font-bold font-num">{{ $s['percentage'] }}%</span></span>
                    </div>
                    <div class="h-2.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $s['percentage'] >= 80 ? 'bg-emerald-500' : ($s['percentage'] >= 60 ? 'bg-brand-500' : 'bg-amber-500') }}" style="width: {{ $s['percentage'] }}%"></div></div>
                    <div class="mt-1 text-xs text-slate-500">@foreach ($s['exams'] as $m){{ $m->exam->name }}: <span dir="ltr" class="font-num">{{ $m->mark + 0 }}/{{ $m->exam->max_mark }}</span>@if(!$loop->last) · @endif @endforeach</div>
                </div>
            @empty
                <x-empty icon="grades" :title="__('No marks yet.')" />
            @endforelse
        </div>

        <footer class="relative mt-8 flex flex-wrap gap-3 border-t border-slate-200 pt-5 text-sm">
            <span class="eyebrow self-center">{{ __('Attendance') }}</span>
            @foreach (['present', 'late', 'absent'] as $st)
                <span class="rounded-xl bg-slate-50 px-4 py-2"><span class="text-slate-500">{{ __($st) }}</span> <b class="font-num">{{ $attendance[$st] ?? 0 }}</b></span>
            @endforeach
        </footer>
    </article>

    @if ($history->count() > 1)
        <p class="no-print text-center text-xs text-slate-500">{{ __('Switch the academic year from the top bar to see earlier report cards.') }}</p>
    @endif
</div>
