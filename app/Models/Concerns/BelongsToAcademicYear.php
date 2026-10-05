<?php

namespace App\Models\Concerns;

use App\Models\AcademicYear;
use App\Support\Years;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** New rows default to the year being viewed (the current year when there is no session, e.g. CLI/jobs). */
trait BelongsToAcademicYear
{
    public static function bootBelongsToAcademicYear(): void
    {
        static::creating(function ($model) {
            $model->academic_year_id ??= app(Years::class)->selected()?->id;
        });
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
