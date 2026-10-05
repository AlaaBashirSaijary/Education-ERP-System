<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\StaffAttendance;
use App\Models\User;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Per-student attendance counts for a month ("YYYY-MM").
     *
     * @return Collection<int, array{student:string,class:string,present:int,late:int,absent:int,rate:?float}>
     */
    public function attendanceSummary(?int $classId, string $month): Collection
    {
        $from = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $to = $from->copy()->endOfMonth();

        $counts = Attendance::whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw("student_id, sum(case when status='present' then 1 else 0 end) as present, sum(case when status='late' then 1 else 0 end) as late, sum(case when status='absent' then 1 else 0 end) as absent")
            ->groupBy('student_id')->get()->keyBy('student_id');

        return Student::with('schoolClass')->where('active', true)
            ->when($classId, fn ($q) => $q->where('school_class_id', $classId))
            ->orderBy('name')->get()
            ->map(function (Student $s) use ($counts) {
                $c = $counts->get($s->id);
                [$p, $l, $a] = [(int) ($c->present ?? 0), (int) ($c->late ?? 0), (int) ($c->absent ?? 0)];
                $days = $p + $l + $a;

                return [
                    'student' => $s->name,
                    'class' => trim($s->schoolClass->name.' '.$s->schoolClass->section),
                    'present' => $p, 'late' => $l, 'absent' => $a,
                    'rate' => $days ? round(($p + $l) / $days * 100, 1) : null,
                ];
            });
    }

    /**
     * Billed / collected / overdue per class.
     *
     * @return Collection<int, array{class:string,billed:float,collected:float,outstanding:float,overdue:float}>
     */
    public function financeByClass(?int $yearId = null): Collection
    {
        $paid = DB::table('payments')->selectRaw('fee_id, sum(amount) as paid')->groupBy('fee_id');

        // Class = the one the student attended in the fee's year (falls back to their current class).
        $rows = DB::table('fees')
            ->join('students', 'students.id', '=', 'fees.student_id')
            ->leftJoin('enrollments as e', fn ($j) => $j->on('e.student_id', '=', 'fees.student_id')->on('e.academic_year_id', '=', 'fees.academic_year_id'))
            ->leftJoinSub($paid, 'p', 'p.fee_id', '=', 'fees.id')
            ->when($yearId, fn ($q) => $q->where('fees.academic_year_id', $yearId))
            ->selectRaw('coalesce(e.school_class_id, students.school_class_id) as class_id, sum(fees.amount) as billed, sum(coalesce(p.paid,0)) as collected, '
                .'sum(case when fees.due_date < ? and fees.amount - coalesce(p.paid,0) > 0 then fees.amount - coalesce(p.paid,0) else 0 end) as overdue', [today()->toDateString()])
            ->groupBy(DB::raw('coalesce(e.school_class_id, students.school_class_id)'))->get()->keyBy('class_id');

        return SchoolClass::orderBy('name')->orderBy('section')->get()->map(function (SchoolClass $c) use ($rows) {
            $r = $rows->get($c->id);
            $billed = (float) ($r->billed ?? 0);
            $collected = (float) ($r->collected ?? 0);

            return [
                'class' => trim($c->name.' '.$c->section),
                'billed' => $billed, 'collected' => $collected,
                'outstanding' => round($billed - $collected, 2), 'overdue' => (float) ($r->overdue ?? 0),
            ];
        });
    }

    /** Payments received per month for the last $months months, oldest first. @return array<string,float> */
    public function collectionsByMonth(int $months = 6): array
    {
        $start = today()->startOfMonth()->subMonths($months - 1);
        $out = [];
        foreach (range(0, $months - 1) as $i) {
            $out[$start->copy()->addMonths($i)->format('Y-m')] = 0.0;
        }

        Payment::where('paid_at', '>=', $start)->get(['amount', 'paid_at'])
            ->each(function ($p) use (&$out) {
                $k = $p->paid_at->format('Y-m');
                if (isset($out[$k])) {
                    $out[$k] += (float) $p->amount;
                }
            });

        return $out;
    }

    /** Attendance totals for the last $days calendar days, oldest first. @return list<array{date:string,present:int,absent:int}> */
    public function attendanceTrend(int $days = 7): array
    {
        $start = today()->subDays($days - 1);
        $rows = Attendance::where('date', '>=', $start->toDateString())
            ->selectRaw("date, sum(case when status in ('present','late') then 1 else 0 end) as present, sum(case when status='absent' then 1 else 0 end) as absent")
            ->groupBy('date')->get()->keyBy(fn ($r) => substr((string) $r->date, 0, 10));

        return collect(range(0, $days - 1))->map(function ($i) use ($start, $rows) {
            $d = $start->copy()->addDays($i);
            $r = $rows->get($d->toDateString());

            return ['date' => $d->toDateString(), 'present' => (int) ($r->present ?? 0), 'absent' => (int) ($r->absent ?? 0)];
        })->all();
    }

    /**
     * Per-employee attendance for a month ("YYYY-MM"): counts, worked hours, attendance rate.
     * Leave days are excluded from the rate so approved leave does not count against anyone.
     *
     * @return Collection<int, array{name:string,role:string,present:int,late:int,absent:int,leave:int,hours:float,rate:?float}>
     */
    public function staffAttendanceSummary(string $month): Collection
    {
        $from = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $records = StaffAttendance::whereBetween('date', [$from->toDateString(), $from->copy()->endOfMonth()->toDateString()])
            ->get()->groupBy('user_id');

        return User::staff()->orderBy('role')->orderBy('name')->get()->map(function (User $u) use ($records) {
            $rows = $records->get($u->id, collect());
            $count = fn (string $st) => $rows->where('status', $st)->count();
            [$p, $l, $a, $lv] = [$count('present'), $count('late'), $count('absent'), $count('leave')];
            $minutes = $rows->sum(fn (StaffAttendance $r) => $r->workedMinutes() ?? 0);
            $counted = $p + $l + $a;

            return [
                'name' => $u->name, 'role' => $u->role, 'present' => $p, 'late' => $l, 'absent' => $a, 'leave' => $lv,
                'hours' => round($minutes / 60, 1), 'rate' => $counted ? round(($p + $l) / $counted * 100, 1) : null,
            ];
        });
    }
}
