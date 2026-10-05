<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['fee_id', 'amount', 'method', 'reference', 'paid_at', 'received_by'];

    protected $casts = ['paid_at' => 'datetime', 'amount' => 'decimal:2'];

    public function fee(): BelongsTo
    {
        return $this->belongsTo(Fee::class);
    }
}
