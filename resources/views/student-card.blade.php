<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><title>{{ $student->name }}</title>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
<style>
 body{font-family:Tajawal,sans-serif;margin:0;display:flex;justify-content:center;padding:24px;background:#f1f5f9}
 .c{width:340px;border-radius:16px;background:#fff;box-shadow:0 2px 10px #0002;overflow:hidden;text-align:center}
 .h{background:#4f46e5;color:#fff;padding:12px;font-weight:700}.b{padding:16px}
 .n{font-size:20px;font-weight:700}.m{color:#64748b;margin:4px 0 12px}
 @media print{body{background:#fff}.c{box-shadow:none;border:1px solid #cbd5e1}button{display:none}}
</style></head>
<body><div class="c"><div class="h">🎓 {{ config('school.name') }}</div>
<div class="b"><div class="n">{{ $student->name }}</div>
<div class="m">{{ $student->schoolClass->name }} {{ $student->schoolClass->section }} · {{ $student->student_no }}</div>
{!! $qr !!}<br><button onclick="window.print()">🖨 {{ __('Print') }}</button></div></div></body></html>
