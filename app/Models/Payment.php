<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use Auditable;

    protected $fillable = ['fee_id', 'amount', 'method', 'reference', 'paid_at', 'received_by'];

    protected $casts = ['paid_at' => 'datetime', 'amount' => 'decimal:2'];

    public function fee(): BelongsTo
    {
        return $this->belongsTo(Fee::class);
    }

    public function auditLabel(): string
    {
        $fee = $this->fee()->with('student')->first();

        return trim(($fee?->student?->name ?? '').' — '.($fee?->title ?? '').' ('.$this->amount.')', ' —');
    }
}
