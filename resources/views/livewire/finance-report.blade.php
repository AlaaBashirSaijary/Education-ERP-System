<div class="space-y-4">
    @php
        $tot = ['billed' => $rows->sum('billed'), 'collected' => $rows->sum('collected'), 'outstanding' => $rows->sum('outstanding'), 'overdue' => $rows->sum('overdue')];
        $max = max(1, max($monthly) ?: 1);
    @endphp
    <div class="stagger grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <x-stat :label="__('Billed')" :value="number_format($tot['billed'], 2)" icon="fees" tone="brand" :note="$year?->name" />
        <x-stat :label="__('Collected')" :value="number_format($tot['collected'], 2)" icon="check" tone="ok" />
        <x-stat :label="__('Outstanding')" :value="number_format($tot['outstanding'], 2)" icon="reports" tone="warn" />
        <x-stat :label="__('Overdue')" :value="number_format($tot['overdue'], 2)" icon="announce" tone="bad" />
    </div>

    <div class="card">
        <h2 class="mb-4 font-bold">{{ __('Collections, last 6 months') }}</h2>
        <div class="flex h-40 gap-3" role="img" aria-label="{{ __('Collections, last 6 months') }}">
            @foreach ($monthly as $m => $v)
                <div class="flex flex-1 flex-col items-stretch justify-end gap-1" title="{{ $m }}: {{ number_format($v, 2) }}">
                    <span class="text-center text-xs text-slate-500" dir="ltr">{{ $v ? number_format($v, 0) : '' }}</span>
                    <div class="w-full rounded-t bg-gradient-to-t from-brand-600 to-brand-400" style="height: {{ max(2, $v / $max * 100) }}%"></div>
                    <span class="text-center text-xs text-slate-400" dir="ltr">{{ substr($m, 2) }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex justify-end"><a class="btn-ghost" href="{{ route('finance-csv') }}">⬇ {{ __('Export CSV') }}</a></div>
    <div class="table-wrap"><table class="tbl">
        <thead><tr><th>{{ __('Class') }}</th><th>{{ __('Billed') }}</th><th>{{ __('Collected') }}</th><th>{{ __('Outstanding') }}</th><th>{{ __('Overdue') }}</th></tr></thead>
        <tbody>@forelse ($rows as $r)
            <tr><td class="font-medium">{{ $r['class'] }}</td><td>{{ number_format($r['billed'], 2) }}</td><td class="text-emerald-700">{{ number_format($r['collected'], 2) }}</td>
                <td class="text-amber-600">{{ number_format($r['outstanding'], 2) }}</td><td class="font-bold text-rose-600">{{ number_format($r['overdue'], 2) }}</td></tr>
        @empty<tr><td colspan="5" class="py-8 text-center text-slate-400">{{ __('No classes yet.') }}</td></tr>@endforelse</tbody>
    </table></div>
</div>
