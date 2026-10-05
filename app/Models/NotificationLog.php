<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $fillable = ['student_id', 'phone', 'channel', 'type', 'message', 'status', 'error'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
