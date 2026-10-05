<?php

namespace App\Livewire;

use App\Models\Fee;
use App\Models\Student;
use App\Services\FeeService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

class Fees extends Component
{
    #[Url] public ?int $studentId = null;
    public string $tab = 'statement'; // statement|overdue

    public string $title = '';
    public string $total = '';
    public int $instalments = 3;
    public string $firstDue = '';

    public ?int $payFeeId = null;
    public string $payAmount = '';
    public string $payMethod = 'cash';
    public string $payRef = '';

    public function mount(): void
    {
        $this->firstDue = today()->addMonth()->toDateString();
        $this->studentId ??= Student::visibleTo(auth()->user())->orderBy('name')->value('id');
    }

    private function staff(): void
    {
        abort_unless(auth()->user()->hasRole('admin', 'accountant'), 403);
    }

    public function createPlan(): void
    {
        $this->staff();
        $d = $this->validate([
            'studentId' => 'required|exists:students,id', 'title' => 'required|string|max:100',
            'total' => 'required|numeric|min:0.01', 'instalments' => 'required|integer|min:1|max:36', 'firstDue' => 'required|date',
        ]);

        $cents = (int) round($d['total'] * 100);
        $n = $d['instalments'];
        $base = intdiv($cents, $n);
        $first = Carbon::parse($d['firstDue']);

        DB::transaction(function () use ($d, $n, $base, $cents, $first) {
            foreach (range(1, $n) as $i) {
                Fee::create([
                    'student_id' => $d['studentId'], 'title' => "{$d['title']} ({$i}/{$n})",
                    'amount' => ($i === $n ? $cents - $base * ($n - 1) : $base) / 100,
                    'due_date' => $first->copy()->addMonthsNoOverflow($i - 1),
                ]);
            }
        });

        $this->reset('title', 'total');
        session()->flash('ok', __('Instalment plan created.'));
    }

    public function startPay(int $feeId): void
    {
        $this->staff();
        $fee = Fee::with('payments')->findOrFail($feeId);
        $this->payFeeId = $feeId;
        $this->payAmount = $fee->balance;
        $this->resetErrorBag();
    }

    public function pay(FeeService $service): void
    {
        $this->staff();
        $d = $this->validate(['payAmount' => 'required|numeric|min:0.01', 'payMethod' => 'required|in:cash,card,transfer', 'payRef' => 'nullable|string|max:80']);

        try {
            $service->pay(Fee::findOrFail($this->payFeeId), (float) $d['payAmount'], $d['payMethod'], $d['payRef'] ?: null, auth()->user());
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('payAmount', $e->errors()['amount'][0]);

            return;
        }

        $this->reset('payFeeId', 'payAmount', 'payRef');
        session()->flash('ok', __('Payment recorded. Parent was notified.'));
    }

    public function render()
    {
        $user = auth()->user();
        $students = Student::visibleTo($user)->orderBy('name')->get(['id', 'name']);
        abort_if($this->studentId && ! $students->contains('id', $this->studentId), 403);

        $fees = $this->studentId
            ? Fee::with('payments')->where('student_id', $this->studentId)->orderBy('due_date')->get() : collect();

        $overdue = $this->tab === 'overdue' && $user->hasRole('admin', 'accountant')
            ? Fee::with(['payments', 'student'])->where('due_date', '<', today())->orderBy('due_date')->get()
                ->filter(fn ($f) => (float) $f->balance > 0)
            : collect();

        return view('livewire.fees', compact('students', 'fees', 'overdue'))->title(__('Fees'));
    }
}
