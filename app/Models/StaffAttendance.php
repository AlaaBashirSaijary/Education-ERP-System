<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendance extends Model
{
    public const STATUSES = ['present', 'late', 'absent', 'leave'];

    protected $fillable = ['user_id', 'date', 'status', 'check_in_at', 'check_out_at', 'method', 'note', 'recorded_by'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Minutes between check-in and check-out, or null when either is missing. */
    public function workedMinutes(): ?int
    {
        if (! $this->check_in_at || ! $this->check_out_at) {
            return null;
        }
        $in = \Illuminate\Support\Carbon::createFromFormat('H:i:s', substr($this->check_in_at, 0, 5).':00');
        $out = \Illuminate\Support\Carbon::createFromFormat('H:i:s', substr($this->check_out_at, 0, 5).':00');

        return max(0, $in->diffInMinutes($out, false));
    }
}
