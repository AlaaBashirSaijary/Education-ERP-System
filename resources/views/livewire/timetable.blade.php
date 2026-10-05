@php
    $isAdmin = auth()->user()->hasRole('admin');
    $days = [0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];
    $todayDow = now()->dayOfWeek; $nowTime = now()->format('H:i');
    // Hide Friday/Saturday when nothing is scheduled on them.
    $used = $grid->flatMap(fn ($row) => $row->keys())->unique();
    $days = array_filter($days, fn ($d, $i) => $i < 5 || $used->contains($i), ARRAY_FILTER_USE_BOTH);
@endphp
<div class="space-y-5">
    <div class="card max-w-md"><label class="label">{{ __('Class') }}</label>
        <select wire:model.live="classId" class="input">@foreach ($classes as $c)<option value="{{ $c->id }}">{{ $c->name }} {{ $c->section }}</option>@endforeach</select></div>

    <div class="table-wrap"><table class="tbl w-full min-w-[44rem] table-fixed">
        <thead><tr><th class="w-16">{{ __('Period') }}</th>@foreach ($days as $i => $d)<th class="{{ $i === $todayDow ? '!text-brand-700 !bg-brand-50' : '' }}">{{ __($d) }}@if ($i === $todayDow) <span class="badge-brand ms-1">{{ __('Today') }}</span>@endif</th>@endforeach</tr></thead>
        <tbody>
        @forelse ($grid as $period => $row)
            <tr><td class="text-base font-bold text-slate-400">{{ $period }}</td>
            @foreach ($days as $i => $d)
                <td wire:key="t{{ $period }}-{{ $i }}" class="align-top {{ $i === $todayDow ? 'bg-brand-50/40' : '' }}">
                    @if ($e = $row->get($i))
                        @php $live = $i === $todayDow && $nowTime >= substr($e->starts_at, 0, 5) && $nowTime <= substr($e->ends_at, 0, 5); @endphp
                        <div class="tone-{{ $e->subject_id % 8 }} rounded-xl p-2.5 {{ $e->teacher_id === auth()->id() ? 'ring-2 ring-brand-500' : '' }} {{ $live ? 'ring-2 ring-saffron' : '' }}">
                            <div class="font-semibold leading-tight">{{ $e->subject->name }}</div>
                            <div class="mt-0.5 truncate text-xs opacity-80">{{ $e->teacher->name }}@if($e->room) · {{ $e->room }}@endif</div>
                            <div class="mt-1 text-[11px] font-medium opacity-70 font-num" dir="ltr">{{ substr($e->starts_at, 0, 5) }}–{{ substr($e->ends_at, 0, 5) }}</div>
                            @if ($isAdmin)<button class="mt-1 text-[11px] font-semibold underline opacity-80" wire:click="remove({{ $e->id }})" wire:confirm="{{ __('Delete this lesson?') }}">{{ __('Delete') }}</button>@endif
                        </div>
                    @endif
                </td>
            @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ count($days) + 1 }}"><x-empty icon="timetable" :title="__('No lessons scheduled.')" /></td></tr>
        @endforelse
        </tbody>
    </table></div>

    @if ($isAdmin)
        <form wire:submit="add" class="card grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <h2 class="text-base font-bold sm:col-span-2 lg:col-span-4">{{ __('Add lesson') }}</h2>
            <div><label class="label">{{ __('Day') }}</label><select wire:model="day" class="input">@foreach ($days as $i => $d)<option value="{{ $i }}">{{ __($d) }}</option>@endforeach</select></div>
            <div><label class="label">{{ __('Period') }}</label><input type="number" wire:model="period" class="input" dir="ltr">@error('period')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Subject') }}</label><select wire:model="subjectId" class="input"><option value="">—</option>@foreach ($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>@error('subjectId')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Teacher') }}</label><select wire:model="teacherId" class="input"><option value="">—</option>@foreach ($teachers as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select>@error('teacherId')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Starts') }}</label><input type="time" wire:model="startsAt" class="input" dir="ltr"></div>
            <div><label class="label">{{ __('Ends') }}</label><input type="time" wire:model="endsAt" class="input" dir="ltr">@error('endsAt')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Room') }}</label><input wire:model="room" class="input"></div>
            <div class="flex items-end"><button class="btn-primary w-full"><x-icon name="plus" class="h-4 w-4" /> {{ __('Add lesson') }}</button></div>
        </form>
    @endif
</div>
