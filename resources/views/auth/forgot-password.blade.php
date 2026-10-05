<x-layouts.guest :title="__('Forgot your password?')" :heading="__('Forgot your password?')" :sub="__('Enter your email and we will send you a link to choose a new one.')">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <x-field name="email" type="email" :label="__('Email')" icon="mail" dir="ltr" autocomplete="username" autofocus />
        <button class="btn-primary w-full !py-3 text-base">{{ __('Send reset link') }}</button>
    </form>
    <p class="mt-7 text-center text-sm"><a href="{{ route('login') }}" class="link">← {{ __('Back to sign in') }}</a></p>
</x-layouts.guest>
