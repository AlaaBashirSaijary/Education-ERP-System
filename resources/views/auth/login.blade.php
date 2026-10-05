@php $rtl = app()->getLocale() === 'ar'; @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Sign in') }} – {{ config('school.name') }}</title>
    <script>(function(){try{var t=localStorage.getItem('theme');if(t==='dark'||(!t&&matchMedia('(prefers-color-scheme: dark)').matches))document.documentElement.classList.add('dark')}catch(e){}})()</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
<div class="grid min-h-screen lg:grid-cols-[1.1fr_1fr]">
    {{-- Brand panel --}}
    <section class="relative hidden overflow-hidden bg-sidebar text-white lg:flex lg:flex-col lg:justify-between lg:p-12">
        <div class="absolute inset-0 text-white/[.07]"><x-pattern /></div>
        <div class="absolute -bottom-40 -end-40 h-[32rem] w-[32rem] rounded-full bg-brand-600/30 blur-3xl"></div>
        <div class="relative flex items-center gap-3">
            <x-emblem class="h-12 w-12 text-brand-400" />
            <span class="font-display text-2xl font-bold">{{ config('school.name') }}</span>
        </div>
        <div class="relative max-w-md space-y-6">
            <h1 class="font-display text-4xl font-bold leading-snug xl:text-5xl">{{ __('Every school day, in order.') }}</h1>
            <p class="text-lg leading-relaxed text-[#A9C2C6]">{{ __('Attendance, grades, fees and messages to parents, in one calm place.') }}</p>
            <ul class="space-y-3 text-[#CFE0E2]">
                @foreach (['Attendance by QR or fingerprint', 'Instant WhatsApp/SMS notices to parents', 'Instalments, receipts and reports'] as $f)
                    <li class="flex items-center gap-3"><span class="grid h-6 w-6 place-items-center rounded-full bg-saffron/20 text-saffron"><x-icon name="check" class="h-3.5 w-3.5" /></span>{{ __($f) }}</li>
                @endforeach
            </ul>
        </div>
        <div class="relative text-sm text-[#7FA3A9]">{{ __('School Management') }}</div>
    </section>

    {{-- Form --}}
    <section class="flex flex-col p-5 sm:p-10">
        <div class="flex items-center justify-between lg:justify-end">
            <div class="flex items-center gap-2 lg:hidden"><x-emblem class="h-8 w-8 text-brand-600" /><span class="text-base font-bold">{{ config('school.name') }}</span></div>
            <div class="flex items-center gap-2">
                <a href="{{ route('lang', $rtl ? 'en' : 'ar') }}" class="btn-ghost !px-3">{{ $rtl ? 'English' : 'العربية' }}</a>
                <button class="btn-ghost !px-2.5" x-data="{ dark: document.documentElement.classList.contains('dark') }"
                        @click="dark = !dark; document.documentElement.classList.toggle('dark', dark); try { localStorage.setItem('theme', dark ? 'dark' : 'light') } catch (e) {}"
                        aria-label="{{ __('Toggle theme') }}"><span x-show="!dark"><x-icon name="moon" /></span><span x-show="dark" x-cloak><x-icon name="sun" /></span></button>
            </div>
        </div>
        <div class="flex flex-1 items-center justify-center py-10">
            <form method="POST" action="{{ route('login.attempt') }}" class="w-full max-w-sm animate-rise space-y-5">
                @csrf
                <div class="space-y-1.5">
                    <h2 class="font-display text-3xl font-bold text-slate-900">{{ __('Sign in') }}</h2>
                    <p class="text-sm text-slate-500">{{ __('Sign in to continue') }}</p>
                </div>
                <div>
                    <label class="label" for="email">{{ __('Email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" class="input" required autofocus autocomplete="username" dir="ltr">
                    @error('email')<p class="err">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="password">{{ __('Password') }}</label>
                    <input id="password" name="password" type="password" class="input" required autocomplete="current-password" dir="ltr">
                </div>
                <button class="btn-primary w-full !py-3 text-base">{{ __('Sign in') }} <x-icon name="arrow" class="h-4 w-4 rtl:-scale-x-100" /></button>
            </form>
        </div>
    </section>
</div>
</body>
</html>
