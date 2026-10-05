@php
    $staff = auth()->user()->hasRole('admin', 'accountant');
    $billed = $fees->sum(fn ($f) => (float) $f->amount); $paidTotal = $fees->sum(fn ($f) => (float) $f->paid);
    $pct = $billed > 0 ? round($paidTotal / $billed * 100) : 0;
    $student = $students->firstWhere('id', $studentId);
@endphp
<div class="space-y-5">
    <x-flash />
    <div class="flex flex-wrap items-center gap-2">
        <div class="seg">
            <label class="{{ $tab === 'statement' ? 'on-present' : '' }}"><input type="radio" class="sr-only" wire:click="$set('tab','statement')"> {{ __('Statement') }}</label>
            @if ($staff)<label class="{{ $tab === 'overdue' ? 'on-present' : '' }}"><input type="radio" class="sr-only" wire:click="$set('tab','overdue')"> {{ __('Overdue') }}</label>@endif
        </div>
    </div>

    @if ($tab === 'overdue')
        <div class="table-wrap"><table class="tbl">
            <thead><tr><th>{{ __('Student') }}</th><th>{{ __('Instalment') }}</th><th>{{ __('Academic year') }}</th><th>{{ __('Due date') }}</th><th>{{ __('Balance') }}</th></tr></thead>
            <tbody>@forelse ($overdue as $f)
                <tr><td><span class="flex items-center gap-3"><x-avatar :name="$f->student->name" size="h-8 w-8 text-xs" /><span class="font-semibold">{{ $f->student->name }}</span></span></td>
                    <td>{{ $f->title }}</td><td class="font-num" dir="ltr" style="text-align:start">{{ $f->academicYear?->name ?: '—' }}</td>
                    <td dir="ltr" class="text-start font-num">{{ $f->due_date->toDateString() }}</td><td class="font-display text-base font-bold text-rose-600 font-num" dir="ltr" style="text-align:start">{{ $f->balance }}</td></tr>
            @empty<tr><td colspan="5"><x-empty icon="fees" :title="__('Nothing overdue 🎉')" /></td></tr>@endforelse</tbody>
        </table></div>
    @else
        <div class="grid gap-4 lg:grid-cols-3">
            <div class="card lg:col-span-1"><label class="label">{{ __('Student') }}</label>
                <select wire:model.live="studentId" class="input">@foreach ($students as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
            <div class="card lg:col-span-2">
                <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
                    <div><div class="eyebrow">{{ __('Academic year') }} <span class="font-num">{{ $year?->name }}</span></div>
                        <div class="font-display text-3xl font-bold text-slate-900 font-num" dir="ltr" style="text-align:start">{{ number_format($billed - $paidTotal, 2) }} <span class="text-sm font-medium text-slate-500">{{ __('remaining') }}</span></div></div>
                    <div class="text-sm text-slate-500 font-num" dir="ltr">{{ number_format($paidTotal, 2) }} / {{ number_format($billed, 2) }}</div>
                </div>
                <div class="h-3 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-brand-400 transition-all" style="width: {{ $pct }}%"></div></div>
            </div>
        </div>

        <div class="table-wrap"><table class="tbl">
            <thead><tr><th>{{ __('Instalment') }}</th><th>{{ __('Due date') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Paid') }}</th><th>{{ __('Balance') }}</th><th>{{ __('Status') }}</th>@if($staff)<th></th>@endif</tr></thead>
            <tbody>
            @forelse ($fees as $f)
                <tr wire:key="f{{ $f->id }}">
                    <td class="font-semibold">{{ $f->title }}
                        @foreach ($f->payments as $pay)<a class="ms-2 inline-flex items-center gap-1 rounded-md bg-brand-50 px-1.5 py-0.5 text-xs font-semibold text-brand-700 hover:bg-brand-100" target="_blank" href="{{ route('receipt', $pay) }}" title="{{ __('Receipt') }} · {{ $pay->paid_at->format('Y-m-d') }}">🧾 <span class="font-num">{{ number_format((float) $pay->amount, 0) }}</span></a>@endforeach</td>
                    <td dir="ltr" class="text-start font-num">{{ $f->due_date->toDateString() }}</td>
                    <td dir="ltr" class="text-start font-num">{{ $f->amount }}</td><td dir="ltr" class="text-start font-num">{{ $f->paid }}</td><td dir="ltr" class="text-start font-bold font-num">{{ $f->balance }}</td>
                    <td><x-status-badge :status="$f->status" /></td>
                    @if ($staff)<td class="text-end">@if ((float) $f->balance > 0)<button class="btn-soft btn-sm" wire:click="startPay({{ $f->id }})">{{ __('Receive payment') }}</button>@endif</td>@endif
                </tr>
                @if ($payFeeId === $f->id)
                    <tr wire:key="p{{ $f->id }}"><td colspan="7" class="bg-brand-50/60">
                        <form wire:submit="pay" class="flex flex-wrap items-end gap-3 py-1">
                            <div><label class="label">{{ __('Amount') }}</label><input wire:model="payAmount" class="input w-36" dir="ltr">@error('payAmount')<p class="err">{{ $message }}</p>@enderror</div>
                            <div><label class="label">{{ __('Method') }}</label><select wire:model="payMethod" class="input"><option value="cash">{{ __('Cash') }}</option><option value="card">{{ __('Card') }}</option><option value="transfer">{{ __('Transfer') }}</option></select></div>
                            <div><label class="label">{{ __('Reference') }}</label><input wire:model="payRef" class="input" dir="ltr"></div>
                            <button class="btn-primary">{{ __('Confirm') }}</button>
                            <button type="button" class="btn-ghost" wire:click="$set('payFeeId', null)">{{ __('Cancel') }}</button>
                        </form></td></tr>
                @endif
            @empty
                <tr><td colspan="7"><x-empty icon="fees" :title="__('No instalments yet.')">{{ __('No instalments in this academic year.') }}</x-empty></td></tr>
            @endforelse
            </tbody>
        </table></div>

        @if ($staff && $studentId)
            <details class="card group"><summary class="flex cursor-pointer list-none items-center justify-between text-base font-bold">
                <span class="flex items-center gap-2"><x-icon name="plus" class="h-5 w-5 text-brand-600" /> {{ __('New instalment plan') }}</span><x-icon name="chevron" class="h-5 w-5 text-slate-400 transition group-open:rotate-180" /></summary>
                <form wire:submit="createPlan" class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div><label class="label">{{ __('Title') }}</label><input wire:model="title" class="input" placeholder="{{ __('Tuition 2026') }}">@error('title')<p class="err">{{ $message }}</p>@enderror</div>
                    <div><label class="label">{{ __('Total amount') }}</label><input wire:model="total" class="input" dir="ltr">@error('total')<p class="err">{{ $message }}</p>@enderror</div>
                    <div><label class="label">{{ __('Number of instalments') }}</label><input type="number" wire:model="instalments" class="input" dir="ltr"></div>
                    <div><label class="label">{{ __('First due date') }}</label><input type="date" wire:model="firstDue" class="input" dir="ltr"></div>
                    <div class="flex items-end"><button class="btn-primary w-full">{{ __('Create plan') }}</button></div>
                </form></details>
        @endif
    @endif
</div>
