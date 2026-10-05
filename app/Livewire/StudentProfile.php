<?php

namespace App\Livewire;

use App\Models\Student;
use App\Support\Years;
use Livewire\Component;

class StudentProfile extends Component
{
    public Student $student;

    public function mount(Student $student): void
    {
        abort_unless(Student::visibleTo(auth()->user())->whereKey($student->id)->exists(), 403);
        $this->student = $student->load(['schoolClass', 'parent']);
    }

    public function render()
    {
        $year = app(Years::class)->selected();
        $s = $this->student;
        $canSeeFees = ! auth()->user()->hasRole('teacher');

        $range = $year ? [$year->starts_on->toDateString(), $year->ends_on->toDateString()] : null;
        $attendance = $s->attendances()->when($range, fn ($q) => $q->whereBetween('date', $range))
            ->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $days = $attendance->sum();
        $rate = $days ? round((($attendance['present'] ?? 0) + ($attendance['late'] ?? 0)) / $days * 100) : null;

        $marks = $s->marks()->whereHas('exam', fn ($q) => $q->where('academic_year_id', $year?->id))->with('exam.subject')->get();
        $bySubject = $marks->groupBy(fn ($m) => $m->exam->subject->name)->map(fn ($rows, $name) => [
            'subject' => $name, 'subject_id' => $rows->first()->exam->subject_id,
            'pct' => round($rows->sum('mark') / $rows->sum(fn ($m) => $m->exam->max_mark) * 100),
        ])->values();
        $max = $marks->sum(fn ($m) => $m->exam->max_mark);

        $fees = $canSeeFees ? $s->fees()->where('academic_year_id', $year?->id)->with('payments')->orderBy('due_date')->get() : collect();

        return view('livewire.student-profile', [
            'year' => $year, 'attendance' => $attendance, 'rate' => $rate, 'bySubject' => $bySubject,
            'overall' => $max ? round($marks->sum('mark') / $max * 100) : null,
            'fees' => $fees, 'canSeeFees' => $canSeeFees,
            'enrollment' => $s->enrollments()->where('academic_year_id', $year?->id)->with('schoolClass')->first(),
            'history' => $s->enrollments()->with(['academicYear', 'schoolClass'])->get()->sortByDesc(fn ($e) => $e->academicYear->starts_on)->values(),
            'recent' => $s->attendances()->latest('date')->take(10)->get(),
        ])->title($s->name);
    }
}
