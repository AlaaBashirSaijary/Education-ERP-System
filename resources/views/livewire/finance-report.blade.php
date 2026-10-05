<div class="space-y-4">
    @php
        $tot = ['billed' => $rows->sum('billed'), 'collected' => $rows->sum('collected'), 'outstanding' => $rows->sum('outstanding'), 'overdue' => $rows->sum('overdue')];
        $max = max(1, max($monthly) ?: 1);
    @endphp
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['Billed', 'billed', 'text-slate-800'], ['Collected', 'collected', 'text-emerald-600'], ['Outstanding', 'outstanding', 'text-amber-600'], ['Overdue', 'overdue', 'text-rose-600']] as [$l, $k, $c])
            <div class="card"><div class="text-sm text-slate-500">{{ __($l) }}</div><div class="mt-1 text-2xl font-bold {{ $c }}">{{ number_format($tot[$k], 2) }}</div></div>
        @endforeach
    </div>

    <div class="card">
        <h2 class="mb-4 font-bold">{{ __('Collections, last 6 months') }}</h2>
        <div class="flex h-40 gap-3" role="img" aria-label="{{ __('Collections, last 6 months') }}">
            @foreach ($monthly as $m => $v)
                <div class="flex flex-1 flex-col items-stretch justify-end gap-1" title="{{ $m }}: {{ number_format($v, 2) }}">
                    <span class="text-center text-xs text-slate-500" dir="ltr">{{ $v ? number_format($v, 0) : '' }}</span>
                    <div class="w-full rounded-t bg-indigo-500" style="height: {{ max(2, $v / $max * 100) }}%"></div>
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
