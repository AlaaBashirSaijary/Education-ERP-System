<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\User;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Admin-only audit trail: who changed what, and when. */
class AuditTrail extends Component
{
    use WithPagination;

    public const TYPES = ['Student', 'Mark', 'Exam', 'Fee', 'Payment', 'Attendance', 'StaffAttendance', 'TimetableEntry', 'SchoolClass', 'AcademicYear', 'User'];

    #[Url] public string $type = '';
    #[Url] public string $event = '';
    #[Url] public ?int $userId = null;
    #[Url] public string $search = '';
    #[Url] public string $from = '';
    #[Url] public string $to = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
    }

    public function updated($name): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $logs = AuditLog::query()
            ->when($this->type, fn ($q) => $q->where('auditable_type', $this->type))
            ->when($this->event, fn ($q) => $q->where('event', $this->event))
            ->when($this->userId, fn ($q) => $q->where('user_id', $this->userId))
            ->when($this->search, fn ($q) => $q->where('label', 'like', '%'.$this->search.'%'))
            ->when($this->from, fn ($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('created_at', '<=', $this->to))
            ->orderByDesc('id')->paginate(25);

        return view('livewire.audit-trail', [
            'logs' => $logs,
            'types' => self::TYPES,
            'staff' => User::whereIn('role', User::STAFF_ROLES)->orderBy('name')->get(['id', 'name']),
        ])->title(__('Audit trail'));
    }
}
