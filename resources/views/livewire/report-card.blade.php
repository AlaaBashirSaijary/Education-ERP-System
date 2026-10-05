<div class="mx-auto max-w-3xl space-y-4">
    <x-flash />
    <div class="card space-y-5">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-bold">{{ $student->name }}</h2>
                <div class="text-sm text-slate-500">{{ $student->schoolClass->name }} {{ $student->schoolClass->section }} · <span dir="ltr">{{ $student->student_no }}</span></div>
            </div>
            <div class="text-center">
                <div class="text-sm text-slate-500">{{ __('Overall') }}</div>
                <div class="text-3xl font-bold text-indigo-600">{{ $overall !== null ? $overall.'%' : '—' }}</div>
            </div>
        </div>

        <div class="table-wrap shadow-none">
            <table class="tbl">
                <thead><tr><th>{{ __('Subject') }}</th><th>{{ __('Exams') }}</th><th>%</th></tr></thead>
                <tbody>
                @forelse ($subjects as $s)
                    <tr><td class="font-medium">{{ $s['subject'] }}</td>
                        <td class="text-slate-600">@foreach ($s['exams'] as $m){{ $m->exam->name }}: <span dir="ltr">{{ $m->mark + 0 }}/{{ $m->exam->max_mark }}</span>@if(!$loop->last) · @endif @endforeach</td>
                        <td class="font-bold">{{ $s['percentage'] }}%</td></tr>
                @empty
                    <tr><td colspan="3" class="py-6 text-center text-slate-400">{{ __('No marks yet.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex gap-3 text-sm">
            @foreach (['present', 'late', 'absent'] as $st)
                <div class="rounded-lg bg-slate-50 px-4 py-2"><span class="text-slate-500">{{ __($st) }}</span> <b>{{ $attendance[$st] ?? 0 }}</b></div>
            @endforeach
        </div>

        <div class="no-print flex gap-2">
            <button class="btn-ghost" onclick="window.print()">🖨 {{ __('Print') }}</button>
            @if (auth()->user()->hasRole('admin', 'teacher'))
                <button class="btn-primary" wire:click="sendToParent">{{ __('Send to parent') }}</button>
            @endif
        </div>
    </div>
</div>
