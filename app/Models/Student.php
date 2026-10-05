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
        'qr_token', 'fingerprint_id', 'active', 'graduated_at',
    ];

    protected $hidden = ['qr_token'];

    protected $casts = ['active' => 'boolean', 'graduated_at' => 'date'];

    protected static function booted(): void
    {
        static::creating(function (Student $s) {
            $s->qr_token ??= Str::random(48);
        });

        // Keep the class history for the current year in step with students.school_class_id.
        static::saved(function (Student $s) {
            $year = app(\App\Support\Years::class)->current();
            if ($year && ($s->wasRecentlyCreated || $s->wasChanged('school_class_id'))) {
                Enrollment::updateOrCreate(
                    ['student_id' => $s->id, 'academic_year_id' => $year->id],
                    ['school_class_id' => $s->school_class_id]
                );
            }
        });
    }

    /** Staff see everyone; parents only their own children. */
    public function scopeVisibleTo($query, User $user)
    {
        return $user->hasRole('parent') ? $query->where('parent_id', $user->id) : $query;
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** Students enrolled in a class during a year (class history, not just the current class). */
    public function scopeEnrolledIn($query, ?int $classId, ?int $yearId)
    {
        return $query->whereHas('enrollments', fn ($e) => $e
            ->when($classId, fn ($q) => $q->where('school_class_id', $classId))
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId)));
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
