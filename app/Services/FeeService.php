<?php

namespace App\Services;

use App\Models\Fee;
use App\Models\Payment;
use App\Models\User;
use App\Services\Messaging\ParentNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeeService
{
    public function __construct(private ParentNotifier $notifier) {}

    /** Record a payment; refuses to overpay the instalment. */
    public function pay(Fee $fee, float $amount, string $method, ?string $reference, User $by): Payment
    {
        // Lock the fee row so concurrent receipts can't overpay it.
        $payment = DB::transaction(function () use ($fee, $amount, $method, $reference, $by) {
            $locked = Fee::with('payments')->lockForUpdate()->findOrFail($fee->id);

            if ($amount > (float) $locked->balance) {
                throw ValidationException::withMessages(['amount' => __('messages.overpay')]);
            }

            return Payment::create([
                'fee_id' => $fee->id, 'amount' => $amount, 'method' => $method,
                'reference' => $reference, 'paid_at' => now(), 'received_by' => $by->id,
            ]);
        });

        $fee->load('payments', 'student');
        $this->notifier->notify($fee->student, 'payment',
            "تم استلام {$payment->amount} للطالب {$fee->student->name} ({$fee->title}). المتبقي: {$fee->balance}");

        return $payment;
    }
}
