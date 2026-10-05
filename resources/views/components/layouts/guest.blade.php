@props(['title' => null, 'heading' => null, 'sub' => null, 'wide' => false])
@php $rtl = app()->getLocale() === 'ar'; @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0B2327">
    <x-pwa-head />
    <title>{{ $title ?? config('school.name') }} – {{ config('school.name') }}</title>
    <script>(function(){try{var t=localStorage.getItem('theme');if(t==='dark'||(!t&&matchMedia('(prefers-color-scheme: dark)').matches))document.documentElement.classList.add('dark')}catch(e){}})()</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen">
<div class="grid min-h-screen lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)]">

    {{-- ===== Brand panel: always dark, shows what the product does ===== --}}
    <aside class="relative hidden overflow-hidden bg-sidebar text-white lg:flex lg:flex-col lg:justify-between lg:p-12 xl:p-14">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -top-32 start-[-6rem] h-[28rem] w-[28rem] rounded-full bg-brand-600/35 blur-3xl"></div>
            <div class="absolute -bottom-40 end-[-6rem] h-[30rem] w-[30rem] rounded-full bg-saffron/15 blur-3xl"></div>
            <div class="absolute inset-0 text-white/[.07] [mask-image:radial-gradient(ellipse_at_center,black_35%,transparent_75%)]"><x-pattern /></div>
        </div>

        <a href="{{ route('login') }}" class="relative flex items-center gap-3">
            <x-emblem class="h-11 w-11 text-brand-400" />
            <span class="font-display text-2xl font-bold">{{ config('school.name') }}</span>
        </a>

        <div class="relative max-w-lg space-y-4">
            <h1 class="font-display text-4xl font-semibold leading-[1.3] xl:text-5xl">{{ __('Every school day, in order.') }}</h1>
            <p class="text-lg leading-relaxed text-[#A9C2C6]">{{ __('Attendance, grades, fees and messages to parents, in one calm place.') }}</p>
        </div>

        {{-- Floating product cards (fixed light/dark colours: the panel is dark in both themes) --}}
        <div class="relative h-72 xl:h-80" aria-hidden="true">
            <div class="float-a absolute start-0 top-0 w-72 -rotate-2 rounded-2xl bg-[#F4F7F7] p-4 text-[#0E1B20] shadow-pop">
                <div class="mb-2 flex items-center gap-2 text-xs font-semibold text-[#177A49]"><span class="grid h-5 w-5 place-items-center rounded-full bg-[#1E9358] text-white"><x-icon name="check" class="h-3 w-3" /></span>{{ __('WhatsApp · School') }}</div>
                <p class="text-sm leading-relaxed">{{ __('Sara arrived at school at :time.', ['time' => '07:42']) }}</p>
                <div class="mt-2 text-end text-[11px] text-[#5F777E]" dir="ltr">07:42 ✓✓</div>
            </div>

            <div class="float-b absolute end-0 top-6 w-56 rotate-2 rounded-2xl bg-white/10 p-4 ring-1 ring-white/20 backdrop-blur">
                <div class="flex items-center gap-4">
                    <svg viewBox="0 0 100 100" class="h-16 w-16 -rotate-90"><circle cx="50" cy="50" r="40" fill="none" stroke="rgb(255 255 255 / .15)" stroke-width="12"/><circle cx="50" cy="50" r="40" fill="none" stroke="#3AAA6E" stroke-width="12" stroke-dasharray="226 251" stroke-linecap="round"/></svg>
                    <div><div class="font-display text-2xl font-bold" dir="ltr">92%</div><div class="text-xs text-[#A9C2C6]">{{ __('Attendance today') }}</div></div>
                </div>
            </div>

            <div class="float-c absolute bottom-10 start-24 w-60 -rotate-1 rounded-2xl bg-white/10 p-4 ring-1 ring-white/20 backdrop-blur">
                <div class="mb-2 flex justify-between text-sm"><span class="font-semibold">{{ __('Math') }}</span><span class="font-bold" dir="ltr">88%</span></div>
                <div class="h-2.5 overflow-hidden rounded-full bg-white/15"><div class="h-full w-[88%] rounded-full bg-gradient-to-r from-brand-400 to-saffron"></div></div>
            </div>

            <div class="float-a absolute bottom-0 end-6 flex items-center gap-2 rounded-full bg-saffron px-4 py-2 text-sm font-bold text-[#1c1300] shadow-pop" style="animation-delay: -2s">
                <x-icon name="check" class="h-4 w-4" /> {{ __('Instalment received') }}
            </div>
        </div>
    </aside>

    {{-- ===== Form side ===== --}}
    <main class="relative flex min-w-0 flex-col">
        <div class="absolute inset-x-0 top-0 h-56 text-brand-600/[.06] lg:hidden [mask-image:linear-gradient(to_bottom,black,transparent)]"><x-pattern /></div>
        <div class="relative flex items-center justify-between p-5 sm:px-10 sm:pt-8">
            <a href="{{ route('login') }}" class="flex items-center gap-2 lg:invisible"><x-emblem class="h-8 w-8 text-brand-600" /><span class="font-display text-lg font-bold">{{ config('school.name') }}</span></a>
            <div class="flex items-center gap-2">
                <x-pwa-install variant="top" />
                <a href="{{ route('lang', $rtl ? 'en' : 'ar') }}" class="btn-ghost !px-3">{{ $rtl ? 'English' : 'العربية' }}</a>
                <button class="btn-ghost !px-2.5" x-data="{ dark: document.documentElement.classList.contains('dark') }"
                        @click="dark = !dark; document.documentElement.classList.toggle('dark', dark); try { localStorage.setItem('theme', dark ? 'dark' : 'light') } catch (e) {}"
                        aria-label="{{ __('Toggle theme') }}" title="{{ __('Toggle theme') }}"><span x-show="!dark"><x-icon name="moon" /></span><span x-show="dark" x-cloak><x-icon name="sun" /></span></button>
            </div>
        </div>

        <div class="relative flex flex-1 items-center justify-center px-5 pb-10 sm:px-10">
            <div class="w-full {{ $wide ? 'max-w-xl' : 'max-w-md' }} animate-rise">
                @if ($heading)
                    <header class="mb-7 space-y-2">
                        <h2 class="font-display text-3xl font-semibold text-slate-900 sm:text-4xl">{{ $heading }}</h2>
                        @if ($sub)<p class="text-slate-500">{{ $sub }}</p>@endif
                    </header>
                @endif
                @if (session('status'))
                    <div class="mb-5 flex items-start gap-3 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200" role="status"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0" /> {{ session('status') }}</div>
                @endif
                {{ $slot }}
            </div>
        </div>
        <footer class="relative px-5 pb-6 text-center text-xs text-slate-400 sm:px-10">{{ config('school.name') }} · {{ __('School Management') }}</footer>
    </main>
</div>
{{-- Livewire ships Alpine, which powers the password toggle, strength meter and theme switch here --}}
@livewireScripts
</body>
</html>
