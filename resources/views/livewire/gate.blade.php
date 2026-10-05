<div class="mx-auto grid max-w-6xl gap-5 lg:grid-cols-5"
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
     x-on:scanned.window="$refs.code.focus()">
    @php $last = $recent[0] ?? null; @endphp

    {{-- Scanner --}}
    <section class="space-y-4 lg:col-span-2">
        <div class="card space-y-4">
            <div class="flex items-center gap-3"><span class="grid h-11 w-11 place-items-center rounded-xl bg-brand-100 text-brand-700"><x-icon name="gate" class="h-5 w-5" /></span>
                <p class="text-sm text-slate-500">{{ __('Scan the student card with a barcode/QR scanner or the camera. The cursor stays in the box.') }}</p></div>
            <form wire:submit="scan()" class="space-y-3">
                <input x-ref="code" wire:model="code" class="input !py-4 text-center text-xl tracking-wider" dir="ltr" autofocus autocomplete="off" placeholder="{{ __('Scan code…') }}" aria-label="{{ __('Scan code…') }}">
                <button class="btn-primary w-full !py-3">{{ __('Check in') }}</button>
            </form>
            <div class="flex gap-2">
                <button type="button" class="btn-ghost flex-1" x-show="!camera" @click="start()">📷 {{ __('Use camera') }}</button>
                <button type="button" class="btn-ghost flex-1" x-show="camera" x-cloak @click="stop()">{{ __('Stop camera') }}</button>
            </div>
            <div x-show="camera" x-cloak wire:ignore><div id="reader" class="mx-auto w-full max-w-sm overflow-hidden rounded-xl"></div></div>
        </div>
    </section>

    {{-- Result + history --}}
    <section class="space-y-4 lg:col-span-3">
        @if ($error)
            <div wire:key="r{{ $scans }}" class="animate-rise rounded-3xl bg-rose-600 p-8 text-center text-white shadow-pop" role="alert">
                <div class="font-display text-5xl">✕</div>
                <div class="mt-2 font-display text-3xl font-bold">{{ $error }}</div>
            </div>
        @elseif ($last)
            @php $bg = ['present' => 'bg-emerald-600', 'late' => 'bg-amber-500 !text-slate-900'][$last['status']] ?? 'bg-slate-600'; @endphp
            <div wire:key="r{{ $scans }}" class="animate-rise rounded-3xl {{ $bg }} p-8 text-center text-white shadow-pop" role="status">
                <x-avatar :name="$last['name']" size="mx-auto h-24 w-24 text-3xl !bg-white/25 !text-inherit" />
                <div class="mt-4 font-display text-4xl font-bold">{{ $last['name'] }}</div>
                <div class="mt-2 text-lg font-semibold opacity-90">{{ __($last['status']) }} · <span class="font-num" dir="ltr">{{ $last['time'] }}</span></div>
                @if ($last['duplicate'])<div class="mt-2 text-sm opacity-80">{{ __('already checked in') }}</div>@endif
            </div>
        @else
            <div class="card-flat"><x-empty icon="gate" :title="__('Waiting for the first scan')">{{ __('The result of each scan appears here in large type.') }}</x-empty></div>
        @endif

        <div class="table-wrap">
            <table class="tbl">
                <thead><tr><th>{{ __('Student') }}</th><th>{{ __('Status') }}</th><th>{{ __('Time') }}</th></tr></thead>
                <tbody>
                @forelse ($recent as $r)
                    <tr><td><span class="flex items-center gap-3"><x-avatar :name="$r['name']" size="h-8 w-8 text-xs" /><span class="font-semibold">{{ $r['name'] }}</span></span></td>
                        <td><x-status-badge :status="$r['status']" /> @if ($r['duplicate'])<span class="text-xs text-slate-400">({{ __('already checked in') }})</span>@endif</td>
                        <td dir="ltr" class="text-start font-num">{{ $r['time'] }}</td></tr>
                @empty
                    <tr><td colspan="3" class="py-6 text-center text-slate-400">{{ __('No check-ins yet.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
