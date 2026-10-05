<?php

namespace App\Livewire;

use App\Models\StaffAttendance as Record;
use App\Models\User;
use App\Services\ReportService;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Admin: daily attendance sheet and monthly report for admins, teachers and accountants. */
class StaffAttendance extends Component
{
    #[Url] public string $tab = 'daily'; // daily|monthly
    public string $date = '';
    #[Url] public string $month = '';
    /** @var array<int,array{status:string,in:string,out:string,note:string}> keyed by user id */
    public array $rows = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        $this->date = today()->toDateString();
        $this->month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->month) ? $this->month : today()->format('Y-m');
        $this->tab = in_array($this->tab, ['daily', 'monthly'], true) ? $this->tab : 'daily';
        $this->load();
    }

    public function updatedDate(): void
    {
        $this->load();
    }

    private function load(): void
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->date)) {
            $this->date = today()->toDateString();
        }
        $existing = Record::whereDate('date', $this->date)->get()->keyBy('user_id');

        $this->rows = User::staff()->pluck('id')->mapWithKeys(function ($id) use ($existing) {
            $e = $existing->get($id);

            return [$id => [
                'status' => $e->status ?? '', 'in' => substr((string) ($e->check_in_at ?? ''), 0, 5),
                'out' => substr((string) ($e->check_out_at ?? ''), 0, 5), 'note' => (string) ($e->note ?? ''),
            ]];
        })->all();
        $this->resetValidation();
    }

    public function markAll(string $status): void
    {
        abort_unless(in_array($status, Record::STATUSES, true), 422);
        foreach ($this->rows as $id => $r) {
            $this->rows[$id]['status'] = $status;
            if (in_array($status, ['absent', 'leave'], true)) {
                $this->rows[$id]['in'] = $this->rows[$id]['out'] = '';
            }
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        $this->validate([
            'date' => 'required|date_format:Y-m-d',
            'rows.*.status' => 'nullable|in:'.implode(',', Record::STATUSES),
            'rows.*.in' => 'nullable|date_format:H:i',
            'rows.*.out' => 'nullable|date_format:H:i',
            'rows.*.note' => 'nullable|string|max:190',
        ]);

        $valid = User::staff()->pluck('id')->all();
        $bad = false;
        foreach ($this->rows as $id => $r) {
            if ($r['in'] !== '' && $r['out'] !== '' && $r['out'] < $r['in']) {
                $this->addError("rows.$id.out", __('Check-out is before check-in.'));
                $bad = true;
            }
        }
        if ($bad) {
            return;
        }

        $saved = 0;
        foreach ($this->rows as $id => $r) {
            if (! in_array((int) $id, $valid, true)) {
                continue;
            }
            $key = ['user_id' => (int) $id, 'date' => $this->date];
            if ($r['status'] === '') {
                Record::where($key)->delete(); // cleared = not recorded

                continue;
            }
            $worked = in_array($r['status'], ['present', 'late'], true);
            Record::updateOrCreate($key, [
                'status' => $r['status'], 'method' => 'manual', 'recorded_by' => auth()->id(),
                'check_in_at' => $worked && $r['in'] !== '' ? $r['in'].':00' : null,
                'check_out_at' => $worked && $r['out'] !== '' ? $r['out'].':00' : null,
                'note' => $r['note'] !== '' ? $r['note'] : null,
            ]);
            $saved++;
        }
        session()->flash('ok', __('Staff attendance saved (:n records).', ['n' => $saved]));
    }

    public function render(ReportService $reports)
    {
        $staff = User::staff()->orderBy('role')->orderBy('name')->get();
        $statuses = array_column($this->rows, 'status');
        $tally = array_count_values(array_filter($statuses)) + ['present' => 0, 'late' => 0, 'absent' => 0, 'leave' => 0];
        $tally['none'] = count(array_filter($statuses, fn ($s) => $s === ''));

        $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->month) ? $this->month : today()->format('Y-m');

        return view('livewire.staff-attendance', [
            'staff' => $staff, 'tally' => $tally, 'monthValid' => $month,
            'summary' => $this->tab === 'monthly' ? $reports->staffAttendanceSummary($month) : collect(),
        ])->title(__('Staff attendance'));
    }
}
