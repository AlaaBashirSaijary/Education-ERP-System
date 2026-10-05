<div class="space-y-6">
    <x-flash />
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="max-w-2xl text-sm text-slate-500">{{ __('Exams, marks and fees belong to an academic year, and each student keeps a class history. At the end of the year, promote students to the next class.') }}</p>
        <div class="flex gap-2">
            <button class="btn-ghost" wire:click="openPromotion">{{ __('Promote students') }}</button>
            <button class="btn-primary" wire:click="create"><x-icon name="plus" class="h-4 w-4" /> {{ __('New academic year') }}</button>
        </div>
    </div>

    @if ($showForm)
        <form wire:submit="save" class="card grid animate-rise gap-4 sm:grid-cols-3">
            <h2 class="text-base font-bold sm:col-span-3">{{ $editingId ? __('Edit academic year') : __('New academic year') }}</h2>
            <div><label class="label">{{ __('Name') }}</label><input wire:model="name" class="input" dir="ltr">@error('name')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Starts') }}</label><input type="date" wire:model="starts_on" class="input" dir="ltr">@error('starts_on')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Ends') }}</label><input type="date" wire:model="ends_on" class="input" dir="ltr">@error('ends_on')<p class="err">{{ $message }}</p>@enderror</div>
            @unless ($editingId)<p class="text-xs text-slate-500 sm:col-span-3">{{ __('Two terms are created automatically by splitting the year in half.') }}</p>@endunless
            <div class="flex gap-2 sm:col-span-3"><button class="btn-primary">{{ __('Save') }}</button><button type="button" class="btn-ghost" wire:click="resetForm">{{ __('Cancel') }}</button></div>
        </form>
    @endif

    @if ($showPromotion)
        <section class="card animate-rise space-y-5 ring-2 ring-brand-500/40">
            <div><h2 class="text-lg font-bold text-slate-900">{{ __('Promote students') }}</h2>
                <p class="text-sm text-slate-500">{{ __('Choose where each class goes next year. Review the summary before applying. Running it twice is safe.') }}</p></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label">{{ __('From year') }}</label><select wire:model.live="fromId" class="input">@foreach ($years as $y)<option value="{{ $y->id }}">{{ $y->name }}</option>@endforeach</select></div>
                <div><label class="label">{{ __('To year') }}</label><select wire:model="toId" class="input"><option value="">—</option>@foreach ($years as $y)@if ($y->id !== $fromId)<option value="{{ $y->id }}">{{ $y->name }}</option>@endif @endforeach</select>@error('toId')<p class="err">{{ $message }}</p>@enderror</div>
            </div>
            <div class="table-wrap shadow-none"><table class="tbl">
                <thead><tr><th>{{ __('Class') }}</th><th>{{ __('Moves to') }}</th></tr></thead>
                <tbody>@forelse ($map as $fromClass => $target)
                    <tr wire:key="m{{ $fromClass }}"><td class="font-semibold">{{ $classes[$fromClass]->name }} {{ $classes[$fromClass]->section }}</td>
                        <td><select wire:model="map.{{ $fromClass }}" class="input max-w-xs">
                            <option value="graduate">🎓 {{ __('Graduates') }}</option>
                            @foreach ($classes as $c)<option value="{{ $c->id }}">{{ $c->name }} {{ $c->section }}@if ($c->id == $fromClass) ({{ __('repeat') }})@endif</option>@endforeach
                        </select>@error("map.$fromClass")<p class="err">{{ $message }}</p>@enderror</td></tr>
                @empty<tr><td colspan="2"><x-empty icon="students" :title="__('No students enrolled in this year.')" class="!py-6" /></td></tr>@endforelse</tbody>
            </table></div>

            @if ($previewRows)
                <div class="rounded-xl bg-brand-50 p-4 ring-1 ring-brand-200">
                    <div class="mb-2 font-semibold text-brand-800">{{ __('Summary') }}</div>
                    <ul class="space-y-1 text-sm text-brand-800">
                        @foreach ($previewRows as $r)
                            <li><b class="font-num">{{ $r['students'] }}</b> {{ __('students') }}: {{ $r['from'] }} → {{ $r['graduate'] ? __('Graduates') : $r['to'] }}@if ($r['skipped']) <span class="text-brand-700/70">({{ __(':n already moved', ['n' => $r['skipped']]) }})</span>@endif</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="makeCurrent" class="h-4 w-4 rounded border-slate-300 text-brand-600"> {{ __('Make the new year the current year after promotion') }}</label>
            <div class="flex flex-wrap gap-2">
                <button class="btn-ghost" wire:click="preview">{{ __('Preview') }}</button>
                @if ($previewRows)<button class="btn-primary" wire:click="apply" wire:confirm="{{ __('Apply the promotion now?') }}">{{ __('Apply promotion') }}</button>@endif
                <button class="btn-ghost" wire:click="$set('showPromotion', false)">{{ __('Cancel') }}</button>
            </div>
        </section>
    @endif

    <div class="stagger grid gap-4 md:grid-cols-2">
        @foreach ($years as $y)
            <article wire:key="y{{ $y->id }}" class="card space-y-4 {{ $y->is_current ? 'ring-2 ring-brand-500' : '' }}">
                <div class="flex items-start justify-between gap-3">
                    <div><div class="flex items-center gap-2"><h3 class="font-display text-2xl font-bold text-slate-900 font-num" dir="ltr">{{ $y->name }}</h3>@if ($y->is_current)<span class="badge-brand">{{ __('Current') }}</span>@endif</div>
                        <div class="text-sm text-slate-500 font-num" dir="ltr" style="text-align:start">{{ $y->starts_on->toDateString() }} → {{ $y->ends_on->toDateString() }}</div></div>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center text-sm">
                    <div class="rounded-xl bg-slate-50 py-2"><div class="text-lg font-bold font-num">{{ $y->enrollments_count }}</div><div class="text-xs text-slate-500">{{ __('students') }}</div></div>
                    <div class="rounded-xl bg-slate-50 py-2"><div class="text-lg font-bold font-num">{{ $y->exams_count }}</div><div class="text-xs text-slate-500">{{ __('Exams') }}</div></div>
                    <div class="rounded-xl bg-slate-50 py-2"><div class="text-lg font-bold font-num">{{ $y->fees_count }}</div><div class="text-xs text-slate-500">{{ __('Instalments') }}</div></div>
                </div>
                <div class="flex flex-wrap gap-1.5">@foreach ($y->terms as $t)<span class="badge-slate">{{ $t->name }}</span>@endforeach</div>
                <div class="flex flex-wrap gap-x-4 gap-y-1 border-t border-slate-100 pt-3 text-sm">
                    @unless ($y->is_current)<button class="link" wire:click="makeCurrentYear({{ $y->id }})" wire:confirm="{{ __('Make this the current academic year? Students move to the class they are enrolled in for it.') }}">{{ __('Make current') }}</button>@endunless
                    <button class="font-medium text-slate-600 hover:underline" wire:click="edit({{ $y->id }})">{{ __('Edit') }}</button>
                    @if (! $y->is_current && $y->exams_count === 0 && $y->fees_count === 0)<button class="link-danger" wire:click="delete({{ $y->id }})" wire:confirm="{{ __('Delete this academic year?') }}">{{ __('Delete') }}</button>@endif
                </div>
            </article>
        @endforeach
    </div>
</div>
