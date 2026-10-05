@props(['status' => null])
@php
    $map = ['present' => 'badge-green', 'late' => 'badge-amber', 'absent' => 'badge-red',
            'paid' => 'badge-green', 'partial' => 'badge-amber', 'overdue' => 'badge-red', 'unpaid' => 'badge-slate', 'leave' => 'badge-brand'];
@endphp
<span class="{{ $map[$status] ?? 'badge-slate' }}">{{ $status ? __($status) : __('Not recorded') }}</span>
