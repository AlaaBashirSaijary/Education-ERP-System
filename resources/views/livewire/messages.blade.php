<div class="space-y-4">
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
