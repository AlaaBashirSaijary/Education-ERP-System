<div class="space-y-5">
    <x-flash />
    @php $admin = auth()->user()->hasRole('admin'); @endphp
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-1 flex-wrap gap-2">
            <div class="relative w-full max-w-xs">
                <span class="pointer-events-none absolute inset-y-0 start-3 grid place-items-center text-slate-400"><x-icon name="students" class="h-4 w-4" /></span>
                <input type="search" wire:model.live.debounce.300ms="search" class="input ps-10" placeholder="{{ __('Search by name or number') }}">
            </div>
            <select wire:model.live="classFilter" class="input w-48"><option value="">{{ __('All classes') }}</option>
                @foreach ($classes as $c)<option value="{{ $c->id }}">{{ $c->name }} {{ $c->section }}</option>@endforeach</select>
            @unless ($isCurrent)<span class="badge-amber self-center">{{ __('Roster of :y', ['y' => $year?->name]) }}</span>@endunless
        </div>
        @if ($admin)<button class="btn-primary" wire:click="create"><x-icon name="plus" class="h-4 w-4" /> {{ __('Add student') }}</button>@endif
    </div>

    @if ($showForm && $admin)
        <form wire:submit="save" class="card grid animate-rise gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <h2 class="text-base font-bold sm:col-span-2 lg:col-span-3">{{ $editingId ? __('Edit student') : __('Add student') }}</h2>
            <div><label class="label">{{ __('Student number') }}</label><input wire:model="student_no" class="input" dir="ltr">@error('student_no')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Full name') }}</label><input wire:model="name" class="input">@error('name')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Class') }}</label>
                <select wire:model="school_class_id" class="input"><option value="">—</option>
                    @foreach ($classes as $c)<option value="{{ $c->id }}">{{ $c->name }} {{ $c->section }}</option>@endforeach</select>
                @error('school_class_id')<p class="err">{{ $message }}</p>@enderror
                @if ($classes->isEmpty())<p class="mt-1 text-xs text-amber-700">{{ __('Create classes first in Setup.') }}</p>@endif</div>
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
            <thead><tr><th>{{ __('Student') }}</th><th>{{ __('Class') }}</th><th>{{ __('Parent') }}</th><th>{{ __('Parent phone') }}</th><th></th></tr></thead>
            <tbody>
            @forelse ($students as $s)
                @php $cls = $s->enrollments->first()?->schoolClass ?? $s->schoolClass; @endphp
                <tr wire:key="s{{ $s->id }}" class="{{ $s->active ? '' : 'opacity-60' }}">
                    <td>
                        <a href="{{ route('student-profile', $s) }}" wire:navigate class="flex items-center gap-3">
                            <x-avatar :name="$s->name" />
                            <span class="min-w-0"><span class="block truncate font-semibold text-slate-900 hover:text-brand-600">{{ $s->name }}
                                @unless ($s->active)<span class="badge-slate ms-1">{{ $s->graduated_at ? __('Graduated') : __('Inactive') }}</span>@endunless</span>
                                <span class="block text-xs text-slate-500 font-num" dir="ltr" style="text-align:start">#{{ $s->student_no }}</span></span>
                        </a>
                    </td>
                    <td>{{ $cls->name }} {{ $cls->section }}</td>
                    <td>{{ $s->parent?->name ?: '—' }}</td>
                    <td dir="ltr" class="text-start font-num">{{ $s->parent_phone ?: '—' }}</td>
                    <td class="whitespace-nowrap text-end">
                        <span class="inline-flex flex-wrap justify-end gap-x-3 gap-y-1 text-sm">
                            <a class="link" href="{{ route('student-profile', $s) }}" wire:navigate>{{ __('Profile') }}</a>
                            @if ($admin)
                                <a class="link" target="_blank" href="{{ route('student-card', $s) }}">{{ __('ID card') }}</a>
                                <button class="font-medium text-slate-600 hover:underline" wire:click="edit({{ $s->id }})">{{ __('Edit') }}</button>
                                <button class="font-medium text-amber-700 hover:underline" wire:click="toggleActive({{ $s->id }})">{{ $s->active ? __('Deactivate') : __('Activate') }}</button>
                                <button class="link-danger" wire:click="delete({{ $s->id }})" wire:confirm="{{ __('Delete this student and all of their records?') }}">{{ __('Delete') }}</button>
                            @endif
                        </span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5"><x-empty icon="students" :title="__('No students yet.')" /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $students->links() }}
</div>
