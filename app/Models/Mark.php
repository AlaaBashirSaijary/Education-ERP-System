<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mark extends Model
{
    use Auditable;

    protected $fillable = ['exam_id', 'student_id', 'mark'];

    protected $casts = ['mark' => 'float'];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function auditLabel(): string
    {
        return trim(($this->student?->name ?? '').' — '.($this->exam?->name ?? ''), ' —');
    }
}
