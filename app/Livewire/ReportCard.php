<?php

namespace App\Livewire;

use App\Models\Student;
use App\Services\Messaging\ParentNotifier;
use Livewire\Component;

class ReportCard extends Component
{
    public Student $student;

    public function mount(Student $student): void
    {
        abort_unless(Student::visibleTo(auth()->user())->whereKey($student->id)->exists(), 403);
        $this->student = $student;
    }

    private function card(): array
    {
        $marks = $this->student->marks()->with('exam.subject')->get();
        $subjects = $marks->groupBy(fn ($m) => $m->exam->subject->name)->map(fn ($rows, $name) => [
            'subject' => $name,
            'percentage' => round($rows->sum('mark') / $rows->sum(fn ($m) => $m->exam->max_mark) * 100, 1),
            'exams' => $rows,
        ])->values();
        $max = $marks->sum(fn ($m) => $m->exam->max_mark);

        return [
            'subjects' => $subjects,
            'overall' => $max ? round($marks->sum('mark') / $max * 100, 1) : null,
            'attendance' => $this->student->attendances()->selectRaw('status, count(*) as days')->groupBy('status')->pluck('days', 'status'),
        ];
    }

    public function sendToParent(ParentNotifier $notifier): void
    {
        abort_unless(auth()->user()->hasRole('admin', 'teacher'), 403);
        $card = $this->card();
        $lines = $card['subjects']->map(fn ($s) => "{$s['subject']}: {$s['percentage']}%")->implode("\n");
        $sent = $notifier->notify($this->student, 'report_card',
            "تقرير {$this->student->name}\n{$lines}\nالمعدل العام: {$card['overall']}%");

        session()->flash($sent ? 'ok' : 'warn', $sent ? __('Report sent to the parent.') : __('No parent phone number on file.'));
    }

    public function render()
    {
        return view('livewire.report-card', $this->card())->title(__('Report card'));
    }
}
