<div class="space-y-4">
    <x-flash />
    @php $admin = auth()->user()->hasRole('admin'); @endphp
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2">
            <input type="search" wire:model.live.debounce.300ms="search" class="input w-64" placeholder="{{ __('Search by name or number') }}">
            <select wire:model.live="classFilter" class="input w-48"><option value="">{{ __('All classes') }}</option>
                @foreach ($classes as $c)<option value="{{ $c->id }}">{{ $c->name }} {{ $c->section }}</option>@endforeach</select>
        </div>
        @if ($admin)<button class="btn-primary" wire:click="create">＋ {{ __('Add student') }}</button>@endif
    </div>

    @if ($showForm && $admin)
        <form wire:submit="save" class="card grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <h2 class="font-bold sm:col-span-2 lg:col-span-3">{{ $editingId ? __('Edit student') : __('Add student') }}</h2>
            <div><label class="label">{{ __('Student number') }}</label><input wire:model="student_no" class="input" dir="ltr">@error('student_no')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Full name') }}</label><input wire:model="name" class="input">@error('name')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Class') }}</label>
                <select wire:model="school_class_id" class="input"><option value="">—</option>
                    @foreach ($classes as $c)<option value="{{ $c->id }}">{{ $c->name }} {{ $c->section }}</option>@endforeach</select>
                @error('school_class_id')<p class="err">{{ $message }}</p>@enderror
                @if ($classes->isEmpty())<p class="mt-1 text-xs text-amber-600">{{ __('Create classes first in Setup.') }}</p>@endif</div>
            <div><label class="label">{{ __('Parent account') }}</label>
                <select wire:model="parent_id" class="input"><option value="">— {{ __('None') }} —</option>
                    @foreach ($parents as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->email }})</option>@endforeach</select>
                @error('parent_id')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Parent phone (WhatsApp)') }}</label><input wire:model="parent_phone" class="input" dir="ltr" placeholder="+9627XXXXXXXX">@error('parent_phone')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Fingerprint ID (optional)') }}</label><input wire:model="fingerprint_id" class="input" dir="ltr">@error('fingerprint_id')<p class="err">{{ $message }}</p>@enderror</div>
            <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-3">
                <button class="btn-primary">{{ __('Save') }}</button>
                <button type="button" class="btn-ghost" wire:click="resetForm">{{ __('Cancel') }}</button>
            </div>
        </form>
    @endif

    <div class="table-wrap">
        <table class="tbl">
            <thead><tr><th>#</th><th>{{ __('Name') }}</th><th>{{ __('Class') }}</th><th>{{ __('Parent') }}</th><th>{{ __('Parent phone') }}</th><th></th></tr></thead>
            <tbody>
            @forelse ($students as $s)
                <tr wire:key="s{{ $s->id }}" class="{{ $s->active ? '' : 'opacity-50' }}">
                    <td dir="ltr" class="text-start text-slate-500">{{ $s->student_no }}</td>
                    <td class="font-medium">{{ $s->name }} @unless($s->active)<span class="badge-slate">{{ __('Inactive') }}</span>@endunless</td>
                    <td>{{ $s->schoolClass->name }} {{ $s->schoolClass->section }}</td>
                    <td>{{ $s->parent?->name ?: '—' }}</td>
                    <td dir="ltr" class="text-start">{{ $s->parent_phone ?: '—' }}</td>
                    <td class="space-x-3 whitespace-nowrap text-end rtl:space-x-reverse">
                        <a class="text-indigo-600 hover:underline" href="{{ route('report-card', $s) }}" wire:navigate>{{ __('Report card') }}</a>
                        @if ($admin)
                            <a class="text-indigo-600 hover:underline" target="_blank" href="{{ route('student-card', $s) }}">{{ __('ID card') }}</a>
                            <button class="text-slate-600 hover:underline" wire:click="edit({{ $s->id }})">{{ __('Edit') }}</button>
                            <button class="text-amber-600 hover:underline" wire:click="toggleActive({{ $s->id }})">{{ $s->active ? __('Deactivate') : __('Activate') }}</button>
                            <button class="text-rose-600 hover:underline" wire:click="delete({{ $s->id }})" wire:confirm="{{ __('Delete this student and all of their records?') }}">{{ __('Delete') }}</button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-8 text-center text-slate-400">{{ __('No students yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $students->links() }}
</div>
