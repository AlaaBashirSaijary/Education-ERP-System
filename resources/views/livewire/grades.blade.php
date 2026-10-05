<div class="space-y-5">
    <x-flash />
    <div class="card grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div><label class="label">{{ __('Class') }}</label>
            <select wire:model.live="classId" class="input">@foreach ($classes as $c)<option value="{{ $c->id }}">{{ $c->name }} {{ $c->section }}</option>@endforeach</select></div>
        <div class="sm:col-span-1 lg:col-span-2"><label class="label">{{ __('Exam') }} · <span class="font-num">{{ $year?->name }}</span></label>
            <select wire:model.live="examId" class="input"><option value="">— {{ __('Choose an exam') }} —</option>
                @foreach ($exams as $e)<option value="{{ $e->id }}">{{ $e->name }} · {{ $e->subject->name }} ({{ $e->max_mark }}){{ $e->term ? ' · '.$e->term->name : '' }}</option>@endforeach</select></div>
    </div>

    <details class="card group" @if(!$exams->count()) open @endif>
        <summary class="flex cursor-pointer list-none items-center justify-between text-base font-bold">
            <span class="flex items-center gap-2"><x-icon name="plus" class="h-5 w-5 text-brand-600" /> {{ __('New exam') }}</span>
            <x-icon name="chevron" class="h-5 w-5 text-slate-400 transition group-open:rotate-180" />
        </summary>
        <form wire:submit="addExam" class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <div class="lg:col-span-2"><label class="label">{{ __('Exam name') }}</label><input wire:model="examName" class="input">@error('examName')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Subject') }}</label>
                <select wire:model="subjectId" class="input"><option value="">—</option>@foreach ($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
                @error('subjectId')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Term') }}</label>
                <select wire:model="termId" class="input"><option value="">—</option>@foreach ($terms as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></div>
            <div><label class="label">{{ __('Maximum mark') }}</label><input type="number" wire:model="maxMark" class="input" dir="ltr"></div>
            <div><label class="label">{{ __('Date') }}</label><input type="date" wire:model="examDate" class="input" dir="ltr"></div>
            <div class="sm:col-span-2 lg:col-span-6"><button class="btn-primary">{{ __('Create exam') }}</button></div>
        </form>
        @if (auth()->user()->hasRole('admin'))
            <div class="mt-5 flex flex-wrap items-end gap-2 border-t border-slate-100 pt-4">
                <div><label class="label">{{ __('New subject') }}</label><input wire:model="subjectName" class="input"></div>
                <div><label class="label">{{ __('Code') }}</label><input wire:model="subjectCode" class="input w-28" dir="ltr"></div>
                <button class="btn-ghost" wire:click="addSubject"><x-icon name="plus" class="h-4 w-4" /></button>
                @error('subjectName')<p class="err">{{ $message }}</p>@enderror @error('subjectCode')<p class="err">{{ $message }}</p>@enderror
            </div>
        @endif
    </details>

    @if ($exam)
        <div class="table-wrap">
            <table class="tbl">
                <thead><tr><th>{{ __('Student') }}</th><th>{{ __('Mark') }} / <span class="font-num">{{ $exam->max_mark }}</span></th></tr></thead>
                <tbody>
                @foreach ($students as $s)
                    <tr wire:key="m{{ $s->id }}"><td><span class="flex items-center gap-3"><x-avatar :name="$s->name" /><span class="font-semibold">{{ $s->name }}</span></span></td>
                        <td><input type="number" step="0.25" min="0" max="{{ $exam->max_mark }}" wire:model="marks.{{ $s->id }}" class="input w-32 text-center font-bold" dir="ltr">
                            @error("marks.$s->id")<p class="err">{{ $message }}</p>@enderror</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <button class="btn-primary" wire:click="saveMarks"><x-icon name="check" class="h-4 w-4" /> {{ __('Save marks') }}</button>
    @else
        <div class="card-flat"><x-empty icon="grades" :title="__('Choose an exam')">{{ __('Pick a class and an exam to enter marks, or create a new exam.') }}</x-empty></div>
    @endif
</div>
