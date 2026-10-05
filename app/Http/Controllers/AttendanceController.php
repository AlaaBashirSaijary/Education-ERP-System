<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Services\Messaging\ParentNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    /** Gate scanner: a QR token or fingerprint id marks the student present (or late). */
    public function scan(Request $request)
    {
        $data = $request->validate([
            'qr_token' => 'required_without:fingerprint_id|string',
            'fingerprint_id' => 'required_without:qr_token|string',
        ]);

        $student = Student::where('active', true)
            ->when(isset($data['qr_token']), fn ($q) => $q->where('qr_token', $data['qr_token']),
                fn ($q) => $q->where('fingerprint_id', $data['fingerprint_id']))
            ->first();

        abort_unless($student, 404, 'Unknown student.');

        $now = now();
        $lateAt = Carbon::parse(config('school.late_after'), $now->timezone)->setDateFrom($now);

        // firstOrCreate keeps double scans idempotent (unique student_id+date).
        $attendance = Attendance::firstOrCreate(
            ['student_id' => $student->id, 'date' => $now->toDateString()],
            [
                'status' => $now->greaterThan($lateAt) ? 'late' : 'present',
                'method' => isset($data['qr_token']) ? 'qr' : 'fingerprint',
                'checked_in_at' => $now,
                'recorded_by' => $request->user()->id,
            ]
        );

        if ($attendance->wasRecentlyCreated) {
            app(ParentNotifier::class)->notify($student, 'attendance',
                "وصل {$student->name} إلى المدرسة الساعة {$now->format('H:i')}.");
        }

        return response()->json(['student' => $student->name, 'status' => $attendance->status,
            'duplicate' => ! $attendance->wasRecentlyCreated]);
    }

    /** Teacher records a whole class manually: [{student_id, status}, ...]. */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'records' => 'required|array|min:1',
            'records.*.student_id' => 'required|exists:students,id',
            'records.*.status' => 'required|in:present,absent,late',
        ]);

        $notifier = app(ParentNotifier::class);

        foreach ($data['records'] as $r) {
            $attendance = Attendance::updateOrCreate(
                ['student_id' => $r['student_id'], 'date' => $data['date']],
                ['status' => $r['status'], 'method' => 'manual', 'recorded_by' => $request->user()->id]
            );

            if ($r['status'] === 'absent' && ($attendance->wasRecentlyCreated || $attendance->wasChanged('status'))) {
                $notifier->notify($attendance->student, 'absence', $this->absenceMessage($attendance->student, $data['date']));
            }
        }

        return response()->noContent();
    }

    public function report(Request $request)
    {
        $data = $request->validate(['date' => 'required|date', 'school_class_id' => 'nullable|exists:school_classes,id']);

        return Attendance::with('student:id,name,school_class_id')
            ->whereDate('date', $data['date'])
            ->when($data['school_class_id'] ?? null,
                fn ($q, $c) => $q->whereHas('student', fn ($s) => $s->where('school_class_id', $c)))
            ->get();
    }

    public static function absenceMessage(Student $student, string $date): string
    {
        return "نفيدكم بأن الطالب/ة {$student->name} غائب/ة اليوم ({$date}) – ".config('school.name');
    }
}
