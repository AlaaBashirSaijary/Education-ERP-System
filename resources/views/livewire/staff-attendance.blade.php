@php
    $opts = ['present' => 'on-present', 'late' => 'on-late', 'absent' => 'on-absent', 'leave' => 'on-leave'];
@endphp
<div class="space-y-5 {{ $tab === 'daily' ? 'pb-24' : '' }}">
    <x-flash />
    <div class="seg">
        <label class="{{ $tab === 'daily' ? 'on-present' : '' }}"><input type="radio" class="sr-only" wire:click="$set('tab','daily')"> {{ __('Daily sheet') }}</label>
        <label class="{{ $tab === 'monthly' ? 'on-present' : '' }}"><input type="radio" class="sr-only" wire:click="$set('tab','monthly')"> {{ __('Monthly report') }}</label>
    </div>

    @if ($tab === 'daily')
        <div class="card flex flex-wrap items-end gap-4">
            <div><label class="label">{{ __('Date') }}</label><input type="date" wire:model.live="date" class="input" dir="ltr">@error('date')<p class="err">{{ $message }}</p>@enderror</div>
            <div class="flex flex-wrap gap-2">
                <button class="btn-ghost" wire:click="markAll('present')">{{ __('All present') }}</button>
                <button class="btn-ghost" wire:click="markAll('absent')">{{ __('All absent') }}</button>
            </div>
            <p class="ms-auto max-w-xs text-xs text-slate-500">{{ __('Leave a row unselected to keep it as not recorded.') }}</p>
        </div>

        <div class="grid grid-cols-2 gap-3 text-center sm:grid-cols-5">
            <div class="rounded-2xl bg-emerald-50 p-3"><div class="font-display text-2xl font-bold text-emerald-700 font-num">{{ $tally['present'] }}</div><div class="text-xs font-semibold text-emerald-700">{{ __('present') }}</div></div>
            <div class="rounded-2xl bg-amber-50 p-3"><div class="font-display text-2xl font-bold text-amber-700 font-num">{{ $tally['late'] }}</div><div class="text-xs font-semibold text-amber-700">{{ __('late') }}</div></div>
            <div class="rounded-2xl bg-rose-50 p-3"><div class="font-display text-2xl font-bold text-rose-700 font-num">{{ $tally['absent'] }}</div><div class="text-xs font-semibold text-rose-700">{{ __('absent') }}</div></div>
            <div class="rounded-2xl bg-brand-50 p-3"><div class="font-display text-2xl font-bold text-brand-700 font-num">{{ $tally['leave'] }}</div><div class="text-xs font-semibold text-brand-700">{{ __('leave') }}</div></div>
            <div class="col-span-2 rounded-2xl bg-slate-100 p-3 sm:col-span-1"><div class="font-display text-2xl font-bold text-slate-600 font-num">{{ $tally['none'] }}</div><div class="text-xs font-semibold text-slate-600">{{ __('Not recorded') }}</div></div>
        </div>

        <div class="space-y-3">
            @forelse ($staff as $u)
                @php $r = $rows[$u->id] ?? ['status' => '']; $worked = in_array($r['status'] ?? '', ['present', 'late'], true); @endphp
                <div wire:key="st{{ $u->id }}" class="card-flat space-y-3 px-4 py-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3"><x-avatar :name="$u->name" />
                            <span class="min-w-0"><span class="block truncate font-semibold text-slate-900">{{ $u->name }}</span><span class="badge-slate">{{ __('role.'.$u->role) }}</span></span></div>
                        <div class="seg">
                            @foreach ($opts as $st => $on)
                                <label class="{{ ($r['status'] ?? '') === $st ? $on : '' }}">
                                    <input type="radio" class="sr-only" wire:model.live="rows.{{ $u->id }}.status" value="{{ $st }}"> {{ __($st) }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    @if ($r['status'] !== '')
                        <div class="grid gap-3 sm:grid-cols-[auto_auto_1fr]">
                            @if ($worked)
                                <div><label class="label">{{ __('Check-in') }}</label><input type="time" wire:model="rows.{{ $u->id }}.in" class="input w-36" dir="ltr">@error("rows.$u->id.in")<p class="err">{{ $message }}</p>@enderror</div>
                                <div><label class="label">{{ __('Check-out') }}</label><input type="time" wire:model="rows.{{ $u->id }}.out" class="input w-36" dir="ltr">@error("rows.$u->id.out")<p class="err">{{ $message }}</p>@enderror</div>
                            @endif
                            <div class="{{ $worked ? '' : 'sm:col-span-3' }}"><label class="label">{{ __('Note') }}</label><input wire:model="rows.{{ $u->id }}.note" class="input" maxlength="190" placeholder="{{ __('Optional') }}"></div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="card-flat"><x-empty icon="users" :title="__('No staff accounts yet.')" /></div>
            @endforelse
        </div>

        @if ($staff->isNotEmpty())
            <div class="no-print fixed inset-x-0 bottom-0 z-10 border-t border-slate-200 bg-surface/90 px-4 py-3 backdrop-blur lg:start-72">
                <div class="mx-auto flex max-w-7xl items-center justify-end"><button class="btn-primary" wire:click="save" wire:loading.attr="disabled"><x-icon name="check" class="h-4 w-4" /> {{ __('Save attendance') }}</button></div>
            </div>
        @endif
    @else
        <div class="card flex flex-wrap items-end gap-4">
            <div><label class="label">{{ __('Month') }}</label><input type="month" wire:model.live="month" class="input" dir="ltr"></div>
            <a class="btn-ghost" href="{{ route('staff-csv', ['month' => $monthValid]) }}">⬇ {{ __('Export CSV') }}</a>
        </div>
        <div class="table-wrap"><table class="tbl">
            <thead><tr><th>{{ __('Employee') }}</th><th>{{ __('present') }}</th><th>{{ __('late') }}</th><th>{{ __('absent') }}</th><th>{{ __('leave') }}</th><th>{{ __('Hours') }}</th><th>{{ __('Attendance rate') }}</th></tr></thead>
            <tbody>@forelse ($summary as $r)
                <tr><td><span class="flex items-center gap-3"><x-avatar :name="$r['name']" size="h-8 w-8 text-xs" /><span><span class="block font-semibold">{{ $r['name'] }}</span><span class="text-xs text-slate-500">{{ __('role.'.$r['role']) }}</span></span></span></td>
                    <td class="text-emerald-700 font-num">{{ $r['present'] }}</td><td class="text-amber-700 font-num">{{ $r['late'] }}</td><td class="text-rose-600 font-num">{{ $r['absent'] }}</td><td class="text-brand-700 font-num">{{ $r['leave'] }}</td>
                    <td class="font-num" dir="ltr" style="text-align:start">{{ $r['hours'] ?: '—' }}</td>
                    <td>@if ($r['rate'] === null)<span class="text-slate-400">—</span>@else<div class="flex items-center gap-2"><div class="h-2 w-24 overflow-hidden rounded-full bg-slate-100"><div class="h-full {{ $r['rate'] >= 95 ? 'bg-emerald-500' : ($r['rate'] >= 85 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ $r['rate'] }}%"></div></div><span class="font-num" dir="ltr">{{ $r['rate'] }}%</span></div>@endif</td></tr>
            @empty<tr><td colspan="7"><x-empty icon="users" :title="__('No staff accounts yet.')" /></td></tr>@endforelse</tbody>
        </table></div>
        <p class="text-xs text-slate-500">{{ __('Leave days are not counted against the attendance rate.') }}</p>
    @endif
</div>
