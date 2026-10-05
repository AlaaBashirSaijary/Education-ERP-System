<?php

namespace App\Livewire;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TimetableEntry;
use App\Models\User;
use Livewire\Component;

class Timetable extends Component
{
    public ?int $classId = null;

    public int $day = 0;
    public int $period = 1;
    public ?int $subjectId = null;
    public ?int $teacherId = null;
    public string $startsAt = '08:00';
    public string $endsAt = '08:45';
    public string $room = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->classId = $user->hasRole('parent')
            ? Student::visibleTo($user)->value('school_class_id')
            : SchoolClass::orderBy('name')->value('id');
    }

    public function add(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        $d = $this->validate([
            'classId' => 'required|exists:school_classes,id', 'subjectId' => 'required|exists:subjects,id',
            'teacherId' => 'required|exists:users,id', 'day' => 'required|integer|between:0,6', 'period' => 'required|integer|min:1|max:12',
            'startsAt' => 'required|date_format:H:i', 'endsAt' => 'required|date_format:H:i|after:startsAt', 'room' => 'nullable|string|max:30',
        ]);

        $slot = ['day_of_week' => $d['day'], 'period' => $d['period']];
        if (TimetableEntry::where($slot)->where('teacher_id', $d['teacherId'])->exists()) {
            $this->addError('teacherId', __('This teacher already has a lesson at that time.'));

            return;
        }
        if (TimetableEntry::where($slot)->where('school_class_id', $d['classId'])->exists()) {
            $this->addError('period', __('This class already has a lesson at that time.'));

            return;
        }

        TimetableEntry::create($slot + [
            'school_class_id' => $d['classId'], 'subject_id' => $d['subjectId'], 'teacher_id' => $d['teacherId'],
            'starts_at' => $d['startsAt'], 'ends_at' => $d['endsAt'], 'room' => $d['room'] ?: null,
        ]);
        $this->reset('room');
        $this->period++;
    }

    public function remove(int $id): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        TimetableEntry::whereKey($id)->delete();
    }

    public function render()
    {
        $user = auth()->user();
        $classes = $user->hasRole('parent')
            ? SchoolClass::whereIn('id', Student::visibleTo($user)->select('school_class_id'))->get()
            : SchoolClass::orderBy('name')->orderBy('section')->get();
        abort_if($this->classId && ! $classes->contains('id', $this->classId), 403);

        $entries = TimetableEntry::with(['subject', 'teacher'])->where('school_class_id', $this->classId)->get();
        // teachers see their own lessons highlighted via teacher_id
        $grid = $entries->groupBy('period')->sortKeys()->map(fn ($row) => $row->keyBy('day_of_week'));

        return view('livewire.timetable', [
            'classes' => $classes, 'grid' => $grid,
            'subjects' => Subject::orderBy('name')->get(),
            'teachers' => User::where('role', 'teacher')->orderBy('name')->get(),
        ])->title(__('Timetable'));
    }
}
