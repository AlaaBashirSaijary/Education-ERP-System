<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class AcademicYear extends Model
{
    protected $fillable = ['name', 'starts_on', 'ends_on', 'is_current'];

    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'is_current' => 'boolean'];

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class)->orderBy('starts_on');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** Two terms splitting the year at the midpoint. */
    public function createDefaultTerms(): void
    {
        $mid = $this->starts_on->copy()->addDays((int) ($this->starts_on->diffInDays($this->ends_on) / 2));
        $this->terms()->createMany([
            ['name' => 'الفصل الأول', 'starts_on' => $this->starts_on, 'ends_on' => $mid],
            ['name' => 'الفصل الثاني', 'starts_on' => $mid->copy()->addDay(), 'ends_on' => $this->ends_on],
        ]);
    }

    /**
     * Make this the current year and point every student at the class they are enrolled in for it.
     * Students without an enrolment (e.g. graduated) keep their last class.
     */
    public function makeCurrent(): void
    {
        DB::transaction(function () {
            static::where('is_current', true)->update(['is_current' => false]);
            $this->update(['is_current' => true]);

            $this->enrollments()->get(['student_id', 'school_class_id'])->each(
                fn (Enrollment $e) => Student::whereKey($e->student_id)
                    ->where('school_class_id', '!=', $e->school_class_id)
                    ->update(['school_class_id' => $e->school_class_id])
            );
        });
        app(\App\Support\Years::class)->forget();
    }

    public function isEmpty(): bool
    {
        return ! Exam::where('academic_year_id', $this->id)->exists() && ! Fee::where('academic_year_id', $this->id)->exists();
    }
}
