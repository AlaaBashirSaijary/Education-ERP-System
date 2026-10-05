<?php

namespace App\Livewire;

use App\Models\Exam;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Support\Years;
use Livewire\Component;

class Grades extends Component
{
    public ?int $classId = null;
    public ?int $examId = null;
    /** @var array<int,string|float|null> student_id => mark */
    public array $marks = [];

    public string $examName = '';
    public ?int $subjectId = null;
    public ?int $termId = null;
    public int $maxMark = 100;
    public string $examDate = '';
    public string $subjectName = '';
    public string $subjectCode = '';

    public function mount(): void
    {
        $this->examDate = today()->toDateString();
        $this->classId = SchoolClass::orderBy('name')->value('id');
    }

    public function updatedClassId(): void
    {
        $this->examId = null;
        $this->marks = [];
    }

    public function updatedExamId(): void
    {
        $exam = Exam::find($this->examId);
        if (! $exam) {
            $this->marks = [];

            return;
        }
        $existing = Mark::where('exam_id', $exam->id)->pluck('mark', 'student_id');
        $this->marks = Student::enrolledIn($exam->school_class_id, $exam->academic_year_id)->pluck('id')
            ->mapWithKeys(fn ($id) => [$id => $existing[$id] ?? null])->all();
    }

    public function addSubject(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        $d = $this->validate(['subjectName' => 'required|string|max:80', 'subjectCode' => 'required|string|max:20|unique:subjects,code']);
        $this->subjectId = Subject::create(['name' => $d['subjectName'], 'code' => $d['subjectCode']])->id;
        $this->reset('subjectName', 'subjectCode');
    }

    public function addExam(): void
    {
        $d = $this->validate([
            'classId' => 'required|exists:school_classes,id', 'subjectId' => 'required|exists:subjects,id',
            'examName' => 'required|string|max:100', 'maxMark' => 'required|integer|min:1|max:1000', 'examDate' => 'required|date',
        ]);
        $this->examId = Exam::create([
            'name' => $d['examName'], 'school_class_id' => $d['classId'], 'subject_id' => $d['subjectId'],
            'max_mark' => $d['maxMark'], 'date' => $d['examDate'],
            'academic_year_id' => app(Years::class)->selected()?->id,
            'term_id' => $this->termId ?: null,
        ])->id;
        $this->reset('examName');
        $this->updatedExamId();
    }

    public function saveMarks(): void
    {
        $exam = Exam::findOrFail($this->examId);
        $this->validate(['marks.*' => "nullable|numeric|min:0|max:{$exam->max_mark}"], [
            'marks.*.max' => __('Mark cannot exceed the maximum.'),
        ]);

        $ids = Student::enrolledIn($exam->school_class_id, $exam->academic_year_id)->pluck('id')->all();
        foreach ($this->marks as $studentId => $mark) {
            if (! in_array((int) $studentId, $ids, true)) {
                continue;
            }
            if ($mark === null || $mark === '') {
                Mark::where(['exam_id' => $exam->id, 'student_id' => $studentId])->delete();
            } else {
                Mark::updateOrCreate(['exam_id' => $exam->id, 'student_id' => $studentId], ['mark' => $mark]);
            }
        }
        session()->flash('ok', __('Marks saved.'));
    }

    public function render()
    {
        $year = app(Years::class)->selected();
        $exam = $this->examId ? Exam::find($this->examId) : null;

        return view('livewire.grades', [
            'year' => $year,
            'terms' => $year?->terms ?? collect(),
            'classes' => SchoolClass::orderBy('name')->orderBy('section')->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'exams' => Exam::with(['subject', 'term'])->where('school_class_id', $this->classId)
                ->where('academic_year_id', $year?->id)->latest('date')->get(),
            'exam' => $exam,
            'students' => $exam ? Student::enrolledIn($exam->school_class_id, $exam->academic_year_id)->orderBy('name')->get() : collect(),
        ])->title(__('Grades'));
    }
}
