@php
    $tally = array_count_values($statuses) + ['present' => 0, 'late' => 0, 'absent' => 0];
    $cls = ['present' => 'on-present', 'late' => 'on-late', 'absent' => 'on-absent'];
@endphp
<div class="space-y-5 pb-24">
    <x-flash />
    <div class="card flex flex-wrap items-end gap-4">
        <div class="min-w-48 flex-1"><label class="label">{{ __('Class') }}</label>
            <select wire:model.live="classId" class="input">@foreach ($classes as $c)<option value="{{ $c->id }}">{{ $c->name }} {{ $c->section }}</option>@endforeach</select></div>
        <div><label class="label">{{ __('Date') }}</label><input type="date" wire:model.live="date" class="input" dir="ltr"></div>
        <div class="flex gap-2">
            <button class="btn-ghost" wire:click="markAll('present')">{{ __('All present') }}</button>
            <button class="btn-ghost" wire:click="markAll('absent')">{{ __('All absent') }}</button>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-3 text-center">
        <div class="rounded-2xl bg-emerald-50 p-3"><div class="font-display text-2xl font-bold text-emerald-700 font-num">{{ $tally['present'] }}</div><div class="text-xs font-semibold text-emerald-700">{{ __('present') }}</div></div>
        <div class="rounded-2xl bg-amber-50 p-3"><div class="font-display text-2xl font-bold text-amber-700 font-num">{{ $tally['late'] }}</div><div class="text-xs font-semibold text-amber-700">{{ __('late') }}</div></div>
        <div class="rounded-2xl bg-rose-50 p-3"><div class="font-display text-2xl font-bold text-rose-700 font-num">{{ $tally['absent'] }}</div><div class="text-xs font-semibold text-rose-700">{{ __('absent') }}</div></div>
    </div>

    @forelse ($students as $s)
        <div wire:key="a{{ $s->id }}" class="card-flat flex flex-wrap items-center justify-between gap-3 px-4 py-3">
            <div class="flex min-w-0 items-center gap-3"><x-avatar :name="$s->name" /><span class="truncate font-semibold text-slate-900">{{ $s->name }}</span></div>
            <div class="seg">
                @foreach ($cls as $st => $on)
                    <label class="{{ ($statuses[$s->id] ?? '') === $st ? $on : '' }}">
                        <input type="radio" class="sr-only" wire:model.live="statuses.{{ $s->id }}" value="{{ $st }}"> {{ __($st) }}
                    </label>
                @endforeach
            </div>
        </div>
    @empty
        <div class="card-flat"><x-empty icon="students" :title="__('No students in this class.')" /></div>
    @endforelse

    @if ($students->isNotEmpty())
        <div class="no-print fixed inset-x-0 bottom-0 z-10 border-t border-slate-200 bg-surface/90 px-4 py-3 backdrop-blur lg:start-72">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-3">
                <span class="hidden text-sm text-slate-500 sm:block">{{ __('Parents of absent students are notified when you save.') }}</span>
                <button class="btn-primary ms-auto" wire:click="save" wire:loading.attr="disabled"><x-icon name="check" class="h-4 w-4" /> {{ __('Save attendance') }}</button>
            </div>
        </div>
    @endif
</div>
