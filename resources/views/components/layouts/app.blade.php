@php
    $rtl = app()->getLocale() === 'ar';
    $user = auth()->user();
    $yearsSvc = app(\App\Support\Years::class);
    $years = \App\Models\AcademicYear::orderByDesc('starts_on')->get();
    $selectedYear = $yearsSvc->selected();
    $viewingPast = $selectedYear && ! $yearsSvc->isViewingCurrent();

    // [route, icon, label, roles]
    $groups = [
        [null, [
            ['dashboard', 'home', 'Dashboard', ['admin','teacher','accountant','parent']],
            ['students', 'students', 'Students', ['admin','teacher','accountant','parent']],
        ]],
        ['Academics', [
            ['attendance', 'attendance', 'Attendance', ['admin','teacher']],
            ['gate', 'gate', 'Gate scanner', ['admin','teacher']],
            ['grades', 'grades', 'Grades', ['admin','teacher']],
            ['timetable', 'timetable', 'Timetable', ['admin','teacher','parent']],
            ['attendance-report', 'reports', 'Attendance report', ['admin','teacher']],
        ]],
        ['Finance', [
            ['fees', 'fees', 'Fees', ['admin','accountant','parent']],
            ['finance-report', 'reports', 'Financial report', ['admin','accountant']],
        ]],
        ['Communication', [
            ['announcements', 'announce', 'Announcements', ['admin','teacher']],
            ['messages', 'messages', 'Parent messages', ['admin']],
        ]],
        ['Administration', [
            ['staff-attendance', 'staff', 'Staff attendance', ['admin']],
            ['years', 'years', 'Academic years', ['admin']],
            ['users', 'users', 'Users', ['admin']],
            ['setup', 'setup', 'Setup', ['admin']],
        ]],
    ];
