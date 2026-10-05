<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Lets a parent claim a child with two facts only the family and the school know:
 * the student number and the phone number the school has on file. A student can be claimed once.
 */
class GuardianLinkService
{
    public function digits(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone);
    }

    /** Compare the last 8 digits so "+962 79 123 4567", "0791234567" and "962791234567" all match. */
    public function phonesMatch(?string $a, ?string $b): bool
    {
        $a = $this->digits($a);
        $b = $this->digits($b);

        return strlen($a) >= 8 && strlen($b) >= 8 && substr($a, -8) === substr($b, -8);
    }

    /** True when the student exists, is active, has no parent account yet and the phone matches. */
    public function canClaim(string $studentNo, string $phone): bool
    {
        $s = Student::where('student_no', trim($studentNo))->first();

        return $s && $s->active && $s->parent_id === null && $this->phonesMatch($s->parent_phone, $phone);
    }

    /** Atomically attach the student to the user; false when the details do not match. */
    public function claim(User $user, string $studentNo, string $phone): bool
    {
        return DB::transaction(function () use ($user, $studentNo, $phone) {
            $s = Student::where('student_no', trim($studentNo))->lockForUpdate()->first();
            if (! $s || ! $s->active || $s->parent_id !== null || ! $this->phonesMatch($s->parent_phone, $phone)) {
                return false;
            }
            $s->update(['parent_id' => $user->id]);

            return true;
        });
    }
}
