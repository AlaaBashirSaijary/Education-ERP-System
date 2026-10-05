<div class="space-y-4">
    <x-flash />
    <div class="flex flex-wrap items-center justify-between gap-3">
        <select wire:model.live="roleFilter" class="input w-48"><option value="">{{ __('All roles') }}</option>
            @foreach (\App\Livewire\Users::ROLES as $r)<option value="{{ $r }}">{{ __('role.'.$r) }}</option>@endforeach</select>
        <button class="btn-primary" wire:click="create">＋ {{ __('Add user') }}</button>
    </div>

    @if ($showForm)
        <form wire:submit="save" class="card grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <h2 class="font-bold sm:col-span-2 lg:col-span-3">{{ $editingId ? __('Edit user') : __('Add user') }}</h2>
            <div><label class="label">{{ __('Full name') }}</label><input wire:model="name" class="input">@error('name')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Email') }}</label><input wire:model="email" type="email" class="input" dir="ltr">@error('email')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Phone') }}</label><input wire:model="phone" class="input" dir="ltr">@error('phone')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Role') }}</label>
                <select wire:model="role" class="input">@foreach (\App\Livewire\Users::ROLES as $r)<option value="{{ $r }}">{{ __('role.'.$r) }}</option>@endforeach</select>
                @error('role')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ $editingId ? __('New password (leave empty to keep)') : __('Password') }}</label>
                <input wire:model="password" type="text" autocomplete="off" class="input" dir="ltr" placeholder="{{ __('Min 8 characters') }}">@error('password')<p class="err">{{ $message }}</p>@enderror</div>
            <div class="flex items-end gap-2">
                <button class="btn-primary">{{ __('Save') }}</button>
                <button type="button" class="btn-ghost" wire:click="resetForm">{{ __('Cancel') }}</button>
            </div>
        </form>
    @endif

    <div class="table-wrap"><table class="tbl">
        <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Email') }}</th><th>{{ __('Phone') }}</th><th>{{ __('Role') }}</th><th></th></tr></thead>
        <tbody>@foreach ($users as $u)
            <tr wire:key="u{{ $u->id }}"><td><span class="flex items-center gap-3"><x-avatar :name="$u->name" size="h-8 w-8 text-xs" /><span class="font-semibold">{{ $u->name }}</span></span></td><td dir="ltr" class="text-start">{{ $u->email }}</td>
                <td dir="ltr" class="text-start">{{ $u->phone ?: '—' }}</td><td><span class="badge-slate">{{ __('role.'.$u->role) }}</span></td>
                <td class="space-x-3 text-end rtl:space-x-reverse">
                    <button class="text-slate-600 hover:underline" wire:click="edit({{ $u->id }})">{{ __('Edit') }}</button>
                    @if ($u->id !== auth()->id())<button class="text-rose-600 hover:underline" wire:click="delete({{ $u->id }})" wire:confirm="{{ __('Delete this user?') }}">{{ __('Delete') }}</button>@endif
                </td></tr>
        @endforeach</tbody>
    </table></div>
</div>
