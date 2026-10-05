<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Fee;
use App\Models\Mark;
use App\Models\NotificationLog;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TimetableEntry;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Sample data for trying out the UI: `php artisan db:seed --class=DemoSeeder` (never run in production). */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        abort_if(app()->isProduction(), 1, 'DemoSeeder creates accounts with a known password; never run it in production.');
        mt_srand(42); // deterministic demo data
        $this->call(DatabaseSeeder::class);

        $users = [
            ['teacher1', 'teacher', 'أ. هدى السالم'], ['teacher2', 'teacher', 'أ. خالد المصري'], ['teacher3', 'teacher', 'أ. رنا الحداد'],
            ['accountant', 'accountant', 'المحاسب سامر'], ['parent', 'parent', 'أحمد الخطيب'],
        ];
        foreach ($users as $i => [$handle, $role, $name]) {
            User::firstOrCreate(['email' => "$handle@school.test"], ['name' => $name, 'role' => $role, 'password' => 'password', 'phone' => '+96279000'.str_pad($i, 4, '0', STR_PAD_LEFT)]);
        }
        // Short aliases used in the README.
        User::where('email', 'teacher1@school.test')->update(['email' => 'teacher@school.test']);
        $teachers = User::where('role', 'teacher')->orderBy('id')->get();
        $parent = User::where('email', 'parent@school.test')->first();

        // Fourth grade first, so class order (by id) follows grade order for the promotion wizard.
        $fourth = [
            SchoolClass::firstOrCreate(['name' => 'الصف الرابع', 'section' => 'أ']),
            SchoolClass::firstOrCreate(['name' => 'الصف الرابع', 'section' => 'ب']),
        ];
        $classes = [
            SchoolClass::firstOrCreate(['name' => 'الصف الخامس', 'section' => 'أ']),
            SchoolClass::firstOrCreate(['name' => 'الصف الخامس', 'section' => 'ب']),
        ];
        $subjects = collect([['MATH', 'الرياضيات'], ['ARB', 'اللغة العربية'], ['ENG', 'اللغة الإنجليزية'], ['SCI', 'العلوم']])
            ->map(fn ($s) => Subject::firstOrCreate(['code' => $s[0]], ['name' => $s[1]]));

        $names = ['سارة أحمد', 'محمد خالد', 'ليان يوسف', 'عمر حسن', 'جود سامر', 'يزن فادي', 'تالا نبيل', 'آدم رامي', 'ريم عادل', 'كرم وليد', 'هنا باسل', 'زيد مازن'];
        $students = collect($names)->map(fn ($name, $i) => Student::firstOrCreate(['student_no' => 'S'.(1000 + $i)], [
            'name' => $name, 'school_class_id' => $classes[$i % 2]->id, 'fingerprint_id' => (string) (1000 + $i),
            'parent_phone' => '+9627900'.(2000 + intdiv($i, 2) * 3), 'parent_id' => $i === 0 ? $parent->id : null,
        ]));

        // Last year (2025/2026): the same students were in fourth grade, with marks and fully paid fees.
        $current = AcademicYear::where('is_current', true)->first();
        $past = AcademicYear::firstOrCreate(['name' => ($current->starts_on->year - 1).'/'.$current->starts_on->year], [
            'starts_on' => ($current->starts_on->year - 1).'-09-01', 'ends_on' => $current->starts_on->year.'-06-30', 'is_current' => false,
        ]);
        if (! $past->terms()->exists()) {
            $past->createDefaultTerms();
        }
        foreach ($students as $i => $s) {
            Enrollment::firstOrCreate(['student_id' => $s->id, 'academic_year_id' => $past->id], ['school_class_id' => $fourth[$i % 2]->id]);
            foreach (range(1, 4) as $k) {
                $due = $past->starts_on->copy()->addMonths($k - 1)->addDays(9);
                $fee = Fee::firstOrCreate(['student_id' => $s->id, 'academic_year_id' => $past->id, 'title' => 'القسط الدراسي ('.$k.'/4)'], ['amount' => 230, 'due_date' => $due]);
                if (! $fee->payments()->exists()) {
                    Payment::create(['fee_id' => $fee->id, 'amount' => 230, 'method' => 'cash', 'paid_at' => $due->copy()->subDays(2)->setTime(10, 0), 'received_by' => User::where('role', 'accountant')->value('id')]);
                }
            }
        }
        foreach ($fourth as $class) {
            foreach ($subjects->take(3) as $subject) {
                $exam = Exam::firstOrCreate(['name' => 'اختبار نهاية العام', 'school_class_id' => $class->id, 'subject_id' => $subject->id, 'academic_year_id' => $past->id],
                    ['max_mark' => 50, 'date' => $past->ends_on->copy()->subDays(20), 'term_id' => $past->terms()->orderByDesc('starts_on')->value('id')]);
                foreach (Enrollment::where('academic_year_id', $past->id)->where('school_class_id', $class->id)->pluck('student_id') as $sid) {
                    Mark::firstOrCreate(['exam_id' => $exam->id, 'student_id' => $sid], ['mark' => mt_rand(28, 50)]);
                }
            }
        }

        // Attendance for the last 7 days (skip Fri/Sat); today is partial so the gate has work left.
        foreach ($students as $s) {
            foreach (range(0, 6) as $back) {
                $day = today()->subDays($back);
                if (in_array($day->dayOfWeek, [5, 6], true) || ($back === 0 && $s->id % 4 === 0)) {
                    continue;
                }
                $r = mt_rand(1, 100);
                Attendance::firstOrCreate(['student_id' => $s->id, 'date' => $day->toDateString()], [
                    'status' => $r <= 82 ? 'present' : ($r <= 92 ? 'late' : 'absent'), 'method' => $r % 2 ? 'qr' : 'manual',
                    'checked_in_at' => $day->copy()->setTime(7, mt_rand(20, 59)),
                ]);
            }
        }

        // Tuition: 4 monthly instalments starting 3 months ago; earlier ones mostly paid.
        foreach ($students as $s) {
            foreach (range(0, 3) as $k) {
                $due = today()->startOfMonth()->subMonths(3 - $k)->addDays(9);
                $fee = Fee::firstOrCreate(['student_id' => $s->id, 'academic_year_id' => $current->id, 'title' => 'القسط الدراسي ('.($k + 1).'/4)'], ['amount' => 250, 'due_date' => $due]);
                $pays = $due->isPast() && mt_rand(1, 100) <= ($k < 2 ? 90 : 55);
                if ($pays && ! $fee->payments()->exists()) {
                    Payment::create(['fee_id' => $fee->id, 'amount' => mt_rand(1, 100) <= 15 ? 150 : 250, 'method' => ['cash', 'card', 'transfer'][mt_rand(0, 2)],
                        'paid_at' => $due->copy()->subDays(mt_rand(0, 6))->setTime(10, 15), 'received_by' => User::where('role', 'accountant')->value('id')]);
                }
            }
        }

        // Exams and marks.
        foreach ($classes as $class) {
            foreach ($subjects->take(3) as $subject) {
                $exam = Exam::firstOrCreate(['name' => 'اختبار منتصف الفصل', 'school_class_id' => $class->id, 'subject_id' => $subject->id],
                    ['max_mark' => 50, 'date' => today()->subDays(14), 'academic_year_id' => $current->id, 'term_id' => $current->terms()->value('id')]);
                foreach ($students->where('school_class_id', $class->id) as $s) {
                    Mark::firstOrCreate(['exam_id' => $exam->id, 'student_id' => $s->id], ['mark' => mt_rand(26, 50)]);
                }
            }
        }

        // Timetable: class B is offset by one teacher so nobody is double-booked.
        foreach ($classes as $ci => $class) {
            foreach (range(0, 4) as $day) {
                foreach (range(1, 4) as $p) {
                    TimetableEntry::firstOrCreate(['school_class_id' => $class->id, 'day_of_week' => $day, 'period' => $p], [
                        'subject_id' => $subjects[($day + $p + $ci) % 4]->id, 'teacher_id' => $teachers[($day + $p + $ci) % 3]->id,
                        'starts_at' => sprintf('%02d:%02d', 8 + intdiv(($p - 1) * 50, 60), (($p - 1) * 50) % 60),
                        'ends_at' => sprintf('%02d:%02d', 8 + intdiv($p * 50 - 5, 60), ($p * 50 - 5) % 60), 'room' => 'قاعة '.($ci + 1).$p,
                    ]);
                }
            }
        }

        if (! NotificationLog::exists()) {
            foreach ($students->take(6) as $i => $s) {
                NotificationLog::create(['student_id' => $s->id, 'phone' => $s->parent_phone, 'channel' => 'whatsapp',
                    'type' => ['attendance', 'absence', 'payment', 'fee_reminder', 'attendance', 'report_card'][$i],
                    'message' => ['وصل '.$s->name.' إلى المدرسة الساعة 07:42.', 'نفيدكم بأن الطالب/ة '.$s->name.' غائب/ة اليوم.', 'تم استلام 250 للطالب '.$s->name.'.',
                        'تذكير: القسط الدراسي (3/4) للطالب '.$s->name.' متأخر.', 'وصل '.$s->name.' إلى المدرسة الساعة 07:51.', 'تقرير '.$s->name.' – المعدل العام: 87%'][$i],
                    'status' => $i === 3 ? 'failed' : 'sent', 'error' => $i === 3 ? 'Invalid number' : null]);
            }
        }
    }
}