@endphp
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
<body x-data="{ open: false }" class="min-h-screen">
<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 start-0 z-40 flex w-72 flex-col bg-sidebar text-white transition-transform duration-200 {{ $rtl ? 'translate-x-full' : '-translate-x-full' }} lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
           :class="open && '!translate-x-0'">
        <div class="relative overflow-hidden px-5 pb-5 pt-[max(1.5rem,env(safe-area-inset-top))]">
            <div class="absolute -top-6 end-0 h-32 w-32 text-white/[.06]"><x-pattern /></div>
            <a href="{{ route('dashboard') }}" wire:navigate class="relative flex items-center gap-3">
                <x-emblem class="h-10 w-10 text-brand-400" />
                <span class="min-w-0">
                    <span class="block truncate text-base font-bold leading-tight">{{ config('school.name') }}</span>
                    <span class="block text-[11px] text-[#7FA3A9]">{{ __('School Management') }}</span>
                </span>
            </a>
        </div>

        <nav class="flex-1 space-y-5 overflow-y-auto px-3 pb-4" aria-label="{{ __('Main navigation') }}">
            @foreach ($groups as [$heading, $items])
                @php $visible = array_filter($items, fn ($i) => in_array($user->role, $i[3])); @endphp
                @if ($visible)
                    <div class="space-y-1">
                        @if ($heading)<div class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-[#5E8087]">{{ __($heading) }}</div>@endif
                        @foreach ($visible as [$route, $icon, $label])
                            <a href="{{ route($route) }}" wire:navigate @click="open = false"
                               class="navlink {{ request()->routeIs($route) || ($route === 'students' && request()->routeIs('student-profile', 'report-card')) ? 'navlink-active' : '' }}"
                               @if (request()->routeIs($route)) aria-current="page" @endif>
                                <x-icon :name="$icon" /> <span class="truncate">{{ __($label) }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </nav>

        <div class="border-t border-white/10 p-3 pb-[max(.75rem,env(safe-area-inset-bottom))]">
            <x-pwa-install class="mb-2" />
            <div class="flex items-center gap-3 rounded-xl p-2">
                <a href="{{ route('profile') }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-3" title="{{ __('My profile') }}">
                    <x-avatar :name="$user->name" size="h-10 w-10 text-sm" />
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-semibold">{{ $user->name }}</span>
                        <span class="block text-xs text-[#7FA3A9]">{{ __('role.'.$user->role) }}</span>
                    </span>
                </a>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="rounded-lg p-2 text-[#A9C2C6] transition hover:bg-white/10 hover:text-white" title="{{ __('Log out') }}" aria-label="{{ __('Log out') }}"><x-icon name="logout" /></button>
                </form>
            </div>
        </div>
    </aside>
    <div x-show="open" x-cloak @click="open = false" class="fixed inset-0 z-30 bg-black/50 backdrop-blur-sm lg:hidden"></div>

    <div class="flex min-w-0 flex-1 flex-col">
        <div x-data="{ off: !navigator.onLine }" @online.window="off = false" @offline.window="off = true" x-show="off" x-cloak
             class="flex items-center justify-center gap-2 bg-amber-500 px-4 py-2 pt-[max(.5rem,env(safe-area-inset-top))] text-sm font-semibold text-[#1c1300]" role="status">
            <x-icon name="info" class="h-4 w-4" /> {{ __('You are offline. Changes cannot be saved until you reconnect.') }}
        </div>
        <header class="topbar sticky top-0 z-20 flex flex-wrap items-center justify-between gap-x-3 gap-y-2 border-b border-slate-200/70 bg-canvas/85 px-4 py-3 backdrop-blur sm:px-6 lg:px-8">
            <div class="flex min-w-0 flex-1 items-center gap-3 sm:flex-none">
                <button class="btn-ghost !px-2.5 lg:hidden" @click="open = !open" aria-label="{{ __('Menu') }}"><x-icon name="menu" /></button>
                <h1 class="truncate text-lg font-bold text-slate-900 sm:text-2xl">{{ $title ?? '' }}</h1>
            </div>
            <div class="flex shrink-0 items-center gap-2 max-sm:w-full max-sm:justify-end">
                {{-- Academic-year switcher --}}
                @if ($selectedYear)
                    <div class="relative" x-data="{ o: false }" @keydown.escape.window="o = false">
                        <button class="btn-ghost !px-3 {{ $viewingPast ? '!bg-amber-100 !text-amber-800 !ring-amber-300' : '' }}" @click="o = !o" :aria-expanded="o" aria-haspopup="listbox">
                            <x-icon name="years" class="h-4 w-4" />
                            <span class="font-num">{{ $selectedYear->name }}</span>
                            <x-icon name="chevron" class="h-4 w-4 opacity-60" />
                        </button>
                        <div x-show="o" x-cloak @click.outside="o = false" x-transition.origin.top
                             class="absolute end-0 z-50 mt-2 w-60 overflow-hidden rounded-2xl bg-surface p-1.5 shadow-pop ring-1 ring-slate-200" role="listbox">
                            <div class="px-3 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ __('Academic year') }}</div>
                            @foreach ($years as $y)
                                <form method="POST" action="{{ route('year.switch', $y) }}">@csrf
                                    <button class="flex w-full items-center justify-between gap-2 rounded-xl px-3 py-2 text-sm hover:bg-slate-100 {{ $y->id === $selectedYear->id ? 'font-bold text-brand-600' : 'text-slate-700' }}" role="option" aria-selected="{{ $y->id === $selectedYear->id ? 'true' : 'false' }}">
                                        <span class="font-num">{{ $y->name }}</span>
                                        <span class="flex items-center gap-1.5">@if ($y->is_current)<span class="badge-brand">{{ __('Current') }}</span>@endif @if ($y->id === $selectedYear->id)<x-icon name="check" class="h-4 w-4" />@endif</span>
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                @endif
                <a href="{{ route('lang', $rtl ? 'en' : 'ar') }}" class="btn-ghost !px-3" title="{{ $rtl ? 'English' : 'العربية' }}">{{ $rtl ? 'EN' : 'ع' }}</a>
                <button class="btn-ghost !px-2.5" x-data="{ dark: document.documentElement.classList.contains('dark') }"
                        @click="dark = !dark; document.documentElement.classList.toggle('dark', dark); try { localStorage.setItem('theme', dark ? 'dark' : 'light') } catch (e) {}"
                        aria-label="{{ __('Toggle theme') }}" title="{{ __('Toggle theme') }}">
                    <span x-show="!dark"><x-icon name="moon" /></span><span x-show="dark" x-cloak><x-icon name="sun" /></span>
                </button>
            </div>
        </header>

        @if ($viewingPast)
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-800 sm:px-6 lg:px-8">
                <span>{{ __('You are viewing the academic year :y, which is not the current year.', ['y' => $selectedYear->name]) }}</span>
                <form method="POST" action="{{ route('year.switch', $yearsSvc->current()) }}">@csrf
                    <button class="font-semibold underline">{{ __('Back to the current year') }}</button></form>
            </div>
        @endif

        <main class="mx-auto w-full max-w-7xl flex-1 p-4 sm:p-6 lg:p-8">{{ $slot }}</main>
    </div>
</div>
@livewireScripts
</body>
</html>
