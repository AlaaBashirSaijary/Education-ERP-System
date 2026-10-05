<?php

namespace App\Livewire;

use App\Models\Attendance;
use App\Models\Fee;
use App\Models\NotificationLog;
use App\Models\Student;
use App\Models\TimetableEntry;
use App\Services\ReportService;
use App\Support\Years;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(ReportService $reports)
    {
        $user = auth()->user();
        $today = today();
        $students = Student::visibleTo($user);
        $ids = (clone $students)->select('id');
        $year = app(Years::class)->selected();

        $counts = Attendance::whereDate('date', $today->toDateString())->whereIn('student_id', $ids)
            ->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $active = (clone $students)->where('active', true)->count();
        $today_ = [
            'present' => (int) ($counts['present'] ?? 0), 'late' => (int) ($counts['late'] ?? 0), 'absent' => (int) ($counts['absent'] ?? 0),
        ];
        $today_['pending'] = max(0, $active - array_sum($today_));

        $canSeeFees = ! $user->hasRole('teacher');
        $fees = $canSeeFees ? Fee::with(['payments', 'student:id,name'])->whereIn('student_id', $ids)->get() : collect();
        $overdue = $fees->filter(fn ($f) => $f->status === 'overdue')->sortBy('due_date')->values();
        $yearFees = $fees->where('academic_year_id', $year?->id);
        $billed = $yearFees->sum(fn ($f) => (float) $f->amount);
        $collected = $yearFees->sum(fn ($f) => (float) $f->paid);

        // Today's lessons: a teacher's own; a parent's children's classes; admins see the count only.
        $lessons = collect();
        if ($user->hasRole('teacher')) {
            $lessons = TimetableEntry::with(['subject', 'schoolClass'])->where('teacher_id', $user->id)->where('day_of_week', $today->dayOfWeek)->orderBy('period')->get();
        } elseif ($user->hasRole('parent')) {
            $classIds = (clone $students)->pluck('school_class_id');
            $lessons = TimetableEntry::with(['subject', 'teacher', 'schoolClass'])->whereIn('school_class_id', $classIds)->where('day_of_week', $today->dayOfWeek)->orderBy('period')->get();
        }

        $children = $user->hasRole('parent')
            ? Student::visibleTo($user)->with(['schoolClass', 'attendances' => fn ($q) => $q->whereDate('date', $today->toDateString())])->get()
            : collect();
        $balances = $children->mapWithKeys(fn ($c) => [$c->id => $fees->where('student_id', $c->id)->sum(fn ($f) => (float) $f->balance)]);

        $hour = now()->hour;
        $greeting = $hour < 12 ? __('Good morning') : ($hour < 18 ? __('Good afternoon') : __('Good evening'));

        return view('livewire.dashboard', [
            'today' => $today_, 'active' => $active, 'overdue' => $overdue, 'outstanding' => $fees->sum(fn ($f) => (float) $f->balance),
            'billed' => $billed, 'collected' => $collected, 'year' => $year, 'greeting' => $greeting,
            'lessons' => $lessons, 'children' => $children, 'balances' => $balances,
            'trend' => $user->hasRole('parent') ? [] : $reports->attendanceTrend(7),
            'collections' => $user->hasRole('admin', 'accountant') ? $reports->collectionsByMonth(6) : [],
            'messages' => $user->hasRole('admin') ? NotificationLog::with('student')->latest()->take(5)->get() : collect(),
            'lessonCount' => $user->hasRole('admin') ? TimetableEntry::where('day_of_week', $today->dayOfWeek)->count() : null,
        ])->title(__('Dashboard'));
    }
}
