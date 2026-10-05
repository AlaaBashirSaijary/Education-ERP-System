@php $rtl = app()->getLocale() === 'ar'; @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Sign in') }} – {{ config('school.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-gradient-to-br from-indigo-600 to-slate-900 p-4">
<div class="w-full max-w-sm">
    <div class="mb-3 text-end">
        <a href="{{ route('lang', $rtl ? 'en' : 'ar') }}" class="btn-ghost">🌐 {{ $rtl ? 'English' : 'العربية' }}</a>
    </div>
    <form method="POST" action="{{ route('login.attempt') }}" class="card space-y-4">
        @csrf
        <div class="text-center">
            <div class="text-4xl">🎓</div>
            <h1 class="mt-2 text-xl font-bold">{{ config('school.name') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Sign in to continue') }}</p>
        </div>
        <div>
            <label class="label" for="email">{{ __('Email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" class="input" required autofocus dir="ltr">
            @error('email')<p class="err">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="password">{{ __('Password') }}</label>
            <input id="password" name="password" type="password" class="input" required dir="ltr">
        </div>
        <button class="btn-primary w-full">{{ __('Sign in') }}</button>
    </form>
</div>
</body>
</html>
