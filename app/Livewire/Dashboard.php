<?php

namespace App\Livewire;

use App\Models\Attendance;
use App\Models\Fee;
use App\Models\Student;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();
        $today = today()->toDateString();
        $students = Student::visibleTo($user);

        $stats = [
            'students' => (clone $students)->where('active', true)->count(),
            'present' => Attendance::whereDate('date', $today)->whereIn('status', ['present', 'late'])->whereIn('student_id', (clone $students)->select('id'))->count(),
            'absent' => Attendance::whereDate('date', $today)->where('status', 'absent')->whereIn('student_id', (clone $students)->select('id'))->count(),
        ];

        $fees = $user->hasRole('teacher') ? collect() :
            Fee::with('payments')->whereIn('student_id', (clone $students)->select('id'))->get();
        $stats['overdue_count'] = $fees->filter(fn ($f) => $f->status === 'overdue')->count();
        $stats['outstanding'] = $fees->sum(fn ($f) => (float) $f->balance);

        $children = $user->hasRole('parent')
            ? Student::visibleTo($user)->with(['schoolClass', 'attendances' => fn ($q) => $q->whereDate('date', $today)])->get()
            : collect();

        return view('livewire.dashboard', compact('stats', 'children'))
            ->title(__('Dashboard'));
    }
}
