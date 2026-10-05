<div class="mx-auto grid max-w-3xl gap-4 md:grid-cols-2">
    <div class="md:col-span-2"><x-flash /></div>
    <form wire:submit="saveProfile" class="card space-y-4">
        <h2 class="font-bold">{{ __('My profile') }}</h2>
        <div><label class="label">{{ __('Full name') }}</label><input wire:model="name" class="input">@error('name')<p class="err">{{ $message }}</p>@enderror</div>
        <div><label class="label">{{ __('Phone') }}</label><input wire:model="phone" class="input" dir="ltr"></div>
        <div class="text-sm text-slate-500" dir="ltr" style="text-align:start">{{ auth()->user()->email }}</div>
        <button class="btn-primary">{{ __('Save') }}</button>
    </form>
    <form wire:submit="changePassword" class="card space-y-4">
        <h2 class="font-bold">{{ __('Change password') }}</h2>
        <div><label class="label">{{ __('Current password') }}</label><input type="password" wire:model="current_password" class="input" dir="ltr">@error('current_password')<p class="err">{{ $message }}</p>@enderror</div>
        <div><label class="label">{{ __('New password') }}</label><input type="password" wire:model="password" class="input" dir="ltr">@error('password')<p class="err">{{ $message }}</p>@enderror</div>
        <div><label class="label">{{ __('Confirm new password') }}</label><input type="password" wire:model="password_confirmation" class="input" dir="ltr"></div>
        <button class="btn-primary">{{ __('Change password') }}</button>
    </form>
    @if (auth()->user()->hasRole('parent'))
        <form wire:submit="linkChild" class="card space-y-4 md:col-span-2">
            <div><h2 class="text-base font-bold">{{ __('Link another child') }}</h2>
                <p class="text-sm text-slate-500">{{ __('Enter the student number and the phone number the school has on file.') }}</p></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label">{{ __('Student number') }}</label><input wire:model="child_no" class="input" dir="ltr">@error('child_no')<p class="err">{{ $message }}</p>@enderror</div>
                <div><label class="label">{{ __('Phone number on file at school') }}</label><input wire:model="child_phone" class="input" dir="ltr">@error('child_phone')<p class="err">{{ $message }}</p>@enderror</div>
            </div>
            <button class="btn-primary">{{ __('Link child') }}</button>
        </form>
    @endif
</div>
