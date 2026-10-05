@php
    $rtl = app()->getLocale() === 'ar';
    $contact = \App\Support\Demo::contactUrl();
    $features = [
        ['attendance', 'Attendance in seconds', 'Scan a QR code or fingerprint at the gate, or call the roll in class. Parents hear about absences straight away.'],
        ['announce', 'Parents always in the loop', 'WhatsApp or SMS notices for arrival, absence, payments and report cards, plus announcements to a class or the whole school.'],
        ['grades', 'Grades and report cards', 'Enter marks once, get a printable report card per term or year, and send it to the parent.'],
        ['fees', 'Fees and receipts', 'Instalment plans, partial payments, overdue reminders and printable receipts. Reports you can export.'],
        ['staff', 'Staff attendance', 'A daily sheet for teachers and administrators, self check-in from their phone, and a monthly hours report.'],
        ['timetable', 'A timetable without clashes', 'A teacher or a class can never be booked twice in the same period.'],
        ['years', 'Academic years', 'Every student keeps a class history, and year-end promotion takes one screen.'],
        ['download', 'Works like an app', 'Install it on any phone. Arabic and English, light and dark, and it adapts to every screen.'],
    ];
    $roles = [
        ['admin', 'users', 'See everything: dashboard, staff attendance, finances, setup and reports.'],
        ['teacher', 'grades', 'Take attendance, enter marks and see your own timetable.'],
        ['accountant', 'fees', 'Instalment plans, payments, receipts and the overdue list.'],
        ['parent', 'students', 'Follow a child: attendance, grades, fees and the day’s lessons.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0B2327">
    <meta name="description" content="{{ __('Attendance, grades, fees and messages to parents, in one calm place.') }}">
    <title>{{ config('school.name') }} – {{ __('Try the school management system') }}</title>
    <script>(function(){try{var t=localStorage.getItem('theme');if(t==='dark'||(!t&&matchMedia('(prefers-color-scheme: dark)').matches))document.documentElement.classList.add('dark')}catch(e){}})()</script>
    <x-pwa-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen">
{{-- ===== Hero ===== --}}
<header class="relative overflow-hidden bg-sidebar text-white">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -top-40 start-[-8rem] h-[34rem] w-[34rem] rounded-full bg-brand-600/35 blur-3xl"></div>
        <div class="absolute -bottom-48 end-[-8rem] h-[34rem] w-[34rem] rounded-full bg-saffron/15 blur-3xl"></div>
        <div class="absolute inset-0 text-white/[.07] [mask-image:radial-gradient(ellipse_at_center,black_30%,transparent_75%)]"><x-pattern /></div>
    </div>
    <div class="relative mx-auto max-w-6xl px-5 pb-16 pt-6 sm:px-8 lg:pb-24">
        <nav class="flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-3"><x-emblem class="h-10 w-10 text-brand-400" /><span class="font-display text-xl font-semibold">{{ config('school.name') }}</span></a>
            <div class="flex items-center gap-2">
                <a href="{{ route('login') }}" class="hidden text-sm font-medium text-[#CFE0E2] hover:text-white sm:inline">{{ __('Sign in') }}</a>
                <a href="{{ route('lang', $rtl ? 'en' : 'ar') }}" class="btn !bg-white/10 !text-white hover:!bg-white/20 !px-3">{{ $rtl ? 'English' : 'العربية' }}</a>
                <button class="btn !bg-white/10 !text-white hover:!bg-white/20 !px-2.5" x-data="{ dark: document.documentElement.classList.contains('dark') }"
                        @click="dark = !dark; document.documentElement.classList.toggle('dark', dark); try { localStorage.setItem('theme', dark ? 'dark' : 'light') } catch (e) {}"
                        aria-label="{{ __('Toggle theme') }}"><span x-show="!dark"><x-icon name="moon" /></span><span x-show="dark" x-cloak><x-icon name="sun" /></span></button>
            </div>
        </nav>

        <div class="mt-14 grid items-center gap-12 lg:mt-20 lg:grid-cols-[1.1fr_1fr]">
            <div class="space-y-6">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-[#CFE0E2] ring-1 ring-white/15"><span class="h-2 w-2 rounded-full bg-saffron"></span>{{ __('Live demo, no sign-up needed') }}</span>
                <h1 class="font-display text-4xl font-semibold leading-[1.3] sm:text-5xl lg:text-6xl">{{ __('Run your whole school from one calm place.') }}</h1>
                <p class="max-w-xl text-lg leading-relaxed text-[#A9C2C6]">{{ __('Attendance, grades, fees and messages to parents, in one calm place.') }}</p>
                <div class="flex flex-wrap gap-3 pt-2">
                    <a href="#try" class="btn bg-saffron !px-6 !py-3 text-base text-[#1c1300] hover:brightness-95">{{ __('Try the demo') }} <x-icon name="arrow" class="h-4 w-4 rtl:-scale-x-100" /></a>
                    @if ($contact)<a href="{{ $contact }}" target="_blank" rel="noopener" class="btn !bg-white/10 !px-6 !py-3 text-base !text-white hover:!bg-white/20">{{ __('Talk to us') }}</a>@endif
                </div>
            </div>

            <div class="relative mx-auto hidden h-80 w-full max-w-md md:block" aria-hidden="true">
                <div class="float-a absolute start-0 top-0 w-72 -rotate-2 rounded-2xl bg-[#F4F7F7] p-4 text-[#0E1B20] shadow-pop">
                    <div class="mb-2 flex items-center gap-2 text-xs font-semibold text-[#177A49]"><span class="grid h-5 w-5 place-items-center rounded-full bg-[#1E9358] text-white"><x-icon name="check" class="h-3 w-3" /></span>{{ __('WhatsApp · School') }}</div>
                    <p class="text-sm leading-relaxed">{{ __('Sara arrived at school at :time.', ['time' => '07:42']) }}</p>
                    <div class="mt-2 text-end text-[11px] text-[#5F777E]" dir="ltr">07:42 ✓✓</div>
                </div>
                <div class="float-b absolute end-0 top-10 w-56 rotate-2 rounded-2xl bg-white/10 p-4 ring-1 ring-white/20 backdrop-blur">
                    <div class="flex items-center gap-4">
                        <svg viewBox="0 0 100 100" class="h-16 w-16 -rotate-90"><circle cx="50" cy="50" r="40" fill="none" stroke="rgb(255 255 255 / .15)" stroke-width="12"/><circle cx="50" cy="50" r="40" fill="none" stroke="#3AAA6E" stroke-width="12" stroke-dasharray="226 251" stroke-linecap="round"/></svg>
                        <div><div class="font-display text-2xl font-bold" dir="ltr">92%</div><div class="text-xs text-[#A9C2C6]">{{ __('Attendance today') }}</div></div>
                    </div>
                </div>
                <div class="float-c absolute bottom-12 start-16 w-60 -rotate-1 rounded-2xl bg-white/10 p-4 ring-1 ring-white/20 backdrop-blur">
                    <div class="mb-2 flex justify-between text-sm"><span class="font-semibold">{{ __('Math') }}</span><span class="font-bold" dir="ltr">88%</span></div>
                    <div class="h-2.5 overflow-hidden rounded-full bg-white/15"><div class="h-full w-[88%] rounded-full bg-gradient-to-r from-brand-400 to-saffron"></div></div>
                </div>
                <div class="float-a absolute bottom-0 end-4 flex items-center gap-2 rounded-full bg-saffron px-4 py-2 text-sm font-bold text-[#1c1300] shadow-pop" style="animation-delay: -2s"><x-icon name="check" class="h-4 w-4" /> {{ __('Instalment received') }}</div>
            </div>
        </div>
    </div>
</header>

<main>
    {{-- ===== Features ===== --}}
    <section class="mx-auto max-w-6xl px-5 py-16 sm:px-8 lg:py-24">
        <div class="mb-10 max-w-2xl space-y-3">
            <h2 class="font-display text-3xl font-semibold text-slate-900 sm:text-4xl">{{ __('Everything a school office does, in one place') }}</h2>
            <p class="text-slate-500">{{ __('Built for administrators, teachers, accountants and parents, and ready to try right now.') }}</p>
        </div>
        <div class="stagger grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($features as [$icon, $title, $text])
                <article class="card space-y-3">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand-100 text-brand-700"><x-icon :name="$icon" class="h-5 w-5" /></span>
                    <h3 class="text-base font-bold text-slate-900">{{ __($title) }}</h3>
                    <p class="text-sm leading-relaxed text-slate-500">{{ __($text) }}</p>
                </article>
            @endforeach
        </div>
    </section>

    {{-- ===== Try it ===== --}}
    <section id="try" class="scroll-mt-6 bg-brand-50/60 py-16 lg:py-24">
        <div class="mx-auto max-w-6xl px-5 sm:px-8">
            <div class="mb-10 max-w-2xl space-y-3">
                <h2 class="font-display text-3xl font-semibold text-slate-900 sm:text-4xl">{{ __('Try it as you would use it') }}</h2>
                <p class="text-slate-500">{{ __('Pick a role and you are in with one click. Everything works: take attendance, enter marks, record a payment, switch language.') }}</p>
            </div>
            <div class="stagger grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($roles as [$role, $icon, $text])
                    <form method="POST" action="{{ route('demo.login', $role) }}" class="card flex flex-col gap-4">@csrf
                        <span class="grid h-12 w-12 place-items-center rounded-xl bg-saffron/20 text-[#8a6200]"><x-icon :name="$icon" class="h-6 w-6" /></span>
                        <div class="flex-1 space-y-1.5"><h3 class="font-display text-xl font-semibold text-slate-900">{{ $role === 'admin' ? __('School principal') : __('role.'.$role) }}</h3>
                            <p class="text-sm leading-relaxed text-slate-500">{{ __($text) }}</p></div>
                        <button class="btn-primary w-full">{{ __('Enter as') }} {{ $role === 'admin' ? __('School principal') : __('role.'.$role) }} <x-icon name="arrow" class="h-4 w-4 rtl:-scale-x-100" /></button>
                    </form>
                @endforeach
            </div>
            <p class="mt-6 flex items-start gap-2 text-sm text-slate-500"><x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" /> {{ __('Demo version: all data is fictional and resets every :h hours. Please do not enter real student data.', ['h' => config('school.demo_reset_hours')]) }}</p>
        </div>
    </section>

    {{-- ===== Contact ===== --}}
    <section class="mx-auto max-w-6xl px-5 py-16 sm:px-8 lg:py-24">
        <div class="relative overflow-hidden rounded-3xl bg-sidebar p-8 text-white sm:p-12">
            <div class="absolute inset-y-0 end-0 w-1/2 text-white/[.07] [mask-image:linear-gradient(to_left,black,transparent)] rtl:[mask-image:linear-gradient(to_right,black,transparent)]"><x-pattern /></div>
            <div class="relative flex flex-wrap items-center justify-between gap-6">
                <div class="max-w-xl space-y-2">
                    <h2 class="font-display text-3xl font-semibold">{{ __('Want it for your school?') }}</h2>
                    <p class="text-[#A9C2C6]">{{ __('Send us a message and tell us about your school. We will get back to you.') }}</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    @if ($contact)<a href="{{ $contact }}" target="_blank" rel="noopener" class="btn bg-saffron !px-6 !py-3 text-base text-[#1c1300] hover:brightness-95">{{ __('Talk to us') }}</a>@endif
                    <a href="#try" class="btn !bg-white/10 !px-6 !py-3 text-base !text-white hover:!bg-white/20">{{ __('Try the demo') }}</a>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="border-t border-slate-200 px-5 py-8 text-center text-sm text-slate-500">
    {{ config('school.name') }} · {{ __('School Management') }}
    @if (config('school.contact_email')) · <span dir="ltr">{{ config('school.contact_email') }}</span>@endif
</footer>
@livewireScripts
</body>
</html>
