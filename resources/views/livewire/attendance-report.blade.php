<div class="space-y-4">
    <div class="card flex flex-wrap items-end gap-4">
        <div><label class="label">{{ __('Class') }}</label>
            <select wire:model.live="classId" class="input"><option value="">{{ __('All classes') }}</option>@foreach ($classes as $c)<option value="{{ $c->id }}">{{ $c->name }} {{ $c->section }}</option>@endforeach</select></div>
        <div><label class="label">{{ __('Month') }}</label><input type="month" wire:model.live="month" class="input" dir="ltr"></div>
        <a class="btn-ghost" href="{{ route('attendance-csv', ['class' => $classId, 'month' => $monthValid]) }}">⬇ {{ __('Export CSV') }}</a>
    </div>
    <div class="table-wrap"><table class="tbl">
        <thead><tr><th>{{ __('Student') }}</th><th>{{ __('Class') }}</th><th>{{ __('present') }}</th><th>{{ __('late') }}</th><th>{{ __('absent') }}</th><th>{{ __('Attendance rate') }}</th></tr></thead>
        <tbody>@forelse ($rows as $r)
            <tr><td class="font-medium">{{ $r['student'] }}</td><td>{{ $r['class'] }}</td>
                <td class="text-emerald-700">{{ $r['present'] }}</td><td class="text-amber-600">{{ $r['late'] }}</td><td class="text-rose-600">{{ $r['absent'] }}</td>
                <td>@if ($r['rate'] === null)<span class="text-slate-400">—</span>@else
                    <div class="flex items-center gap-2"><div class="h-2 w-24 overflow-hidden rounded-full bg-slate-100"><div class="h-full {{ $r['rate'] >= 90 ? 'bg-emerald-500' : ($r['rate'] >= 75 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ $r['rate'] }}%"></div></div><span dir="ltr">{{ $r['rate'] }}%</span></div>@endif</td></tr>
        @empty<tr><td colspan="6" class="py-8 text-center text-slate-400">{{ __('No students yet.') }}</td></tr>@endforelse</tbody>
    </table></div>
</div>
