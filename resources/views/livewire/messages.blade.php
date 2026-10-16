<div class="space-y-4">
    <div class="card space-y-3">
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-base font-bold">{{ __('Message delivery') }}</h2>
            <span class="{{ $setup['channel'] === 'log' ? 'badge-amber' : ($setup['missing'] ? 'badge-red' : 'badge-green') }}">
                {{ $setup['channel'] === 'log' ? __('Log only (nothing is sent)') : strtoupper($setup['channel']) }}</span>
        </div>
        @if ($setup['missing'])
            <p class="text-sm text-red-700">{{ __('Missing settings in the .env file: :k', ['k' => implode(', ', $setup['missing'])]) }}</p>
        @endif
        @unless ($setup['country'])
            <p class="text-xs text-slate-500">{{ __('Tip: set SCHOOL_COUNTRY_CODE (for example 962) so numbers like 0791234567 are understood.') }}</p>
        @endunless
        <form wire:submit="sendTest" class="flex flex-wrap items-start gap-2">
            <div class="min-w-48 flex-1"><input wire:model="testPhone" class="input" dir="ltr" placeholder="+962791234567" aria-label="{{ __('Phone') }}">@error('testPhone')<p class="err">{{ $message }}</p>@enderror</div>
            <button class="btn-primary" wire:loading.attr="disabled">{{ __('Send a test message') }}</button>
        </form>
        @if ($testResult)
            <p class="rounded-lg px-3 py-2 text-sm {{ $testResult['ok'] ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-800' }}" role="status" dir="auto">{{ $testResult['text'] }}</p>
        @endif
    </div>
    <div class="table-wrap"><table class="tbl">
        <thead><tr><th>{{ __('Time') }}</th><th>{{ __('Student') }}</th><th>{{ __('Phone') }}</th><th>{{ __('Type') }}</th><th>{{ __('Message') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>@forelse ($logs as $l)
            <tr wire:key="l{{ $l->id }}"><td dir="ltr" class="text-start text-slate-500">{{ $l->created_at->format('m-d H:i') }}</td>
                <td>{{ $l->student?->name }}</td><td dir="ltr" class="text-start">{{ $l->phone }}</td>
                <td><span class="badge-slate">{{ __('msg.'.$l->type) }}</span></td>
                <td class="max-w-sm truncate" title="{{ $l->message }}">{{ $l->message }}</td>
                <td><span class="{{ ['sent' => 'badge-green', 'failed' => 'badge-red'][$l->status] ?? 'badge-amber' }}" title="{{ $l->error }}">{{ __($l->status) }}</span></td></tr>
        @empty<tr><td colspan="6" class="py-8 text-center text-slate-400">{{ __('No messages yet.') }}</td></tr>@endforelse</tbody>
    </table></div>
    {{ $logs->links() }}
</div>
