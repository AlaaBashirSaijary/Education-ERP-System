<?php

namespace App\Livewire;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Students extends Component
{
    use WithPagination;

    #[Url] public string $search = '';
    public bool $showForm = false;

    public string $student_no = '';
    public string $name = '';
    public ?int $school_class_id = null;
    public string $parent_phone = '';
    public string $parent_name = '';
    public string $parent_email = '';
    public string $fingerprint_id = '';

    public string $newClass = '';
    public string $newSection = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    private function admin(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
    }

    public function addClass(): void
    {
        $this->admin();
        $data = $this->validate(['newClass' => 'required|string|max:50', 'newSection' => 'nullable|string|max:20']);
        $class = SchoolClass::firstOrCreate(['name' => $data['newClass'], 'section' => $data['newSection'] ?: null]);
        $this->school_class_id = $class->id;
        $this->reset('newClass', 'newSection');
    }

    public function save(): void
    {
        $this->admin();
        $data = $this->validate([
            'student_no' => 'required|string|max:30|unique:students,student_no',
            'name' => 'required|string|max:120',
            'school_class_id' => 'required|exists:school_classes,id',
            'parent_phone' => 'nullable|string|max:20',
            'parent_name' => 'nullable|string|max:120',
            'parent_email' => 'nullable|email|unique:users,email',
            'fingerprint_id' => 'nullable|string|max:50|unique:students,fingerprint_id',
        ]);

        // Optionally create a parent login so the family can follow the child online.
        $parent = null;
        if ($data['parent_email']) {
            $parent = User::create([
                'name' => $data['parent_name'] ?: $data['name'].' (parent)',
                'email' => $data['parent_email'], 'phone' => $data['parent_phone'] ?: null,
                'role' => 'parent', 'password' => str()->random(16),
            ]);
        }

        Student::create([
            'student_no' => $data['student_no'], 'name' => $data['name'], 'school_class_id' => $data['school_class_id'],
            'parent_phone' => $data['parent_phone'] ?: null, 'parent_id' => $parent?->id,
            'fingerprint_id' => $data['fingerprint_id'] ?: null,
        ]);

        $this->reset('student_no', 'name', 'parent_phone', 'parent_name', 'parent_email', 'fingerprint_id', 'showForm');
        session()->flash('ok', __('Student added.'));
    }

    public function render()
    {
        $students = Student::visibleTo(auth()->user())->with('schoolClass')
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")->orWhere('student_no', 'like', "%{$this->search}%")))
            ->orderBy('name')->paginate(15);

        return view('livewire.students', [
            'students' => $students,
            'classes' => SchoolClass::orderBy('name')->orderBy('section')->get(),
        ])->title(__('Students'));
    }
}
