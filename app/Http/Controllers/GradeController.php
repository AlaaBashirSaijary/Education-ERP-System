<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Mark;
use App\Models\Student;
use App\Services\Messaging\ParentNotifier;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function storeExam(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'school_class_id' => 'required|exists:school_classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'max_mark' => 'required|integer|min:1',
            'date' => 'required|date',
        ]);

        return response()->json(Exam::create($data), 201);
    }

    /** Enter marks for an exam: [{student_id, mark}, ...]. Re-submitting corrects a mark. */
    public function storeMarks(Request $request, Exam $exam)
    {
        $data = $request->validate([
            'marks' => 'required|array|min:1',
            'marks.*.student_id' => 'required|exists:students,id',
            'marks.*.mark' => "required|numeric|min:0|max:{$exam->max_mark}",
        ]);

        $inClass = Student::enrolledIn($exam->school_class_id, $exam->academic_year_id)
            ->whereIn('id', array_column($data['marks'], 'student_id'))->pluck('id')->all();

        abort_if(count($inClass) !== count(array_unique(array_column($data['marks'], 'student_id'))),
            422, 'Some students are not in this exam\'s class.');

        foreach ($data['marks'] as $m) {
            Mark::updateOrCreate(['exam_id' => $exam->id, 'student_id' => $m['student_id']], ['mark' => $m['mark']]);
        }

        return response()->noContent();
    }

    public function reportCard(Request $request, Student $student)
    {
        $this->authorizeStudent($request, $student);

        $yearId = app(\App\Support\Years::class)->selected()?->id;
        $marks = $student->marks()->whereHas('exam', fn ($q) => $q->where('academic_year_id', $yearId))->with('exam.subject')->get();

        $subjects = $marks->groupBy(fn ($m) => $m->exam->subject->name)->map(fn ($rows, $name) => [
            'subject' => $name,
            'percentage' => round($rows->sum('mark') / $rows->sum(fn ($m) => $m->exam->max_mark) * 100, 1),
            'exams' => $rows->map(fn ($m) => ['exam' => $m->exam->name, 'mark' => $m->mark, 'max' => $m->exam->max_mark])->values(),
        ])->values();

        $total = $marks->sum('mark');
        $max = $marks->sum(fn ($m) => $m->exam->max_mark);

        return [
            'student' => $student->name,
            'subjects' => $subjects,
            'overall_percentage' => $max ? round($total / $max * 100, 1) : null,
            'attendance' => $student->attendances()->selectRaw('status, count(*) as days')->groupBy('status')->pluck('days', 'status'),
        ];
    }

    /** Push the report-card summary to the parent. */
    public function sendReportCard(Request $request, Student $student)
    {
        $card = $this->reportCard($request, $student);
        $lines = collect($card['subjects'])->map(fn ($s) => "{$s['subject']}: {$s['percentage']}%")->implode("\n");

        app(ParentNotifier::class)->notify($student, 'report_card',
            "تقرير {$student->name}\n{$lines}\nالمعدل العام: {$card['overall_percentage']}%");

        return response()->noContent();
    }
}
