<div class="space-y-5">
    @if ($result)
        <div class="card animate-rise border-emerald-200 bg-emerald-50/60">
            <h2 class="text-base font-bold text-emerald-800">{{ __('Import finished') }}</h2>
            <p class="mt-1 text-sm text-emerald-900">
                {{ __(':n students added', ['n' => $result['created']]) }} ·
                {{ __(':n updated', ['n' => $result['updated']]) }} ·
                {{ __(':n skipped', ['n' => $result['skipped']]) }} ·
                {{ __(':n with errors', ['n' => $result['errors']]) }}</p>
            <a href="{{ route('students') }}" wire:navigate class="btn-primary mt-3 inline-flex">{{ __('Go to students') }}</a>
        </div>
    @endif

    <div class="card space-y-4">
        <h2 class="text-base font-bold">{{ __('1. Prepare your file') }}</h2>
        <p class="text-sm text-slate-600">{{ __('In Excel choose Save As → CSV UTF-8. Required columns: name and class. Optional: student number, section, parent phone. Arabic or English column titles both work.') }}</p>
        <a href="{{ route('students.import.template') }}" class="btn-ghost inline-flex"><x-icon name="download" class="h-4 w-4" /> {{ __('Download a sample file') }}</a>
    </div>

    <div class="card space-y-4">
        <h2 class="text-base font-bold">{{ __('2. Upload and review') }}</h2>
        <input type="file" wire:model="file" accept=".csv,.txt,text/csv" class="input">
        <div wire:loading wire:target="file" class="text-sm text-slate-500">{{ __('Reading the file…') }}</div>
        @error('file')<p class="err">{{ $message }}</p>@enderror

        <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm">
            <label class="flex items-center gap-2"><input type="checkbox" wire:model.live="updateExisting"> {{ __('Update students whose number already exists') }}</label>
            <label class="flex items-center gap-2"><input type="checkbox" wire:model.live="createClasses"> {{ __('Create classes that do not exist yet') }}</label>
        </div>
    </div>

    @if ($a)
        @if ($a['missing'])
            <div class="card border-red-200 bg-red-50 text-sm text-red-800" role="alert">
                {{ __('These required columns were not found: :c', ['c' => implode('، ', array_map(fn ($m) => $m === 'name' ? __('Full name') : __('Class'), $a['missing']))]) }}
            </div>
        @else
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <span class="badge-green">{{ __('New') }}: {{ $counts['new'] ?? 0 }}</span>
                <span class="badge-amber">{{ __('Update') }}: {{ $counts['update'] ?? 0 }}</span>
                <span class="badge-slate">{{ __('Skipped') }}: {{ $counts['skip'] ?? 0 }}</span>
                <span class="badge-red">{{ __('Errors') }}: {{ $counts['error'] ?? 0 }}</span>
                <span class="flex-1"></span>
                <button class="btn-primary" wire:click="import" wire:loading.attr="disabled" @disabled(! (($counts['new'] ?? 0) + ($counts['update'] ?? 0)))>
                    {{ __('Import :n students', ['n' => ($counts['new'] ?? 0) + ($counts['update'] ?? 0)]) }}</button>
            </div>
            @if (($counts['error'] ?? 0) > 0)<p class="text-xs text-slate-500">{{ __('Rows with errors are left out. Fix them in the file and upload it again.') }}</p>@endif

            <div class="table-wrap"><table class="tbl">
                <thead><tr><th>{{ __('Line') }}</th><th>{{ __('Student number') }}</th><th>{{ __('Full name') }}</th><th>{{ __('Class') }}</th><th>{{ __('Parent phone') }}</th><th>{{ __('Result') }}</th></tr></thead>
                <tbody>@foreach (array_slice($a['rows'], 0, 200) as $r)
                    <tr wire:key="r{{ $r['line'] }}">
                        <td class="font-num text-slate-500">{{ $r['line'] }}</td>
                        <td dir="ltr" class="text-start font-num">{{ $r['data']['student_no'] ?: '—' }}</td>
                        <td>{{ $r['data']['name'] }}</td>
                        <td>{{ trim($r['data']['class'].' '.$r['data']['section']) }}</td>
                        <td dir="ltr" class="text-start font-num">{{ $r['data']['parent_phone'] ?: '—' }}</td>
                        <td><span class="{{ ['new' => 'badge-green', 'update' => 'badge-amber', 'skip' => 'badge-slate', 'error' => 'badge-red'][$r['status']] }}">{{ $r['message'] ?: __('New') }}</span></td>
                    </tr>@endforeach</tbody>
            </table></div>
            @if (count($a['rows']) > 200)<p class="text-xs text-slate-500">{{ __('Showing the first 200 rows; all rows are imported.') }}</p>@endif
        @endif
    @endif
</div>
