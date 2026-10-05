<x-layouts.guest :title="__('Sign in')" :heading="__('Welcome back')" :sub="__('Sign in to continue')">
    <form method="POST" action="{{ route('login.attempt') }}" class="space-y-5">
        @csrf
        <x-field name="email" type="email" :label="__('Email')" icon="mail" dir="ltr" autocomplete="username" autofocus />
        <x-field name="password" type="password" :label="__('Password')" icon="lock" dir="ltr" autocomplete="current-password" />

        <div class="flex items-center justify-between text-sm">
            <label class="flex cursor-pointer items-center gap-2 text-slate-600"><input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"> {{ __('Remember me') }}</label>
            <a href="{{ route('password.request') }}" class="link">{{ __('Forgot your password?') }}</a>
        </div>

        <button class="btn-primary w-full !py-3 text-base">{{ __('Sign in') }} <x-icon name="arrow" class="h-4 w-4 rtl:-scale-x-100" /></button>
    </form>

    @if (config('school.demo_logins'))
        <div class="mt-7 rounded-2xl border border-dashed border-slate-300 p-4" x-data>
            <div class="mb-3 flex items-center gap-2 text-xs font-semibold text-slate-500"><x-icon name="info" class="h-4 w-4" /> {{ __('Demo accounts: tap to fill the form') }}</div>
            <div class="grid grid-cols-2 gap-2">
                @foreach ([['admin', 'admin@school.test', 'change-me-now'], ['teacher', 'teacher@school.test', 'password'], ['accountant', 'accountant@school.test', 'password'], ['parent', 'parent@school.test', 'password']] as [$role, $mail, $pw])
                    <button type="button" class="btn-ghost btn-sm" @click="document.getElementById('email').value = '{{ $mail }}'; document.getElementById('password').value = '{{ $pw }}'; document.getElementById('password').focus()">{{ __('role.'.$role) }}</button>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mt-8 space-y-3 border-t border-slate-200 pt-6 text-center text-sm text-slate-500">
        <p>{{ __('Parent and new here?') }} <a href="{{ route('register') }}" class="link">{{ __('Create a parent account') }}</a></p>
        <p class="flex items-center justify-center gap-2 text-xs"><x-icon name="shield" class="h-4 w-4" /> {{ __('Teachers and staff: your administrator creates your account.') }}</p>
    </div>
</x-layouts.guest>
