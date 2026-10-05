<div class="space-y-4">
    <x-flash />
    @php $staff = auth()->user()->hasRole('admin', 'accountant'); @endphp
    <div class="flex flex-wrap items-center gap-2">
        <button class="{{ $tab === 'statement' ? 'btn-primary' : 'btn-ghost' }}" wire:click="$set('tab','statement')">{{ __('Statement') }}</button>
        @if ($staff)<button class="{{ $tab === 'overdue' ? 'btn-primary' : 'btn-ghost' }}" wire:click="$set('tab','overdue')">{{ __('Overdue') }}</button>@endif
    </div>

    @if ($tab === 'overdue')
        <div class="table-wrap"><table class="tbl">
            <thead><tr><th>{{ __('Student') }}</th><th>{{ __('Instalment') }}</th><th>{{ __('Due date') }}</th><th>{{ __('Balance') }}</th></tr></thead>
            <tbody>@forelse ($overdue as $f)
                <tr><td class="font-medium">{{ $f->student->name }}</td><td>{{ $f->title }}</td><td dir="ltr" class="text-start">{{ $f->due_date->toDateString() }}</td><td class="font-bold text-rose-600" dir="ltr">{{ $f->balance }}</td></tr>
            @empty<tr><td colspan="4" class="py-8 text-center text-slate-400">{{ __('Nothing overdue 🎉') }}</td></tr>@endforelse</tbody>
        </table></div>
    @else
        <div class="card max-w-md"><label class="label">{{ __('Student') }}</label>
            <select wire:model.live="studentId" class="input">@foreach ($students as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>

        <div class="table-wrap"><table class="tbl">
            <thead><tr><th>{{ __('Instalment') }}</th><th>{{ __('Due date') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Paid') }}</th><th>{{ __('Balance') }}</th><th>{{ __('Status') }}</th>@if($staff)<th></th>@endif</tr></thead>
            <tbody>
            @forelse ($fees as $f)
                <tr wire:key="f{{ $f->id }}">
                    <td class="font-medium">{{ $f->title }}
                        @foreach ($f->payments as $pay)<a class="ms-2 text-xs text-indigo-600 hover:underline" target="_blank" href="{{ route('receipt', $pay) }}" title="{{ $pay->paid_at->format('Y-m-d') }}">🧾 {{ number_format((float) $pay->amount, 0) }}</a>@endforeach</td><td dir="ltr" class="text-start">{{ $f->due_date->toDateString() }}</td>
                    <td dir="ltr" class="text-start">{{ $f->amount }}</td><td dir="ltr" class="text-start">{{ $f->paid }}</td><td dir="ltr" class="text-start font-bold">{{ $f->balance }}</td>
                    <td><x-status-badge :status="$f->status" /></td>
                    @if ($staff)<td class="text-end">@if ((float) $f->balance > 0)<button class="btn-ghost" wire:click="startPay({{ $f->id }})">{{ __('Receive payment') }}</button>@endif</td>@endif
                </tr>
                @if ($payFeeId === $f->id)
                    <tr wire:key="p{{ $f->id }}"><td colspan="7" class="bg-indigo-50">
                        <form wire:submit="pay" class="flex flex-wrap items-end gap-3">
                            <div><label class="label">{{ __('Amount') }}</label><input wire:model="payAmount" class="input w-32" dir="ltr">@error('payAmount')<p class="err">{{ $message }}</p>@enderror</div>
                            <div><label class="label">{{ __('Method') }}</label><select wire:model="payMethod" class="input"><option value="cash">{{ __('Cash') }}</option><option value="card">{{ __('Card') }}</option><option value="transfer">{{ __('Transfer') }}</option></select></div>
                            <div><label class="label">{{ __('Reference') }}</label><input wire:model="payRef" class="input" dir="ltr"></div>
                            <button class="btn-primary">{{ __('Confirm') }}</button>
                            <button type="button" class="btn-ghost" wire:click="$set('payFeeId', null)">{{ __('Cancel') }}</button>
                        </form></td></tr>
                @endif
            @empty
                <tr><td colspan="7" class="py-8 text-center text-slate-400">{{ __('No instalments yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table></div>

        @if ($staff && $studentId)
            <details class="card"><summary class="cursor-pointer font-semibold">＋ {{ __('New instalment plan') }}</summary>
                <form wire:submit="createPlan" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div><label class="label">{{ __('Title') }}</label><input wire:model="title" class="input" placeholder="{{ __('Tuition 2026') }}">@error('title')<p class="err">{{ $message }}</p>@enderror</div>
                    <div><label class="label">{{ __('Total amount') }}</label><input wire:model="total" class="input" dir="ltr">@error('total')<p class="err">{{ $message }}</p>@enderror</div>
                    <div><label class="label">{{ __('Number of instalments') }}</label><input type="number" wire:model="instalments" class="input" dir="ltr"></div>
                    <div><label class="label">{{ __('First due date') }}</label><input type="date" wire:model="firstDue" class="input" dir="ltr"></div>
                    <div class="flex items-end"><button class="btn-primary w-full">{{ __('Create plan') }}</button></div>
                </form></details>
        @endif
    @endif
</div>
