<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAcademicYear;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use BelongsToAcademicYear;

    protected $fillable = ['name', 'school_class_id', 'subject_id', 'max_mark', 'date', 'academic_year_id', 'term_id'];

    protected $casts = ['date' => 'date'];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function marks(): HasMany
    {
        return $this->hasMany(Mark::class);
    }
}
