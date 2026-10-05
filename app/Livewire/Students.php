<?php

namespace App\Livewire;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Validation\Rule;
use App\Support\Years;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Students extends Component
{
    use WithPagination;

    #[Url] public string $search = '';
    #[Url] public ?int $classFilter = null;
    public bool $showForm = false;
    public ?int $editingId = null;

    public string $student_no = '';
    public string $name = '';
    public ?int $school_class_id = null;
    public ?int $parent_id = null;
    public string $parent_phone = '';
    public string $fingerprint_id = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingClassFilter(): void
    {
        $this->resetPage();
    }

    private function admin(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
    }

    public function create(): void
    {
        $this->admin();
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->admin();
        $s = Student::findOrFail($id);
        $this->editingId = $s->id;
        $this->student_no = $s->student_no;
        $this->name = $s->name;
        $this->school_class_id = $s->school_class_id;
        $this->parent_id = $s->parent_id;
        $this->parent_phone = (string) $s->parent_phone;
        $this->fingerprint_id = (string) $s->fingerprint_id;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function resetForm(): void
    {
        $this->reset('editingId', 'student_no', 'name', 'school_class_id', 'parent_id', 'parent_phone', 'fingerprint_id', 'showForm');
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->admin();
        $data = $this->validate([
            'student_no' => ['required', 'string', 'max:30', Rule::unique('students', 'student_no')->ignore($this->editingId)],
            'name' => 'required|string|max:120',
            'school_class_id' => 'required|exists:school_classes,id',
            'parent_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'parent')],
            'parent_phone' => 'nullable|string|max:20',
            'fingerprint_id' => ['nullable', 'string', 'max:50', Rule::unique('students', 'fingerprint_id')->ignore($this->editingId)],
        ]);
        $data['parent_phone'] = $data['parent_phone'] ?: null;
        $data['fingerprint_id'] = $data['fingerprint_id'] ?: null;

        $this->editingId ? Student::findOrFail($this->editingId)->update($data) : Student::create($data);

        $this->resetForm();
        session()->flash('ok', __('Saved.'));
    }

    public function toggleActive(int $id): void
    {
        $this->admin();
        $s = Student::findOrFail($id);
        $s->update(['active' => ! $s->active]);
    }

    public function delete(int $id): void
    {
        $this->admin();
        Student::findOrFail($id)->delete();
        session()->flash('ok', __('Student deleted.'));
    }

    public function render()
    {
        $year = app(Years::class)->selected();
        $students = Student::visibleTo(auth()->user())
            ->with(['parent', 'schoolClass', 'enrollments' => fn ($q) => $q->where('academic_year_id', $year?->id)->with('schoolClass')])
            ->enrolledIn($this->classFilter, $year?->id)
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")->orWhere('student_no', 'like', "%{$this->search}%")))
            ->orderBy('name')->paginate(15);

        return view('livewire.students', [
            'students' => $students,
            'year' => $year,
            'isCurrent' => app(Years::class)->isViewingCurrent(),
            'classes' => SchoolClass::orderBy('name')->orderBy('section')->get(),
            'parents' => auth()->user()->hasRole('admin') ? User::where('role', 'parent')->orderBy('name')->get(['id', 'name', 'email']) : collect(),
        ])->title(__('Students'));
    }
}
