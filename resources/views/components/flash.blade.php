@if (session('ok'))
    <div class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200" x-data x-init="setTimeout(() => $el.remove(), 5000)">✓ {{ session('ok') }}</div>
@endif
@if (session('warn'))
    <div class="mb-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200">⚠ {{ session('warn') }}</div>
@endif
