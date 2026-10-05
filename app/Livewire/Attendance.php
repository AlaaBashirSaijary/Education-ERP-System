<?php

namespace App\Livewire;

use App\Models\Attendance as AttendanceModel;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\AttendanceService;
use Livewire\Component;

class Attendance extends Component
{
    public ?int $classId = null;
    public string $date = '';
    /** @var array<int,string> student_id => status */
    public array $statuses = [];

    public function mount(): void
    {
        $this->date = today()->toDateString();
        $this->classId = SchoolClass::orderBy('name')->value('id');
        $this->load();
    }

    public function updated($name): void
    {
        if (in_array($name, ['classId', 'date'], true)) {
            $this->load();
        }
    }

    private function load(): void
    {
        $existing = AttendanceModel::whereDate('date', $this->date)->pluck('status', 'student_id');
        $this->statuses = Student::where('school_class_id', $this->classId)->where('active', true)
            ->pluck('id')->mapWithKeys(fn ($id) => [$id => $existing[$id] ?? 'present'])->all();
    }

    public function markAll(string $status): void
    {
        abort_unless(in_array($status, ['present', 'absent', 'late'], true), 422);
        $this->statuses = array_map(fn () => $status, $this->statuses);
    }

    public function save(AttendanceService $service): void
    {
        $this->validate(['date' => 'required|date', 'classId' => 'required|exists:school_classes,id']);

        $ids = Student::where('school_class_id', $this->classId)->pluck('id')->all();
        foreach ($this->statuses as $id => $status) {
            if (in_array((int) $id, $ids, true) && in_array($status, ['present', 'absent', 'late'], true)) {
                $service->mark((int) $id, $this->date, $status, auth()->user());
            }
        }

        session()->flash('ok', __('Attendance saved. Parents of absent students were notified.'));
    }

    public function render()
    {
        return view('livewire.attendance', [
            'classes' => SchoolClass::orderBy('name')->orderBy('section')->get(),
            'students' => Student::where('school_class_id', $this->classId)->where('active', true)->orderBy('name')->get(),
        ])->title(__('Attendance'));
    }
}
