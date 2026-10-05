<div class="space-y-4">
    @php
        $isAdmin = auth()->user()->hasRole('admin');
        $days = [0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];
    @endphp
    <div class="card max-w-md"><label class="label">{{ __('Class') }}</label>
        <select wire:model.live="classId" class="input">@foreach ($classes as $c)<option value="{{ $c->id }}">{{ $c->name }} {{ $c->section }}</option>@endforeach</select></div>

    <div class="table-wrap"><table class="tbl">
        <thead><tr><th>{{ __('Period') }}</th>@foreach ($days as $d)<th>{{ __($d) }}</th>@endforeach</tr></thead>
        <tbody>
        @forelse ($grid as $period => $row)
            <tr><td class="font-bold">{{ $period }}</td>
            @foreach ($days as $i => $d)
                <td wire:key="t{{ $period }}-{{ $i }}">
                    @if ($e = $row->get($i))
                        <div class="rounded-lg bg-indigo-50 p-2 {{ $e->teacher_id === auth()->id() ? 'ring-2 ring-indigo-400' : '' }}">
                            <div class="font-semibold text-indigo-800">{{ $e->subject->name }}</div>
                            <div class="text-xs text-slate-500">{{ $e->teacher->name }}@if($e->room) · {{ $e->room }}@endif</div>
                            <div class="text-xs text-slate-400" dir="ltr">{{ substr($e->starts_at, 0, 5) }}–{{ substr($e->ends_at, 0, 5) }}</div>
                            @if ($isAdmin)<button class="mt-1 text-xs text-rose-600" wire:click="remove({{ $e->id }})" wire:confirm="{{ __('Delete this lesson?') }}">{{ __('Delete') }}</button>@endif
                        </div>
                    @endif
                </td>
            @endforeach
            </tr>
        @empty
            <tr><td colspan="8" class="py-8 text-center text-slate-400">{{ __('No lessons scheduled.') }}</td></tr>
        @endforelse
        </tbody>
    </table></div>

    @if ($isAdmin)
        <form wire:submit="add" class="card grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div><label class="label">{{ __('Day') }}</label><select wire:model="day" class="input">@foreach ($days as $i => $d)<option value="{{ $i }}">{{ __($d) }}</option>@endforeach</select></div>
            <div><label class="label">{{ __('Period') }}</label><input type="number" wire:model="period" class="input" dir="ltr">@error('period')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Subject') }}</label><select wire:model="subjectId" class="input"><option value="">—</option>@foreach ($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>@error('subjectId')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Teacher') }}</label><select wire:model="teacherId" class="input"><option value="">—</option>@foreach ($teachers as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select>@error('teacherId')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Starts') }}</label><input type="time" wire:model="startsAt" class="input" dir="ltr"></div>
            <div><label class="label">{{ __('Ends') }}</label><input type="time" wire:model="endsAt" class="input" dir="ltr">@error('endsAt')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Room') }}</label><input wire:model="room" class="input"></div>
            <div class="flex items-end"><button class="btn-primary w-full">＋ {{ __('Add lesson') }}</button></div>
        </form>
    @endif
</div>
