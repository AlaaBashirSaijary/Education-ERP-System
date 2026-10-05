<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $fillable = ['student_id', 'phone', 'channel', 'type', 'message', 'status', 'error'];
}
