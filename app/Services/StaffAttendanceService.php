<?php

namespace App\Services;

use App\Models\StaffAttendance;
use App\Models\User;
use Illuminate\Support\Carbon;

class StaffAttendanceService
{
    public function today(User $user): ?StaffAttendance
    {
        return StaffAttendance::where('user_id', $user->id)->whereDate('date', today()->toDateString())->first();
    }

    /**
     * Self check-in. Idempotent: a second tap keeps the first time. A day the admin marked as
     * leave stays leave; a day marked absent (e.g. by mistake) becomes present/late with the real time.
     */
    public function checkIn(User $user): StaffAttendance
    {
        abort_unless($user->isStaff(), 403);
        $now = now();
        $late = $now->greaterThan(Carbon::parse(config('school.staff_late_after'), $now->timezone)->setDateFrom($now));
        $existing = $this->today($user);

        if ($existing && ($existing->check_in_at || $existing->status === 'leave')) {
            return $existing;
        }

        return StaffAttendance::updateOrCreate(
            ['user_id' => $user->id, 'date' => $now->toDateString()],
            ['status' => $late ? 'late' : 'present', 'check_in_at' => $now->format('H:i:s'), 'method' => 'self', 'recorded_by' => $user->id]
        );
    }

    /** Records the leaving time; only possible after a check-in. */
    public function checkOut(User $user): ?StaffAttendance
    {
        abort_unless($user->isStaff(), 403);
        $record = $this->today($user);
        if (! $record || ! $record->check_in_at) {
            return null;
        }
        $record->update(['check_out_at' => now()->format('H:i:s')]);

        return $record;
    }
}
