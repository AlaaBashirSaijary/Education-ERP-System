<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Exam;
use App\Models\Fee;
use App\Models\Mark;
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
        $this->call(DatabaseSeeder::class);
        foreach (['teacher' => 'معلم تجريبي', 'accountant' => 'محاسب تجريبي', 'parent' => 'ولي أمر تجريبي'] as $role => $name) {
            User::firstOrCreate(['email' => "$role@school.test"], ['name' => $name, 'role' => $role, 'password' => 'password', 'phone' => '+962790000001']);
        }
        $teacher = User::where('email', 'teacher@school.test')->first();
        $parent = User::where('email', 'parent@school.test')->first();

        $class = SchoolClass::firstOrCreate(['name' => 'الصف الخامس', 'section' => 'أ']);
        $math = Subject::firstOrCreate(['code' => 'MATH'], ['name' => 'الرياضيات']);
        $arabic = Subject::firstOrCreate(['code' => 'ARB'], ['name' => 'اللغة العربية']);

        foreach (['سارة أحمد', 'محمد خالد', 'ليان يوسف', 'عمر حسن'] as $i => $name) {
            $s = Student::firstOrCreate(['student_no' => 'S10'.$i], [
                'name' => $name, 'school_class_id' => $class->id, 'parent_phone' => '+96279000010'.$i,
                'parent_id' => $i === 0 ? $parent->id : null,
            ]);
            Attendance::firstOrCreate(['student_id' => $s->id, 'date' => today()->toDateString()],
                ['status' => ['present', 'late', 'absent', 'present'][$i], 'method' => 'manual']);
            foreach (range(1, 3) as $m) {
                Fee::firstOrCreate(['student_id' => $s->id, 'title' => "القسط ($m/3)"],
                    ['amount' => 300, 'due_date' => today()->addMonths($m - 2)]);
            }
        }
        $exam = Exam::firstOrCreate(['name' => 'اختبار منتصف الفصل', 'school_class_id' => $class->id, 'subject_id' => $math->id], ['max_mark' => 50, 'date' => today()]);
        Mark::firstOrCreate(['exam_id' => $exam->id, 'student_id' => Student::where('student_no', 'S100')->value('id')], ['mark' => 44]);
        TimetableEntry::firstOrCreate(['school_class_id' => $class->id, 'day_of_week' => 0, 'period' => 1],
            ['subject_id' => $arabic->id, 'teacher_id' => $teacher->id, 'starts_at' => '08:00', 'ends_at' => '08:45', 'room' => '5أ']);
    }
}
