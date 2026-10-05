<x-layouts.guest :title="__('Choose a new password')" :heading="__('Choose a new password')" :sub="__('Use at least 8 characters.')">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-field name="email" type="email" :label="__('Email')" icon="mail" dir="ltr" :value="$email" autocomplete="username" />
        <x-field name="password" type="password" :label="__('New password')" icon="lock" dir="ltr" autocomplete="new-password" minlength="8" />
        <x-field name="password_confirmation" type="password" :label="__('Confirm new password')" icon="lock" dir="ltr" autocomplete="new-password" />
        <button class="btn-primary w-full !py-3 text-base">{{ __('Change password') }}</button>
    </form>
</x-layouts.guest>
