<?php

namespace App\Livewire;

use App\Services\GuardianLinkService;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Profile extends Component
{
    public string $name = '';
    public string $phone = '';
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $child_no = '';
    public string $child_phone = '';

    public function mount(): void
    {
        $this->name = auth()->user()->name;
        $this->phone = (string) auth()->user()->phone;
    }

    public function saveProfile(): void
    {
        $d = $this->validate(['name' => 'required|string|max:120', 'phone' => 'nullable|string|max:20']);
        auth()->user()->update(['name' => $d['name'], 'phone' => $d['phone'] ?: null]);
        session()->flash('ok', __('Saved.'));
    }

    public function changePassword(): void
    {
        if (auth()->user()->isDemoAccount()) {
            $this->addError('current_password', __('This is a shared demo account; its password cannot be changed.'));

            return;
        }
        $d = $this->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|max:100|confirmed',
        ]);

        if (! Hash::check($d['current_password'], auth()->user()->password)) {
            $this->addError('current_password', __('Current password is incorrect.'));

            return;
        }

        auth()->user()->update(['password' => $d['password']]);
        $this->reset('current_password', 'password', 'password_confirmation');
        session()->flash('ok', __('Password changed.'));
    }

    /** A parent links another child with the same two-fact check used at sign-up. */
    public function linkChild(GuardianLinkService $links): void
    {
        abort_unless(auth()->user()->hasRole('parent'), 403);
        $d = $this->validate(['child_no' => 'required|string|max:30', 'child_phone' => 'required|string|max:20']);

        if (! $links->claim(auth()->user(), $d['child_no'], $d['child_phone'])) {
            $this->addError('child_no', __('We could not match these details to a student. Check the student number and the phone number the school has on file, or contact the school.'));

            return;
        }
        $this->reset('child_no', 'child_phone');
        session()->flash('ok', __('Child linked to your account.'));
    }

    public function render()
    {
        return view('livewire.profile')->title(__('My profile'));
    }
}
