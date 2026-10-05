<div class="grid gap-6 lg:grid-cols-2">
    <div class="lg:col-span-2"><x-flash /></div>
    <section class="space-y-3">
        <h2 class="font-bold">{{ __('Classes') }}</h2>
        <form wire:submit="addClass" class="card flex flex-wrap items-end gap-2">
            <div class="min-w-40 flex-1"><label class="label">{{ __('Class') }}</label><input wire:model="className" class="input" placeholder="{{ __('Grade 5') }}">@error('className')<p class="err">{{ $message }}</p>@enderror</div>
            <div class="w-24"><label class="label">{{ __('Section') }}</label><input wire:model="classSection" class="input"></div>
            <button class="btn-primary">＋</button>
        </form>
        <div class="table-wrap"><table class="tbl"><tbody>
            @forelse ($classes as $c)
                <tr wire:key="c{{ $c->id }}"><td class="font-medium">{{ $c->name }} {{ $c->section }}</td><td class="text-slate-500">{{ $c->students_count }} {{ __('students') }}</td>
                    <td class="text-end"><button class="text-rose-600 hover:underline" wire:click="deleteClass({{ $c->id }})" wire:confirm="{{ __('Delete this class?') }}">{{ __('Delete') }}</button></td></tr>
            @empty<tr><td class="py-6 text-center text-slate-400">{{ __('No classes yet.') }}</td></tr>@endforelse
        </tbody></table></div>
    </section>
    <section class="space-y-3">
        <h2 class="font-bold">{{ __('Subjects') }}</h2>
        <form wire:submit="addSubject" class="card flex flex-wrap items-end gap-2">
            <div class="min-w-40 flex-1"><label class="label">{{ __('Subject') }}</label><input wire:model="subjectName" class="input">@error('subjectName')<p class="err">{{ $message }}</p>@enderror</div>
            <div class="w-28"><label class="label">{{ __('Code') }}</label><input wire:model="subjectCode" class="input" dir="ltr">@error('subjectCode')<p class="err">{{ $message }}</p>@enderror</div>
            <button class="btn-primary">＋</button>
        </form>
        <div class="table-wrap"><table class="tbl"><tbody>
            @forelse ($subjects as $s)
                <tr wire:key="sb{{ $s->id }}"><td class="font-medium">{{ $s->name }}</td><td dir="ltr" class="text-start text-slate-500">{{ $s->code }}</td>
                    <td class="text-end"><button class="text-rose-600 hover:underline" wire:click="deleteSubject({{ $s->id }})" wire:confirm="{{ __('Delete this subject?') }}">{{ __('Delete') }}</button></td></tr>
            @empty<tr><td class="py-6 text-center text-slate-400">{{ __('No subjects yet.') }}</td></tr>@endforelse
        </tbody></table></div>
    </section>
</div>
