<x-layouts.guest :title="__('Create a parent account')" :heading="__('Create a parent account')" :sub="__('Follow attendance, grades and fees for your children.')" wide>
    <form method="POST" action="{{ route('register.store') }}" class="space-y-7"
          x-data="{ pw: '', get score() { let s = 0; if (this.pw.length >= 8) s++; if (this.pw.length >= 12) s++; if (/[A-Z؀-ۿ]/.test(this.pw) && /[a-z0-9]/.test(this.pw)) s++; if (/\d/.test(this.pw) && /[^A-Za-z0-9؀-ۿ]/.test(this.pw)) s++; return s; } }">
        @csrf

        <section class="space-y-4">
            <h3 class="mb-3 flex items-center gap-3 font-semibold text-slate-900"><span class="grid h-7 w-7 place-items-center rounded-full bg-brand-600 text-sm text-white">1</span> {{ __('Your account') }}</h3>
            <x-field name="name" :label="__('Full name')" icon="user" autocomplete="name" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="email" type="email" :label="__('Email')" icon="mail" dir="ltr" autocomplete="email" />
                <x-field name="phone" type="tel" :label="__('Your phone number')" icon="phone" dir="ltr" autocomplete="tel" placeholder="+9627XXXXXXXX" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-field name="password" type="password" :label="__('Password')" icon="lock" dir="ltr" autocomplete="new-password" x-model="pw" minlength="8" />
                    <div class="mt-2 flex gap-1" aria-hidden="true">
                        @foreach ([1, 2, 3, 4] as $i)<span class="h-1.5 flex-1 rounded-full transition-colors" :class="score >= {{ $i }} ? (score <= 1 ? 'bg-rose-500' : (score == 2 ? 'bg-amber-500' : 'bg-emerald-500')) : 'bg-slate-200'"></span>@endforeach
                    </div>
                    <p class="mt-1 text-xs text-slate-500" x-text="pw.length === 0 ? '{{ __('At least 8 characters.') }}' : (score <= 1 ? '{{ __('Weak') }}' : (score == 2 ? '{{ __('Fair') }}' : '{{ __('Strong') }}'))"></p>
                </div>
                <x-field name="password_confirmation" type="password" :label="__('Confirm password')" icon="lock" dir="ltr" autocomplete="new-password" />
            </div>
        </section>

        <section class="space-y-4 rounded-2xl bg-brand-50/70 p-5 ring-1 ring-brand-100">
            <h3 class="flex items-center gap-3 font-semibold text-slate-900"><span class="grid h-7 w-7 place-items-center rounded-full bg-brand-600 text-sm text-white">2</span> {{ __('Link your child') }}</h3>
            <p class="flex gap-2 text-sm text-slate-600"><x-icon name="shield" class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" /> {{ __('To protect students, we match the student number with the phone number the school has on file. Use the same phone you gave the school.') }}</p>
            <x-field name="student_no" :label="__('Student number')" icon="hash" dir="ltr" :hint="__('Printed on the student card and the report card.')" autocomplete="off" />
        </section>

        <button class="btn-primary w-full !py-3 text-base">{{ __('Create account') }} <x-icon name="arrow" class="h-4 w-4 rtl:-scale-x-100" /></button>
    </form>

    <p class="mt-7 text-center text-sm text-slate-500">{{ __('Already have an account?') }} <a href="{{ route('login') }}" class="link">{{ __('Sign in') }}</a></p>
    <p class="mt-3 flex items-center justify-center gap-2 text-center text-xs text-slate-400"><x-icon name="info" class="h-4 w-4 shrink-0" /> {{ __('Teachers and staff: your administrator creates your account.') }}</p>
</x-layouts.guest>
