<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableEntry extends Model
{
    use Auditable;

    protected $fillable = [
        'school_class_id', 'subject_id', 'teacher_id', 'day_of_week', 'period',
        'starts_at', 'ends_at', 'room',
    ];

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function auditLabel(): string
    {
        return trim(($this->schoolClass?->name ?? '').' '.($this->subject?->name ?? '').' #'.$this->period);
    }
}
