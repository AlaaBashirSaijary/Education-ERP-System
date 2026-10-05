<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><title>{{ __('Receipt') }} R-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</title>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
<style>
 body{font-family:Tajawal,sans-serif;margin:0;padding:24px;background:#f1f5f9;color:#1e293b}
 .r{max-width:560px;margin:auto;background:#fff;border-radius:12px;padding:28px;box-shadow:0 2px 10px #0002}
 h1{margin:0;font-size:20px}.top{display:flex;justify-content:space-between;border-bottom:2px solid #4f46e5;padding-bottom:12px;margin-bottom:16px}
 table{width:100%;border-collapse:collapse}td{padding:8px 0;border-bottom:1px solid #e2e8f0}td:last-child{text-align:end;font-weight:700}
 .amt{font-size:24px;color:#4f46e5}button{margin-top:16px;padding:8px 16px}
 @media print{body{background:#fff;padding:0}.r{box-shadow:none}button{display:none}}
</style></head>
<body><div class="r">
 <div class="top"><div><h1>🎓 {{ config('school.name') }}</h1><div>{{ __('Payment receipt') }}</div></div>
  <div dir="ltr" style="text-align:end"><b>R-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</b><br>{{ $payment->paid_at->format('Y-m-d H:i') }}</div></div>
 <table>
  <tr><td>{{ __('Student') }}</td><td>{{ $student->name }}</td></tr>
  <tr><td>{{ __('Instalment') }}</td><td>{{ $fee->title }}</td></tr>
  <tr><td>{{ __('Method') }}</td><td>{{ __(ucfirst($payment->method)) }}</td></tr>
  @if ($payment->reference)<tr><td>{{ __('Reference') }}</td><td dir="ltr">{{ $payment->reference }}</td></tr>@endif
  <tr><td>{{ __('Received by') }}</td><td>{{ $receiver?->name ?: '—' }}</td></tr>
  <tr><td>{{ __('Balance after this payment') }}</td><td dir="ltr">{{ number_format((float) $fee->amount - (float) $fee->payments->where('id', '<=', $payment->id)->sum('amount'), 2) }}</td></tr>
  <tr><td>{{ __('Amount') }}</td><td class="amt" dir="ltr">{{ number_format((float) $payment->amount, 2) }}</td></tr>
 </table>
 <button onclick="window.print()">🖨 {{ __('Print') }}</button>
</div></body></html>
