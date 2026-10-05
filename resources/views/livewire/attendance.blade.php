<div class="space-y-4">
    <x-flash />
    <div class="card flex flex-wrap items-end gap-4">
        <div><label class="label">{{ __('Class') }}</label>
            <select wire:model.live="classId" class="input">
                @foreach ($classes as $c)<option value="{{ $c->id }}">{{ $c->name }} {{ $c->section }}</option>@endforeach
            </select></div>
        <div><label class="label">{{ __('Date') }}</label><input type="date" wire:model.live="date" class="input" dir="ltr"></div>
        <div class="flex gap-2">
            <button class="btn-ghost" wire:click="markAll('present')">{{ __('All present') }}</button>
            <button class="btn-ghost" wire:click="markAll('absent')">{{ __('All absent') }}</button>
        </div>
    </div>

    <div class="table-wrap">
        <table class="tbl">
            <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Status') }}</th></tr></thead>
            <tbody>
            @forelse ($students as $s)
                <tr wire:key="a{{ $s->id }}">
                    <td class="font-medium">{{ $s->name }}</td>
                    <td>
                        <div class="inline-flex overflow-hidden rounded-lg ring-1 ring-slate-300">
                            @foreach (['present' => 'bg-emerald-600 text-white', 'late' => 'bg-amber-500 text-white', 'absent' => 'bg-rose-600 text-white'] as $st => $on)
                                <label class="cursor-pointer px-3 py-1.5 text-sm {{ ($statuses[$s->id] ?? '') === $st ? $on : 'bg-white text-slate-600' }}">
                                    <input type="radio" class="sr-only" wire:model.live="statuses.{{ $s->id }}" value="{{ $st }}"> {{ __($st) }}
                                </label>
                            @endforeach
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="2" class="py-8 text-center text-slate-400">{{ __('No students in this class.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($students->isNotEmpty())
        <button class="btn-primary" wire:click="save" wire:loading.attr="disabled">{{ __('Save attendance') }}</button>
    @endif
</div>
