<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use App\Services\Messaging\ParentNotifier;
use Illuminate\Support\Carbon;

class AttendanceService
{
    public function __construct(private ParentNotifier $notifier) {}

    /**
     * Mark a student present/late from a QR token or fingerprint id.
     * Returns null for an unknown/inactive student; double scans are idempotent.
     *
     * @return array{student: Student, attendance: Attendance, duplicate: bool}|null
     */
    public function scan(?string $qrToken, ?string $fingerprintId, User $by): ?array
    {
        $student = Student::where('active', true)
            ->when($qrToken, fn ($q) => $q->where('qr_token', $qrToken),
                fn ($q) => $q->where('fingerprint_id', (string) $fingerprintId))
            ->first();

        if (! $student) {
            return null;
        }

        $now = now();
        $lateAt = Carbon::parse(config('school.late_after'), $now->timezone)->setDateFrom($now);

        $attendance = Attendance::firstOrCreate(
            ['student_id' => $student->id, 'date' => $now->toDateString()],
            [
                'status' => $now->greaterThan($lateAt) ? 'late' : 'present',
                'method' => $qrToken ? 'qr' : 'fingerprint',
                'checked_in_at' => $now,
                'recorded_by' => $by->id,
            ]
        );

        if ($attendance->wasRecentlyCreated) {
            $this->notifier->notify($student, 'attendance',
                "وصل {$student->name} إلى المدرسة الساعة {$now->format('H:i')}.");
        }

        return ['student' => $student, 'attendance' => $attendance, 'duplicate' => ! $attendance->wasRecentlyCreated];
    }

    /** Set one student's status for a date (manual entry); notifies parents when newly absent. */
    public function mark(int $studentId, string $date, string $status, User $by): Attendance
    {
        $attendance = Attendance::updateOrCreate(
            ['student_id' => $studentId, 'date' => $date],
            ['status' => $status, 'method' => 'manual', 'recorded_by' => $by->id]
        );

        if ($status === 'absent' && ($attendance->wasRecentlyCreated || $attendance->wasChanged('status'))) {
            $student = $attendance->student;
            $this->notifier->notify($student, 'absence', self::absenceMessage($student, $date));
        }

        return $attendance;
    }

    public static function absenceMessage(Student $student, string $date): string
    {
        return "نفيدكم بأن الطالب/ة {$student->name} غائب/ة اليوم ({$date}) – ".config('school.name');
    }
}
