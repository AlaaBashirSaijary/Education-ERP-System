<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAcademicYear;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fee extends Model
{
    use BelongsToAcademicYear;

    protected $fillable = ['student_id', 'title', 'amount', 'due_date', 'academic_year_id'];

    protected $casts = ['due_date' => 'date', 'amount' => 'decimal:2'];

    protected $appends = ['paid', 'balance', 'status'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getPaidAttribute(): string
    {
        return number_format((float) $this->payments->sum('amount'), 2, '.', '');
    }

    public function getBalanceAttribute(): string
    {
        return number_format((float) $this->amount - (float) $this->paid, 2, '.', '');
    }

    public function getStatusAttribute(): string
    {
        if ((float) $this->balance <= 0) {
            return 'paid';
        }
        if ($this->due_date->isPast() && ! $this->due_date->isToday()) {
            return 'overdue';
        }

        return (float) $this->paid > 0 ? 'partial' : 'unpaid';
    }
}
