<div class="space-y-4">
    <x-flash />
    <div class="card grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div><label class="label">{{ __('Class') }}</label>
            <select wire:model.live="classId" class="input">@foreach ($classes as $c)<option value="{{ $c->id }}">{{ $c->name }} {{ $c->section }}</option>@endforeach</select></div>
        <div class="sm:col-span-1 lg:col-span-3"><label class="label">{{ __('Exam') }}</label>
            <select wire:model.live="examId" class="input"><option value="">— {{ __('Choose an exam') }} —</option>
                @foreach ($exams as $e)<option value="{{ $e->id }}">{{ $e->name }} · {{ $e->subject->name }} ({{ $e->max_mark }})</option>@endforeach</select></div>
    </div>

    <details class="card" @if(!$exams->count()) open @endif>
        <summary class="cursor-pointer font-semibold">＋ {{ __('New exam') }}</summary>
        <form wire:submit="addExam" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div><label class="label">{{ __('Exam name') }}</label><input wire:model="examName" class="input">@error('examName')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Subject') }}</label>
                <select wire:model="subjectId" class="input"><option value="">—</option>@foreach ($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
                @error('subjectId')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Maximum mark') }}</label><input type="number" wire:model="maxMark" class="input" dir="ltr"></div>
            <div><label class="label">{{ __('Date') }}</label><input type="date" wire:model="examDate" class="input" dir="ltr"></div>
            <div class="flex items-end"><button class="btn-primary w-full">{{ __('Create exam') }}</button></div>
        </form>
        @if (auth()->user()->hasRole('admin'))
            <div class="mt-4 flex flex-wrap items-end gap-2 border-t pt-4">
                <div><label class="label">{{ __('New subject') }}</label><input wire:model="subjectName" class="input"></div>
                <div><label class="label">{{ __('Code') }}</label><input wire:model="subjectCode" class="input w-28" dir="ltr"></div>
                <button class="btn-ghost" wire:click="addSubject">＋</button>
                @error('subjectName')<p class="err">{{ $message }}</p>@enderror @error('subjectCode')<p class="err">{{ $message }}</p>@enderror
            </div>
        @endif
    </details>

    @if ($exam)
        <div class="table-wrap">
            <table class="tbl">
                <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Mark') }} / {{ $exam->max_mark }}</th></tr></thead>
                <tbody>
                @foreach ($students as $s)
                    <tr wire:key="m{{ $s->id }}"><td class="font-medium">{{ $s->name }}</td>
                        <td><input type="number" step="0.25" min="0" max="{{ $exam->max_mark }}" wire:model="marks.{{ $s->id }}" class="input w-28" dir="ltr">
                            @error("marks.$s->id")<p class="err">{{ $message }}</p>@enderror</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <button class="btn-primary" wire:click="saveMarks">{{ __('Save marks') }}</button>
    @endif
</div>
