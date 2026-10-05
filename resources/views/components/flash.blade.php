@if (session('ok'))
    <div class="mb-4 flex animate-rise items-center gap-3 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200" x-data x-init="setTimeout(() => $el.remove(), 5000)" role="status">
        <x-icon name="check" class="h-5 w-5 shrink-0" /> {{ session('ok') }}</div>
@endif
@if (session('warn'))
    <div class="mb-4 flex animate-rise items-center gap-3 rounded-xl bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 ring-1 ring-amber-200" role="alert">⚠ {{ session('warn') }}</div>
@endif
