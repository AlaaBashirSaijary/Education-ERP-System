<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Profile extends Component
{
    public string $name = '';
    public string $phone = '';
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

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

    public function render()
    {
        return view('livewire.profile')->title(__('My profile'));
    }
}
