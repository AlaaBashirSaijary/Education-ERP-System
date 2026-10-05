<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use App\Models\Payment;
use App\Models\Student;
use App\Services\Messaging\ParentNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeController extends Controller
{
    /** Split a total into N monthly instalments (last one absorbs rounding). */
    public function createPlan(Request $request, Student $student)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'total' => 'required|numeric|min:0.01',
            'instalments' => 'required|integer|min:1|max:36',
            'first_due_date' => 'required|date',
        ]);

        $cents = (int) round($data['total'] * 100);
        $n = $data['instalments'];
        $base = intdiv($cents, $n);
        $first = \Illuminate\Support\Carbon::parse($data['first_due_date']);

        $fees = DB::transaction(function () use ($student, $data, $n, $base, $cents, $first) {
            return collect(range(1, $n))->map(fn ($i) => Fee::create([
                'student_id' => $student->id,
                'title' => "{$data['title']} ({$i}/{$n})",
                'amount' => ($i === $n ? $cents - $base * ($n - 1) : $base) / 100,
                'due_date' => $first->copy()->addMonthsNoOverflow($i - 1),
            ]));
        });

        return response()->json($fees, 201);
    }

    public function statement(Request $request, Student $student)
    {
        $this->authorizeStudent($request, $student);

        $fees = $student->fees()->with('payments')->orderBy('due_date')->get();

        return [
            'fees' => $fees,
            'total' => number_format($fees->sum('amount'), 2, '.', ''),
            'paid' => number_format($fees->sum(fn ($f) => (float) $f->paid), 2, '.', ''),
            'balance' => number_format($fees->sum(fn ($f) => (float) $f->balance), 2, '.', ''),
        ];
    }

    public function pay(Request $request, Fee $fee)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,card,transfer',
            'reference' => 'nullable|string',
        ]);

        // Lock the fee row so concurrent receipts can't overpay it.
        $payment = DB::transaction(function () use ($fee, $data, $request) {
            $locked = Fee::with('payments')->lockForUpdate()->findOrFail($fee->id);
            abort_if((float) $data['amount'] > (float) $locked->balance, 422, 'المبلغ أكبر من المتبقي على القسط.');

            return Payment::create($data + ['fee_id' => $fee->id, 'paid_at' => now(), 'received_by' => $request->user()->id]);
        });

        $fee->load('payments', 'student');
        app(ParentNotifier::class)->notify($fee->student, 'payment',
            "تم استلام {$payment->amount} للطالب {$fee->student->name} ({$fee->title}). المتبقي: {$fee->balance}");

        return response()->json($payment, 201);
    }

    public function overdue()
    {
        return Fee::with(['payments', 'student:id,name'])->where('due_date', '<', today())->get()
            ->filter(fn ($f) => (float) $f->balance > 0)->values();
    }
}
