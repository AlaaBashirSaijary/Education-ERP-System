<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Year-end promotion. $map is [fromClassId => toClassId | 'graduate'].
 * Safe to re-run: students already enrolled in the target year are skipped.
 */
class PromotionService
{
    public const GRADUATE = 'graduate';

    /** @return array<int,array{from:SchoolClass,to:?SchoolClass,graduate:bool,students:int,skipped:int}> */
    public function preview(AcademicYear $from, AcademicYear $to, array $map): array
    {
        $classes = SchoolClass::all()->keyBy('id');
        $rows = [];

        foreach ($map as $fromId => $target) {
            $enrolled = $this->activeEnrollments($from, (int) $fromId);
            $already = Enrollment::where('academic_year_id', $to->id)->whereIn('student_id', $enrolled->pluck('student_id'))->count();

            $rows[] = [
                'from' => $classes[$fromId],
                'to' => $target === self::GRADUATE ? null : ($classes[$target] ?? null),
                'graduate' => $target === self::GRADUATE,
                'students' => $enrolled->count(),
                'skipped' => $already,
            ];
        }

        return $rows;
    }

    /** @return array{promoted:int,graduated:int,skipped:int} */
    public function apply(AcademicYear $from, AcademicYear $to, array $map, bool $makeCurrent): array
    {
        abort_if($from->is($to), 422, 'The target year must differ from the source year.');
        $out = ['promoted' => 0, 'graduated' => 0, 'skipped' => 0];

        DB::transaction(function () use ($from, $to, $map, $makeCurrent, &$out) {
            foreach ($map as $fromId => $target) {
                foreach ($this->activeEnrollments($from, (int) $fromId) as $e) {
                    if (Enrollment::where(['student_id' => $e->student_id, 'academic_year_id' => $to->id])->exists()) {
                        $out['skipped']++;

                        continue;
                    }
                    if ($target === self::GRADUATE) {
                        Student::whereKey($e->student_id)->update(['active' => false, 'graduated_at' => $to->starts_on]);
                        $out['graduated']++;

                        continue;
                    }
                    Enrollment::create(['student_id' => $e->student_id, 'academic_year_id' => $to->id, 'school_class_id' => (int) $target]);
                    $out['promoted']++;
                }
            }
            if ($makeCurrent) {
                $to->makeCurrent();
            }
        });

        return $out;
    }

    private function activeEnrollments(AcademicYear $year, int $classId): Collection
    {
        return Enrollment::where('academic_year_id', $year->id)->where('school_class_id', $classId)
            ->whereHas('student', fn ($q) => $q->where('active', true))->get();
    }
}
