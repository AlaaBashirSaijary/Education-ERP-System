<div class="space-y-4">
    <x-flash />
    <div class="flex flex-wrap items-center justify-between gap-3">
        <input type="search" wire:model.live.debounce.300ms="search" class="input max-w-xs" placeholder="{{ __('Search by name or number') }}">
        @if (auth()->user()->hasRole('admin'))
            <button class="btn-primary" wire:click="$toggle('showForm')">＋ {{ __('Add student') }}</button>
        @endif
    </div>

    @if ($showForm)
        <form wire:submit="save" class="card grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div><label class="label">{{ __('Student number') }}</label><input wire:model="student_no" class="input" dir="ltr">@error('student_no')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Full name') }}</label><input wire:model="name" class="input">@error('name')<p class="err">{{ $message }}</p>@enderror</div>
            <div>
                <label class="label">{{ __('Class') }}</label>
                <select wire:model="school_class_id" class="input">
                    <option value="">—</option>
                    @foreach ($classes as $c)<option value="{{ $c->id }}">{{ $c->name }} {{ $c->section }}</option>@endforeach
                </select>
                @error('school_class_id')<p class="err">{{ $message }}</p>@enderror
                <div class="mt-2 flex gap-2">
                    <input wire:model="newClass" class="input" placeholder="{{ __('New class') }}">
                    <input wire:model="newSection" class="input w-20" placeholder="{{ __('Section') }}">
                    <button type="button" class="btn-ghost" wire:click="addClass">＋</button>
                </div>
            </div>
            <div><label class="label">{{ __('Parent phone (WhatsApp)') }}</label><input wire:model="parent_phone" class="input" dir="ltr" placeholder="+9627XXXXXXXX">@error('parent_phone')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Parent name') }}</label><input wire:model="parent_name" class="input"></div>
            <div><label class="label">{{ __('Parent email (creates a login)') }}</label><input wire:model="parent_email" class="input" dir="ltr" type="email">@error('parent_email')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Fingerprint ID (optional)') }}</label><input wire:model="fingerprint_id" class="input" dir="ltr">@error('fingerprint_id')<p class="err">{{ $message }}</p>@enderror</div>
            <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-3">
                <button class="btn-primary">{{ __('Save') }}</button>
                <button type="button" class="btn-ghost" wire:click="$set('showForm', false)">{{ __('Cancel') }}</button>
            </div>
        </form>
    @endif

    <div class="table-wrap">
        <table class="tbl">
            <thead><tr><th>#</th><th>{{ __('Name') }}</th><th>{{ __('Class') }}</th><th>{{ __('Parent phone') }}</th><th></th></tr></thead>
            <tbody>
            @forelse ($students as $s)
                <tr wire:key="s{{ $s->id }}">
                    <td dir="ltr" class="text-start text-slate-500">{{ $s->student_no }}</td>
                    <td class="font-medium">{{ $s->name }}</td>
                    <td>{{ $s->schoolClass->name }} {{ $s->schoolClass->section }}</td>
                    <td dir="ltr" class="text-start">{{ $s->parent_phone ?: '—' }}</td>
                    <td class="space-x-3 text-end rtl:space-x-reverse">
                        <a class="text-indigo-600 hover:underline" href="{{ route('report-card', $s) }}" wire:navigate>{{ __('Report card') }}</a>
                        @if (auth()->user()->hasRole('admin'))
                            <a class="text-indigo-600 hover:underline" target="_blank" href="{{ route('student-card', $s) }}">{{ __('ID card') }}</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-8 text-center text-slate-400">{{ __('No students yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $students->links() }}
</div>
