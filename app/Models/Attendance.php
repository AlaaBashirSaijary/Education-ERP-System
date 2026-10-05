<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = ['student_id', 'date', 'status', 'method', 'checked_in_at', 'recorded_by'];

    protected $casts = ['checked_in_at' => 'datetime']; // `date` stays a plain Y-m-d string so unique lookups match

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
