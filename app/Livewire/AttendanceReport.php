<?php

namespace App\Livewire;

use App\Models\SchoolClass;
use App\Services\ReportService;
use Livewire\Attributes\Url;
use Livewire\Component;

class AttendanceReport extends Component
{
    #[Url] public ?int $classId = null;
    #[Url] public string $month = '';

    public function mount(): void
    {
        $this->month = preg_match('/^\d{4}-\d{2}$/', $this->month) ? $this->month : today()->format('Y-m');
    }

    public function render(ReportService $reports)
    {
        $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->month) ? $this->month : today()->format('Y-m');

        return view('livewire.attendance-report', [
            'rows' => $reports->attendanceSummary($this->classId, $month),
            'classes' => SchoolClass::orderBy('name')->orderBy('section')->get(),
            'monthValid' => $month,
        ])->title(__('Attendance report'));
    }
}
