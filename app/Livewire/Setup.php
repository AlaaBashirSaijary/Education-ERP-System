<?php

namespace App\Livewire;

use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TimetableEntry;
use Illuminate\Validation\Rule;
use Livewire\Component;

/** Admin setup: classes and subjects. */
class Setup extends Component
{
    public string $className = '';
    public string $classSection = '';
    public string $subjectName = '';
    public string $subjectCode = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
    }

    public function addClass(): void
    {
        $d = $this->validate(['className' => 'required|string|max:50', 'classSection' => 'nullable|string|max:20']);
        $section = $d['classSection'] ?: null;

        if (SchoolClass::where('name', $d['className'])->where('section', $section)->exists()) {
            $this->addError('className', __('This class already exists.'));

            return;
        }
        SchoolClass::create(['name' => $d['className'], 'section' => $section]);
        $this->reset('className', 'classSection');
    }

    public function deleteClass(int $id): void
    {
        $class = SchoolClass::withCount('students')->findOrFail($id);
        // Deleting a class would cascade-delete its students, so refuse instead.
        if ($class->students_count > 0 || \App\Models\Enrollment::where('school_class_id', $id)->exists()) {
            session()->flash('warn', __('Move or delete the students of this class first.'));

            return;
        }
        $class->delete();
    }

    public function addSubject(): void
    {
        $d = $this->validate([
            'subjectName' => 'required|string|max:80',
            'subjectCode' => ['required', 'string', 'max:20', Rule::unique('subjects', 'code')],
        ]);
        Subject::create(['name' => $d['subjectName'], 'code' => $d['subjectCode']]);
        $this->reset('subjectName', 'subjectCode');
    }

    public function deleteSubject(int $id): void
    {
        if (Exam::where('subject_id', $id)->exists() || TimetableEntry::where('subject_id', $id)->exists()) {
            session()->flash('warn', __('This subject is used by exams or the timetable.'));

            return;
        }
        Subject::findOrFail($id)->delete();
    }

    public function render()
    {
        return view('livewire.setup', [
            'classes' => SchoolClass::withCount('students')->orderBy('name')->orderBy('section')->get(),
            'subjects' => Subject::orderBy('name')->get(),
        ])->title(__('Setup'));
    }
}
