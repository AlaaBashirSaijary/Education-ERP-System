@props(['variant' => 'sidebar'])
{{-- "Install app" button. Appears only when the browser can install the app (or on iOS, where we show the Share-sheet steps). --}}
<div x-data="pwaInstall" x-show="available" x-cloak {{ $attributes }}>
    @if ($variant === 'sidebar')
        <button type="button" @click="install()" class="navlink w-full"><x-icon name="download" /> <span class="truncate">{{ __('Install the app') }}</span></button>
    @else
        <button type="button" @click="install()" class="btn-ghost !px-3" title="{{ __('Install the app') }}"><x-icon name="download" class="h-4 w-4" /> <span class="hidden sm:inline">{{ __('Install the app') }}</span></button>
    @endif

    {{-- iOS: no install event, so explain the Share sheet --}}
    <div x-show="help" x-cloak x-transition.opacity @keydown.escape.window="help = false" @click.self="help = false"
         class="fixed inset-0 z-[60] grid place-items-end bg-black/60 p-4 sm:place-items-center" role="dialog" aria-modal="true" aria-label="{{ __('Install the app') }}">
        <div class="w-full max-w-sm space-y-4 rounded-3xl bg-surface p-6 text-slate-800 shadow-pop" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
            <div class="flex items-center gap-3"><x-emblem class="h-10 w-10 text-brand-600" /><h2 class="font-display text-xl font-semibold">{{ __('Install the app') }}</h2></div>
            <ol class="space-y-3 text-sm">
                <li class="flex items-start gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-brand-100 font-bold text-brand-700">1</span>
                    <span>{{ __('Open this page in Safari, then tap the Share button') }} <x-icon name="share" class="inline h-4 w-4 align-text-bottom" /></span></li>
                <li class="flex items-start gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-brand-100 font-bold text-brand-700">2</span>
                    <span>{{ __('Choose “Add to Home Screen”') }}</span></li>
                <li class="flex items-start gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-brand-100 font-bold text-brand-700">3</span>
                    <span>{{ __('Tap “Add”. The app appears on your home screen.') }}</span></li>
            </ol>
            <button type="button" class="btn-primary w-full" @click="help = false">{{ __('Got it') }}</button>
        </div>
    </div>
</div>
