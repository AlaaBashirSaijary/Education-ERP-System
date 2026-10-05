@php
    $rtl = app()->getLocale() === 'ar';
    $user = auth()->user();
    $nav = [
        ['dashboard', 'home', 'Dashboard', ['admin','teacher','accountant','parent']],
        ['students', 'students', 'Students', ['admin','teacher','accountant','parent']],
        ['attendance', 'attendance', 'Attendance', ['admin','teacher']],
        ['gate', 'gate', 'Gate scanner', ['admin','teacher']],
        ['grades', 'grades', 'Grades', ['admin','teacher']],
        ['fees', 'fees', 'Fees', ['admin','accountant','parent']],
        ['timetable', 'timetable', 'Timetable', ['admin','teacher','parent']],
        ['attendance-report', 'reports', 'Attendance report', ['admin','teacher']],
        ['finance-report', 'reports', 'Financial report', ['admin','accountant']],
        ['announcements', 'announce', 'Announcements', ['admin','teacher']],
        ['messages', 'messages', 'Parent messages', ['admin']],
        ['users', 'users', 'Users', ['admin']],
        ['setup', 'setup', 'Setup', ['admin']],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('school.name') }} – {{ __('School Management') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body x-data="{ open: false }">
<div class="flex min-h-screen">
    {{-- Sidebar: start side (right in Arabic, left in English) --}}
    <aside class="fixed inset-y-0 start-0 z-30 w-64 transform bg-slate-900 p-4 transition-transform {{ $rtl ? 'translate-x-full' : '-translate-x-full' }} lg:static lg:translate-x-0"
           :class="open && '!translate-x-0'">
        <div class="mb-6 px-2 text-lg font-bold text-white">🎓 {{ config('school.name') }}</div>
        <nav class="space-y-1 overflow-y-auto" style="max-height: calc(100vh - 5rem)">
            @foreach ($nav as [$route, $icon, $label, $roles])
                @if (in_array($user->role, $roles))
                    <a href="{{ route($route) }}" wire:navigate
                       class="navlink {{ request()->routeIs($route) ? 'navlink-active' : '' }}">
                        <x-icon :name="$icon" /> {{ __($label) }}
                    </a>
                @endif
            @endforeach
        </nav>
    </aside>
    <div x-show="open" x-cloak @click="open = false" class="fixed inset-0 z-20 bg-black/40 lg:hidden"></div>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3">
            <button class="btn-ghost lg:hidden" @click="open = !open" aria-label="Menu">☰</button>
            <h1 class="hidden min-w-0 truncate px-2 text-lg font-bold sm:block">{{ $title ?? '' }}</h1>
            <div class="flex shrink-0 items-center gap-2">
                <a href="{{ route('lang', $rtl ? 'en' : 'ar') }}" class="btn-ghost" title="Language">
                    🌐 {{ $rtl ? 'English' : 'العربية' }}
                </a>
                <a href="{{ route('profile') }}" wire:navigate class="hidden text-sm text-slate-500 hover:text-indigo-600 sm:inline" title="{{ __('My profile') }}">{{ $user->name }} · {{ __('role.'.$user->role) }}</a>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="btn-ghost">{{ __('Log out') }}</button>
                </form>
            </div>
        </header>
        <main class="flex-1 p-4 sm:p-6">{{ $slot }}</main>
    </div>
</div>
@livewireScripts
</body>
</html>
