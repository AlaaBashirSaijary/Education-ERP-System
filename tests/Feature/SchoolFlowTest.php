<?php

namespace Tests\Feature;

use App\Jobs\SendParentMessage;
use App\Models\Exam;
use App\Models\Fee;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SchoolFlowTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, array $extra = []): User
    {
        return User::factory()->create(['role' => $role] + $extra);
    }

    private function student(array $extra = []): Student
    {
        $class = SchoolClass::firstOrCreate(['name' => 'Grade 5', 'section' => 'A']);

        return Student::create($extra + [
            'student_no' => 'S'.random_int(1000, 99999), 'name' => 'Sara', 'school_class_id' => $class->id,
            'parent_phone' => '+962790000000',
        ]);
    }

    public function test_qr_scan_marks_present_notifies_parent_and_is_idempotent(): void
    {
        Queue::fake();
        $this->travelTo(now()->setTime(7, 30));
        $student = $this->student();
        $teacher = $this->user('teacher');

        $r = $this->actingAs($teacher)->postJson('/api/attendance/scan', ['qr_token' => $student->qr_token]);
        $r->assertOk()->assertJson(['status' => 'present', 'duplicate' => false]);

        $this->actingAs($teacher)->postJson('/api/attendance/scan', ['qr_token' => $student->qr_token])
            ->assertJson(['duplicate' => true]);

        $this->assertDatabaseCount('attendances', 1);
        Queue::assertPushed(SendParentMessage::class, 1);
    }

    public function test_late_scan_and_unknown_token(): void
    {
        Queue::fake();
        $this->travelTo(now()->setTime(9, 0));
        $student = $this->student();
        $teacher = $this->user('teacher');

        $this->actingAs($teacher)->postJson('/api/attendance/scan', ['qr_token' => $student->qr_token])
            ->assertJson(['status' => 'late']);
        $this->actingAs($teacher)->postJson('/api/attendance/scan', ['qr_token' => 'nope'])->assertNotFound();
    }

    public function test_close_day_marks_absent_and_notifies(): void
    {
        Queue::fake();
        $this->student();

        $this->artisan('attendance:close-day')->assertSuccessful();

        $this->assertDatabaseHas('attendances', ['status' => 'absent']);
        $this->assertDatabaseHas('notification_logs', ['type' => 'absence', 'phone' => '+962790000000']);
        Queue::assertPushed(SendParentMessage::class);
    }

    public function test_marks_report_card_and_parent_isolation(): void
    {
        Queue::fake();
        $parent = $this->user('parent');
        $other = $this->user('parent');
        $student = $this->student(['parent_id' => $parent->id]);
        $subject = Subject::create(['name' => 'Math', 'code' => 'M1']);
        $exam = Exam::create(['name' => 'Mid', 'school_class_id' => $student->school_class_id,
            'subject_id' => $subject->id, 'max_mark' => 50, 'date' => today()]);

        $this->actingAs($this->user('teacher'))
            ->postJson("/api/exams/{$exam->id}/marks", ['marks' => [['student_id' => $student->id, 'mark' => 40]]])
            ->assertNoContent();
        $this->actingAs($this->user('teacher'))
            ->postJson("/api/exams/{$exam->id}/marks", ['marks' => [['student_id' => $student->id, 'mark' => 60]]])
            ->assertUnprocessable(); // above max

        $this->actingAs($parent)->getJson("/api/students/{$student->id}/report-card")
            ->assertOk()->assertJsonPath('overall_percentage', 80);
        $this->actingAs($other)->getJson("/api/students/{$student->id}/report-card")->assertForbidden();
    }

    public function test_fee_plan_split_payment_and_overpay_guard(): void
    {
        Queue::fake();
        $student = $this->student();
        $acc = $this->user('accountant');

        $this->actingAs($acc)->postJson("/api/students/{$student->id}/fee-plan", [
            'title' => 'Tuition', 'total' => 1000, 'instalments' => 3, 'first_due_date' => today()->subMonth()->toDateString(),
        ])->assertCreated();

        $amounts = Fee::orderBy('id')->pluck('amount')->map(fn ($a) => (float) $a)->all();
        $this->assertSame([333.33, 333.33, 333.34], $amounts);

        $fee = Fee::orderBy('id')->first();
        $this->actingAs($acc)->postJson("/api/fees/{$fee->id}/payments", ['amount' => 400, 'method' => 'cash'])
            ->assertUnprocessable();
        $this->actingAs($acc)->postJson("/api/fees/{$fee->id}/payments", ['amount' => 100, 'method' => 'cash'])
            ->assertCreated();

        // Only the first instalment (due last month) is overdue; it is partially paid.
        $this->assertSame('overdue', $fee->fresh()->load('payments')->status);
        $this->actingAs($acc)->getJson('/api/fees/overdue')->assertJsonCount(1);
        $this->artisan('fees:remind')->assertSuccessful();
        $this->assertDatabaseHas('notification_logs', ['type' => 'fee_reminder']);
    }

    public function test_timetable_blocks_teacher_double_booking(): void
    {
        $admin = $this->user('admin');
        $teacher = $this->user('teacher');
        $a = SchoolClass::create(['name' => '1', 'section' => 'A']);
        $b = SchoolClass::create(['name' => '1', 'section' => 'B']);
        $s = Subject::create(['name' => 'Art', 'code' => 'A1']);
        $slot = ['subject_id' => $s->id, 'teacher_id' => $teacher->id, 'day_of_week' => 1, 'period' => 2,
            'starts_at' => '09:00', 'ends_at' => '09:45'];

        $this->actingAs($admin)->postJson('/api/timetable', $slot + ['school_class_id' => $a->id])->assertCreated();
        $this->actingAs($admin)->postJson('/api/timetable', $slot + ['school_class_id' => $b->id])->assertUnprocessable();
    }

    public function test_unauthenticated_is_rejected(): void
    {
        $this->getJson('/api/students')->assertUnauthorized();
    }

    public function test_role_guard_and_login(): void
    {
        $this->actingAs($this->user('parent'))->postJson('/api/classes', ['name' => 'x'])->assertForbidden();
        $u = $this->user('admin', ['email' => 'a@school.test']);
        $this->postJson('/api/login', ['email' => 'a@school.test', 'password' => 'password'])
            ->assertOk()->assertJsonStructure(['token']);
    }
}
