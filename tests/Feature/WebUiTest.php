<?php

namespace Tests\Feature;

use App\Livewire as Pages;
use App\Models\Attendance;
use App\Models\Fee;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class WebUiTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function student(array $extra = []): Student
    {
        $class = SchoolClass::firstOrCreate(['name' => 'Grade 5', 'section' => 'A']);

        return Student::create($extra + ['student_no' => 'S'.random_int(1000, 99999), 'name' => 'Sara',
            'school_class_id' => $class->id, 'parent_phone' => '+962790000000']);
    }

    public function test_guest_is_redirected_and_login_works(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('dir="rtl"', false)->assertSee('تسجيل الدخول');

        User::factory()->create(['email' => 'a@s.test', 'role' => 'admin']);
        $this->post('/login', ['email' => 'a@s.test', 'password' => 'password'])->assertRedirect('/dashboard');
        $this->post('/logout')->assertRedirect('/login');
        $this->post('/login', ['email' => 'a@s.test', 'password' => 'bad'])->assertSessionHasErrors('email');
    }

    public function test_language_toggle_switches_direction_and_text(): void
    {
        $this->actingAs($this->user('admin'));
        $this->get('/dashboard')->assertSee('dir="rtl"', false)->assertSee('لوحة التحكم');

        $this->get('/lang/en')->assertRedirect();
        $this->get('/dashboard')->assertSee('dir="ltr"', false)->assertSee('Dashboard')->assertSee('العربية');

        $this->get('/lang/fr')->assertNotFound();
    }

    public function test_role_access(): void
    {
        $this->actingAs($this->user('parent'));
        $this->get('/attendance')->assertForbidden();
        $this->get('/gate')->assertForbidden();
        $this->get('/messages')->assertForbidden();
        $this->get('/fees')->assertOk();

        $this->actingAs($this->user('teacher'));
        $this->get('/fees')->assertForbidden();
        $this->get('/attendance')->assertOk();
    }

    public function test_gate_scan_via_livewire(): void
    {
        Queue::fake();
        $student = $this->student();

        Livewire::actingAs($this->user('teacher'))->test(Pages\Gate::class)
            ->call('scan', $student->qr_token)->assertSee('Sara')->assertSet('error', null)
            ->call('scan', 'bogus-token')->assertSet('error', 'طالب غير معروف.');

        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_manual_attendance_saves_and_notifies_for_absent(): void
    {
        Queue::fake();
        $student = $this->student();

        Livewire::actingAs($this->user('teacher'))->test(Pages\Attendance::class)
            ->set("statuses.{$student->id}", 'absent')->call('save');

        $this->assertSame('absent', Attendance::first()->status);
        $this->assertDatabaseHas('notification_logs', ['type' => 'absence']);
    }

    public function test_parent_only_sees_own_children_everywhere(): void
    {
        $mine = $this->user('parent');
        $mineStudent = $this->student(['parent_id' => $mine->id, 'name' => 'Mine']);
        $other = $this->student(['name' => 'Stranger']);

        $this->actingAs($mine)->get('/students')->assertSee('Mine')->assertDontSee('Stranger');
        $this->get("/students/{$other->id}/report-card")->assertForbidden();
        $this->get("/students/{$mineStudent->id}/report-card")->assertOk();
        $this->get('/students/'.$other->id.'/card')->assertForbidden();
        $this->get('/fees?studentId='.$other->id)->assertForbidden();
    }

    public function test_fee_plan_and_payment_via_livewire(): void
    {
        Queue::fake();
        $student = $this->student();
        $acc = $this->user('accountant');

        $c = Livewire::actingAs($acc)->test(Pages\Fees::class)
            ->set('studentId', $student->id)->set('title', 'Tuition')->set('total', '1000')
            ->set('instalments', 3)->call('createPlan');
        $this->assertSame([333.33, 333.33, 333.34], Fee::orderBy('id')->pluck('amount')->map(fn ($a) => (float) $a)->all());

        $fee = Fee::first();
        $c->call('startPay', $fee->id)->set('payAmount', '999')->call('pay')->assertHasErrors('payAmount');
        $c->set('payAmount', '100')->call('pay')->assertHasNoErrors();
        $this->assertDatabaseHas('payments', ['fee_id' => $fee->id, 'amount' => 100]);
    }

    public function test_timetable_conflict_message(): void
    {
        $admin = $this->user('admin');
        $teacher = $this->user('teacher');
        $a = SchoolClass::create(['name' => '1', 'section' => 'A']);
        $b = SchoolClass::create(['name' => '1', 'section' => 'B']);
        $subject = \App\Models\Subject::create(['name' => 'Art', 'code' => 'A1']);

        $c = Livewire::actingAs($admin)->test(Pages\Timetable::class)
            ->set('classId', $a->id)->set('subjectId', $subject->id)->set('teacherId', $teacher->id)
            ->set('day', 1)->set('period', 2)->call('add')->assertHasNoErrors();
        $c->set('classId', $b->id)->set('period', 2)->call('add')->assertHasErrors('teacherId');
    }

    public function test_admin_manages_users_and_cannot_lock_self_out(): void
    {
        $admin = $this->user('admin');
        $c = Livewire::actingAs($admin)->test(Pages\Users::class)
            ->set('name', 'Teach')->set('email', 't@x.test')->set('role', 'teacher')->set('password', 'short')->call('save')
            ->assertHasErrors('password');
        $c->set('password', 'longenough1')->call('save')->assertHasNoErrors();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('longenough1', User::where('email', 't@x.test')->value('password')));

        $c->call('edit', $admin->id)->set('role', 'teacher')->call('save')->assertHasErrors('role');
        $c->call('delete', $admin->id);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);

        $this->actingAs($this->user('teacher'))->get('/users')->assertForbidden();
    }

    public function test_profile_password_change_requires_current_password(): void
    {
        $u = $this->user('parent');
        $c = Livewire::actingAs($u)->test(Pages\Profile::class)
            ->set('current_password', 'wrong')->set('password', 'newpassword1')->set('password_confirmation', 'newpassword1')
            ->call('changePassword')->assertHasErrors('current_password');
        $c->set('current_password', 'password')->call('changePassword')->assertHasNoErrors();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpassword1', $u->fresh()->password));
    }

    public function test_student_edit_deactivate_delete_and_setup_guards(): void
    {
        $student = $this->student();
        $admin = $this->user('admin');

        Livewire::actingAs($admin)->test(Pages\Students::class)
            ->call('edit', $student->id)->set('name', 'Sara Renamed')->call('save')->assertHasNoErrors();
        $this->assertSame('Sara Renamed', $student->fresh()->name);

        Livewire::actingAs($admin)->test(Pages\Students::class)->call('toggleActive', $student->id);
        $this->assertFalse($student->fresh()->active);

        // A class that still has students cannot be deleted (it would cascade).
        Livewire::actingAs($admin)->test(Pages\Setup::class)->call('deleteClass', $student->school_class_id);
        $this->assertDatabaseHas('school_classes', ['id' => $student->school_class_id]);

        Livewire::actingAs($admin)->test(Pages\Students::class)->call('delete', $student->id);
        $this->assertDatabaseMissing('students', ['id' => $student->id]);

        // Non-admins cannot mutate.
        $s2 = $this->student(['student_no' => 'X1']);
        Livewire::actingAs($this->user('teacher'))->test(Pages\Students::class)->call('delete', $s2->id)->assertForbidden();
    }

    public function test_reports_csv_and_announcement_dedupes_siblings(): void
    {
        Queue::fake();
        $a = $this->student(['name' => '=cmd|evil']);
        $b = $this->student(['name' => 'Brother']); // same parent phone as $a
        Attendance::create(['student_id' => $a->id, 'date' => today()->toDateString(), 'status' => 'present']);

        $csv = $this->actingAs($this->user('teacher'))
            ->get('/reports/attendance.csv?month='.today()->format('Y-m'))->assertOk()->streamedContent();
        $this->assertStringContainsString("'=cmd|evil", $csv); // formula injection neutralised
        $this->get('/reports/finance.csv')->assertForbidden();

        Livewire::actingAs($this->user('admin'))->test(Pages\Announcements::class)
            ->set('message', 'Holiday tomorrow')->call('send');
        $this->assertDatabaseCount('notification_logs', 1); // two siblings, one phone, one message
    }

    public function test_finance_report_and_receipt_access(): void
    {
        Queue::fake();
        $parent = $this->user('parent');
        $student = $this->student(['parent_id' => $parent->id]);
        $fee = Fee::create(['student_id' => $student->id, 'title' => 'T', 'amount' => 100, 'due_date' => today()->subDay()]);
        $acc = $this->user('accountant');
        $payment = \App\Models\Payment::create(['fee_id' => $fee->id, 'amount' => 40, 'method' => 'cash', 'paid_at' => now(), 'received_by' => $acc->id]);

        $this->actingAs($acc)->get('/reports/finance')->assertOk()->assertSeeInOrder(['100.00', '40.00', '60.00']);
        $this->get("/payments/{$payment->id}/receipt")->assertOk()->assertSee('R-'.str_pad($payment->id, 6, '0', STR_PAD_LEFT));
        $this->actingAs($parent)->get("/payments/{$payment->id}/receipt")->assertOk();
        $this->actingAs($this->user('parent'))->get("/payments/{$payment->id}/receipt")->assertForbidden();
        $this->actingAs($this->user('teacher'))->get("/payments/{$payment->id}/receipt")->assertForbidden();
        $this->actingAs($parent)->get('/reports/finance')->assertForbidden();
    }

    public function test_dashboard_renders_charts_for_staff(): void
    {
        $this->actingAs($this->user('admin'))->get('/dashboard')->assertOk()->assertSee('الحضور آخر 7 أيام');
    }

    /** Every page must render for every role that may open it (this caught a broken Messages page). */
    public function test_every_page_renders_for_each_allowed_role(): void
    {
        Queue::fake();
        $parent = $this->user('parent');
        $student = $this->student(['parent_id' => $parent->id]);
        \App\Models\NotificationLog::create(['student_id' => $student->id, 'phone' => '+1', 'channel' => 'log', 'type' => 'absence', 'message' => 'm']);
        Fee::create(['student_id' => $student->id, 'title' => 'T', 'amount' => 10, 'due_date' => today()->subDay()]);

        $pages = [
            'admin' => ['/dashboard', '/students', "/students/{$student->id}", "/students/{$student->id}/report-card", '/attendance', '/gate', '/grades', '/fees',
                '/timetable', '/reports/attendance', '/reports/finance', '/announcements', '/messages', '/users', '/setup', '/years', '/profile'],
            'teacher' => ['/dashboard', '/students', "/students/{$student->id}", '/attendance', '/gate', '/grades', '/timetable', '/reports/attendance', '/announcements', '/profile'],
            'accountant' => ['/dashboard', '/students', '/fees', '/reports/finance', '/profile'],
            'parent' => ['/dashboard', '/students', "/students/{$student->id}", "/students/{$student->id}/report-card", '/fees', '/timetable', '/profile'],
        ];
        foreach (['ar', 'en'] as $lang) {
            foreach ($pages as $role => $urls) {
                $user = $role === 'parent' ? $parent : $this->user($role);
                $this->actingAs($user)->withSession(['locale' => $lang]);
                foreach ($urls as $url) {
                    $this->get($url)->assertOk();
                }
            }
        }
    }

    public function test_year_switch_filters_marks_and_fees_and_new_rows_follow_the_viewed_year(): void
    {
        Queue::fake();
        $current = \App\Models\AcademicYear::where('is_current', true)->first();
        $past = \App\Models\AcademicYear::create(['name' => '2020/2021', 'starts_on' => '2020-09-01', 'ends_on' => '2021-06-30']);
        $student = $this->student();
        Fee::create(['student_id' => $student->id, 'title' => 'Old fee', 'amount' => 50, 'due_date' => '2021-01-01', 'academic_year_id' => $past->id]);
        Fee::create(['student_id' => $student->id, 'title' => 'New fee', 'amount' => 70, 'due_date' => today()->addMonth()]);
        $acc = $this->user('accountant');

        $this->actingAs($acc)->get('/fees?studentId='.$student->id)->assertSee('New fee')->assertDontSee('Old fee');
        $this->post("/year/{$past->id}")->assertRedirect();
        $this->get('/fees?studentId='.$student->id)->assertSee('Old fee')->assertDontSee('New fee')->assertSee('2020/2021');

        // New rows created while viewing the past year belong to it.
        $fee = Fee::create(['student_id' => $student->id, 'title' => 'Late entry', 'amount' => 5, 'due_date' => '2021-02-01']);
        $this->assertSame($past->id, $fee->academic_year_id);
        $this->assertNotSame($current->id, $fee->academic_year_id);
    }

    public function test_promotion_moves_students_records_history_and_is_idempotent(): void
    {
        $five = SchoolClass::create(['name' => 'Grade 5', 'section' => 'A']);
        $six = SchoolClass::create(['name' => 'Grade 6', 'section' => 'A']);
        $stay = $this->student(['name' => 'Mover', 'school_class_id' => $five->id]);
        $grad = $this->student(['name' => 'Senior', 'school_class_id' => $six->id]);
        $old = \App\Models\AcademicYear::where('is_current', true)->first();
        $new = \App\Models\AcademicYear::create(['name' => '2099/2100', 'starts_on' => '2099-09-01', 'ends_on' => '2100-06-30']);

        $service = app(\App\Services\PromotionService::class);
        $map = [$five->id => (string) $six->id, $six->id => 'graduate'];
        $preview = $service->preview($old, $new, $map);
        $this->assertSame([1, 1], array_column($preview, 'students'));

        $r = $service->apply($old, $new, $map, true);
        $this->assertSame(['promoted' => 1, 'graduated' => 1, 'skipped' => 0], $r);
        $this->assertSame($six->id, $stay->fresh()->school_class_id);          // moved up and year is now current
        $this->assertTrue($new->fresh()->is_current);
        $this->assertFalse($grad->fresh()->active);                              // graduated
        $this->assertNotNull($grad->fresh()->graduated_at);
        $this->assertSame($five->id, $stay->enrollments()->where('academic_year_id', $old->id)->value('school_class_id')); // history kept

        $again = $service->apply($old, $new, $map, false);                       // re-run is safe
        $this->assertSame(0, $again['promoted']);
    }

    public function test_years_page_is_admin_only_and_guards_deleting_data(): void
    {
        $this->actingAs($this->user('teacher'))->get('/years')->assertForbidden();
        $admin = $this->user('admin');
        $current = \App\Models\AcademicYear::where('is_current', true)->first();

        Livewire::actingAs($admin)->test(Pages\Years::class)->call('delete', $current->id);
        $this->assertDatabaseHas('academic_years', ['id' => $current->id]);

        $c = Livewire::actingAs($admin)->test(Pages\Years::class)->call('create')
            ->set('name', '2031/2032')->set('starts_on', '2031-09-01')->set('ends_on', '2032-06-30')->call('save')->assertHasNoErrors();
        $created = \App\Models\AcademicYear::where('name', '2031/2032')->first();
        $this->assertCount(2, $created->terms);
        $c->call('makeCurrentYear', $created->id);
        $this->assertTrue($created->fresh()->is_current);
        $this->assertFalse($current->fresh()->is_current);
    }
}
