<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    protected $fillable = ['academic_year_id', 'name', 'starts_on', 'ends_on'];

    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date'];
}
