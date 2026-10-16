@php
    $fieldName = fn ($k) => \Illuminate\Support\Facades\Lang::has('field.'.$k) ? __('field.'.$k) : str_replace('_', ' ', $k);
    $show = fn ($v) => is_bool($v) ? ($v ? __('Yes') : __('No')) : (($v === null || $v === '') ? '—' : (is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE)));
@endphp
<div class="space-y-4">
    <p class="text-sm text-slate-500">{{ __('Every change to students, marks, fees, payments and users is recorded here with who made it. Entries cannot be edited.') }}</p>

    <div class="card grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <select wire:model.live="type" class="input"><option value="">{{ __('All records') }}</option>
            @foreach ($types as $t)<option value="{{ $t }}">{{ __('audit.'.$t) }}</option>@endforeach</select>
        <select wire:model.live="event" class="input"><option value="">{{ __('All actions') }}</option>
            @foreach (['created', 'updated', 'deleted'] as $e)<option value="{{ $e }}">{{ __('audit.'.$e) }}</option>@endforeach</select>
        <select wire:model.live="userId" class="input"><option value="">{{ __('All users') }}</option>
            @foreach ($staff as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select>
        <input type="search" wire:model.live.debounce.400ms="search" class="input" placeholder="{{ __('Search the description') }}">
        <input type="date" wire:model.live="from" class="input" dir="ltr" aria-label="{{ __('From') }}">
        <input type="date" wire:model.live="to" class="input" dir="ltr" aria-label="{{ __('To') }}">
    </div>

    <div class="table-wrap"><table class="tbl">
        <thead><tr><th>{{ __('Time') }}</th><th>{{ __('User') }}</th><th>{{ __('Action') }}</th><th>{{ __('Record') }}</th><th>{{ __('Changes') }}</th></tr></thead>
        <tbody>@forelse ($logs as $l)
            <tr wire:key="a{{ $l->id }}" class="align-top">
                <td dir="ltr" class="whitespace-nowrap text-start text-slate-500">{{ $l->created_at->format('Y-m-d H:i') }}</td>
                <td class="whitespace-nowrap">{{ $l->user_name ?: '—' }}</td>
                <td><span class="{{ ['created' => 'badge-green', 'updated' => 'badge-amber', 'deleted' => 'badge-red'][$l->event] ?? 'badge-slate' }}">{{ __('audit.'.$l->event) }}</span></td>
                <td><span class="block text-xs text-slate-500">{{ __('audit.'.$l->auditable_type) }}</span>{{ $l->label }}</td>
                <td class="text-sm">
                    @if ($l->event === 'updated')
                        @foreach ($l->new_values ?? [] as $k => $new)
                            <div><span class="text-slate-500">{{ $fieldName($k) }}:</span>
                                <span class="text-red-700 line-through">{{ $show(($l->old_values ?? [])[$k] ?? null) }}</span>
                                → <span class="font-semibold text-emerald-700">{{ $show($new) }}</span></div>
                        @endforeach
                    @else
                        @foreach (array_slice($l->event === 'created' ? ($l->new_values ?? []) : ($l->old_values ?? []), 0, 6, true) as $k => $v)
                            @continue(in_array($k, ['id']))
                            <div><span class="text-slate-500">{{ $fieldName($k) }}:</span> {{ $show($v) }}</div>
                        @endforeach
                    @endif
                </td>
            </tr>
        @empty<tr><td colspan="5" class="py-8 text-center text-slate-400">{{ __('No changes recorded yet.') }}</td></tr>@endforelse</tbody>
    </table></div>
    {{ $logs->links() }}
</div>
