<div class="mx-auto max-w-2xl space-y-4"
     x-data="{ camera: false, scanner: null,
        async start() {
            if (!window.Html5Qrcode) {
                await new Promise((res, rej) => { const s = document.createElement('script'); s.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js'; s.onload = res; s.onerror = rej; document.head.appendChild(s); });
            }
            this.camera = true;
            await this.$nextTick();
            this.scanner = new Html5Qrcode('reader');
            let last = 0;
            await this.scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: 250 }, (text) => {
                if (Date.now() - last < 2500) return; last = Date.now();
                $wire.scan(text);
            });
        },
        async stop() { await this.scanner?.stop(); this.camera = false; } }"
     x-on:scanned.window="$refs.code.focus(); if ($event.detail.ok) { new Audio('data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQAAAAA=').play().catch(()=>{}) }">
    <div class="card space-y-3">
        <p class="text-sm text-slate-500">{{ __('Scan the student card with a barcode/QR scanner or the camera. The cursor stays in the box.') }}</p>
        <form wire:submit="scan()" class="flex gap-2">
            <input x-ref="code" wire:model="code" class="input text-lg" dir="ltr" autofocus autocomplete="off" placeholder="{{ __('Scan code…') }}">
            <button class="btn-primary">{{ __('Check in') }}</button>
        </form>
        <div class="flex gap-2">
            <button type="button" class="btn-ghost" x-show="!camera" @click="start()">📷 {{ __('Use camera') }}</button>
            <button type="button" class="btn-ghost" x-show="camera" x-cloak @click="stop()">{{ __('Stop camera') }}</button>
        </div>
        <div x-show="camera" x-cloak wire:ignore><div id="reader" class="mx-auto w-full max-w-sm"></div></div>
        @if ($error)<div class="rounded-lg bg-rose-50 px-4 py-3 text-rose-700 ring-1 ring-rose-200">✗ {{ $error }}</div>@endif
    </div>

    <div class="table-wrap">
        <table class="tbl">
            <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Status') }}</th><th>{{ __('Time') }}</th></tr></thead>
            <tbody>
            @forelse ($recent as $r)
                <tr><td class="font-medium">{{ $r['name'] }}</td>
                    <td><x-status-badge :status="$r['status']" /> @if ($r['duplicate'])<span class="text-xs text-slate-400">({{ __('already checked in') }})</span>@endif</td>
                    <td dir="ltr" class="text-start">{{ $r['time'] }}</td></tr>
            @empty
                <tr><td colspan="3" class="py-8 text-center text-slate-400">{{ __('No check-ins yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
