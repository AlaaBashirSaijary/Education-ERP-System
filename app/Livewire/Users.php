<?php

namespace App\Livewire;

use App\Models\Student;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Users extends Component
{
    public const ROLES = ['admin', 'teacher', 'accountant', 'parent'];

    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $role = 'teacher';
    public string $password = '';
    public string $roleFilter = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $u = User::findOrFail($id);
        $this->editingId = $u->id;
        $this->name = $u->name;
        $this->email = $u->email;
        $this->phone = (string) $u->phone;
        $this->role = $u->role;
        $this->password = '';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function resetForm(): void
    {
        $this->reset('editingId', 'name', 'email', 'phone', 'role', 'password', 'showForm');
        $this->resetValidation();
    }

    public function save(): void
    {
        if ($this->editingId && User::find($this->editingId)?->isDemoAccount()) {
            session()->flash('warn', __('Demo accounts are shared by all visitors and cannot be edited.'));
            $this->resetForm();

            return;
        }

        $d = $this->validate([
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->editingId)],
            'phone' => 'nullable|string|max:20',
            'role' => ['required', Rule::in(self::ROLES)],
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:8', 'max:100'],
        ]);

        if ($this->editingId === auth()->id() && $d['role'] !== 'admin') {
            $this->addError('role', __('You cannot remove your own admin role.'));

            return;
        }

        $payload = ['name' => $d['name'], 'email' => $d['email'], 'phone' => $d['phone'] ?: null, 'role' => $d['role']];
        if ($d['password']) {
            $payload['password'] = $d['password']; // hashed by the model cast
        }

        $this->editingId ? User::findOrFail($this->editingId)->update($payload) : User::create($payload);

        $this->resetForm();
        session()->flash('ok', __('Saved.'));
    }

    public function delete(int $id): void
    {
        if ($id === auth()->id()) {
            session()->flash('warn', __('You cannot delete your own account.'));

            return;
        }
        if (User::find($id)?->isDemoAccount()) {
            session()->flash('warn', __('Demo accounts are shared by all visitors and cannot be deleted.'));

            return;
        }
        Student::where('parent_id', $id)->update(['parent_id' => null]);
        User::findOrFail($id)->delete();
        session()->flash('ok', __('User deleted.'));
    }

    public function render()
    {
        return view('livewire.users', [
            'users' => User::when($this->roleFilter, fn ($q, $r) => $q->where('role', $r))->orderBy('role')->orderBy('name')->get(),
        ])->title(__('Users'));
    }
}
