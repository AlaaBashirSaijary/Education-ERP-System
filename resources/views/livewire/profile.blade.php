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
</div>
