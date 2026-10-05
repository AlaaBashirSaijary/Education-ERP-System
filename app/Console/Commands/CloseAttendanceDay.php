<?php

namespace App\Console\Commands;

use App\Services\AttendanceService;
use App\Models\Attendance;
use App\Models\Student;
use App\Services\Messaging\ParentNotifier;
use Illuminate\Console\Command;

class CloseAttendanceDay extends Command
{
    protected $signature = 'attendance:close-day';

    protected $description = 'Mark active students with no check-in today as absent and notify their parents';

    public function handle(ParentNotifier $notifier): int
    {
        $today = today()->toDateString();
        $count = 0;

        Student::where('active', true)
            ->whereDoesntHave('attendances', fn ($q) => $q->whereDate('date', $today))
            ->each(function (Student $s) use ($today, $notifier, &$count) {
                Attendance::create(['student_id' => $s->id, 'date' => $today, 'status' => 'absent', 'method' => 'manual']);
                $notifier->notify($s, 'absence', AttendanceService::absenceMessage($s, $today));
                $count++;
            });

        $this->info("Marked {$count} students absent.");

        return self::SUCCESS;
    }
}
