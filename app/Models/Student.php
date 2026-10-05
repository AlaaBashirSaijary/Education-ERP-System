<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Student extends Model
{
    protected $fillable = [
        'student_no', 'name', 'school_class_id', 'parent_id', 'parent_phone',
        'qr_token', 'fingerprint_id', 'active',
    ];

    protected $hidden = ['qr_token'];

    protected $casts = ['active' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (Student $s) {
            $s->qr_token ??= Str::random(48);
        });
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function marks(): HasMany
    {
        return $this->hasMany(Mark::class);
    }

    public function fees(): HasMany
    {
        return $this->hasMany(Fee::class);
    }

    /** Phone that receives notifications: explicit number first, else the parent account's. */
    public function notifyPhone(): ?string
    {
        return $this->parent_phone ?: $this->parent?->phone;
    }
}
