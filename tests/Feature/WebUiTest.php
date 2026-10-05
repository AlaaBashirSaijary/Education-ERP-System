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
                '/timetable', '/reports/attendance', '/reports/finance', '/announcements', '/messages', '/users', '/setup', '/years', '/staff-attendance', '/profile'],
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

    /* ---------- sign-up & password reset ---------- */

    private function signup(array $over = []): array
    {
        return $over + ['name' => 'New Parent', 'email' => 'np@x.test', 'phone' => '+962 79 000 0000', 'student_no' => 'S-77',
            'password' => 'longenough1', 'password_confirmation' => 'longenough1'];
    }

    public function test_register_page_renders_in_both_languages(): void
    {
        $this->get('/register')->assertOk()->assertSee('إنشاء حساب ولي أمر');
        $this->withSession(['locale' => 'en'])->get('/register')->assertOk()->assertSee('Create a parent account');
        $this->get('/forgot-password')->assertOk();
        $this->get('/login')->assertOk()->assertSee('/register', false)->assertSee('/forgot-password', false);
    }

    public function test_parent_signs_up_and_is_linked_to_their_child(): void
    {
        $student = $this->student(['student_no' => 'S-77', 'parent_phone' => '0790000000']);   // different phone format on purpose

        $this->post('/register', $this->signup())->assertRedirect('/dashboard');

        $user = User::where('email', 'np@x.test')->first();
        $this->assertSame('parent', $user->role);                                // can never self-register as staff
        $this->assertSame($user->id, $student->fresh()->parent_id);
        $this->assertAuthenticatedAs($user);
        $this->get('/students')->assertSee('Sara');
    }

    public function test_signup_cannot_claim_a_student_with_wrong_details_or_one_already_claimed(): void
    {
        $student = $this->student(['student_no' => 'S-77', 'parent_phone' => '+962790000000']);

        // wrong phone
        $this->post('/register', $this->signup(['phone' => '+962795555555']))->assertSessionHasErrors('student_no');
        // unknown student number
        $this->post('/register', $this->signup(['student_no' => 'NOPE']))->assertSessionHasErrors('student_no');
        $this->assertDatabaseMissing('users', ['email' => 'np@x.test']);          // no half-created account
        $this->assertNull($student->fresh()->parent_id);

        // role field is ignored even if posted
        $this->post('/register', $this->signup(['role' => 'admin']))->assertRedirect('/dashboard');
        $this->assertSame('parent', User::where('email', 'np@x.test')->value('role'));

        // second family cannot take the same student
        $this->post('/logout');
        $this->post('/register', $this->signup(['email' => 'other@x.test']))->assertSessionHasErrors('student_no');
        $this->assertDatabaseMissing('users', ['email' => 'other@x.test']);
    }

    public function test_signup_validation_and_duplicate_email(): void
    {
        $this->student(['student_no' => 'S-77', 'parent_phone' => '+962790000000']);
        User::factory()->create(['email' => 'np@x.test']);
        $this->post('/register', $this->signup())->assertSessionHasErrors('email');
        $this->post('/register', $this->signup(['email' => 'ok@x.test', 'password_confirmation' => 'different']))->assertSessionHasErrors('password');
    }

    public function test_parent_can_link_another_child_from_the_profile(): void
    {
        $parent = $this->user('parent');
        $second = $this->student(['student_no' => 'S-88', 'name' => 'Second', 'parent_phone' => '+962791111111']);

        Livewire::actingAs($parent)->test(Pages\Profile::class)
            ->set('child_no', 'S-88')->set('child_phone', '000')->call('linkChild')->assertHasErrors('child_no')
            ->set('child_phone', '0791111111')->call('linkChild')->assertHasNoErrors();
        $this->assertSame($parent->id, $second->fresh()->parent_id);

        Livewire::actingAs($this->user('teacher'))->test(Pages\Profile::class)->call('linkChild')->assertForbidden();
    }

    public function test_password_reset_flow(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $user = User::factory()->create(['email' => 'forgot@x.test']);

        $this->post('/forgot-password', ['email' => 'forgot@x.test'])->assertSessionHas('status');
        $this->post('/forgot-password', ['email' => 'nobody@x.test'])->assertSessionHas('status');   // same answer: no account probing
        \Illuminate\Support\Facades\Notification::assertSentTo($user, \Illuminate\Auth\Notifications\ResetPassword::class, function ($n) use ($user) {
            $this->get('/reset-password/'.$n->token.'?email=forgot@x.test')->assertOk();
            $this->post('/reset-password', ['token' => 'bad', 'email' => 'forgot@x.test', 'password' => 'brandnew123', 'password_confirmation' => 'brandnew123'])->assertSessionHasErrors('email');
            $this->post('/reset-password', ['token' => $n->token, 'email' => 'forgot@x.test', 'password' => 'brandnew123', 'password_confirmation' => 'brandnew123'])->assertRedirect('/login');

            return true;
        });
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('brandnew123', $user->fresh()->password));
    }

    public function test_demo_logins_only_show_when_enabled_and_remember_me_works(): void
    {
        config(['school.demo_logins' => false]);
        $this->get('/login')->assertDontSee('admin@school.test');
        config(['school.demo_logins' => true]);
        $this->get('/login')->assertSee('admin@school.test');

        User::factory()->create(['email' => 'r@x.test', 'role' => 'admin']);
        $this->post('/login', ['email' => 'r@x.test', 'password' => 'password', 'remember' => '1'])->assertRedirect('/dashboard');
        $this->assertNotNull(User::where('email', 'r@x.test')->value('remember_token'));
    }

    /* ---------- staff attendance ---------- */

    public function test_admin_records_staff_attendance_and_clearing_removes_the_record(): void
    {
        $admin = $this->user('admin');
        $teacher = $this->user('teacher');
        $acc = $this->user('accountant');

        $c = Livewire::actingAs($admin)->test(Pages\StaffAttendance::class)
            ->set("rows.{$teacher->id}.status", 'late')->set("rows.{$teacher->id}.in", '08:10')->set("rows.{$teacher->id}.out", '14:00')
            ->set("rows.{$acc->id}.status", 'leave')->set("rows.{$acc->id}.in", '09:00')   // leave: times are ignored
            ->call('save')->assertHasNoErrors();

        $t = \App\Models\StaffAttendance::where('user_id', $teacher->id)->first();
        $this->assertSame(['late', '08:10:00', '14:00:00'], [$t->status, $t->check_in_at, $t->check_out_at]);
        $this->assertSame(350, $t->workedMinutes());
        $leave = \App\Models\StaffAttendance::where('user_id', $acc->id)->first();
        $this->assertNull($leave->check_in_at);
        $this->assertDatabaseMissing('staff_attendances', ['user_id' => $admin->id]);   // untouched rows are not recorded

        // checkout before checkin is rejected and nothing changes
        $c->set("rows.{$teacher->id}.out", '07:00')->call('save')->assertHasErrors("rows.{$teacher->id}.out");
        $this->assertSame('14:00:00', $t->fresh()->check_out_at);

        // clearing a row deletes the record
        $c->set("rows.{$teacher->id}.out", '14:00')->set("rows.{$teacher->id}.status", '')->call('save');
        $this->assertDatabaseMissing('staff_attendances', ['user_id' => $teacher->id]);
    }

    public function test_staff_attendance_is_admin_only_and_parents_are_not_listed(): void
    {
        $this->actingAs($this->user('teacher'))->get('/staff-attendance')->assertForbidden();
        $this->actingAs($this->user('accountant'))->get('/staff-attendance')->assertForbidden();
        $this->actingAs($this->user('parent'))->get('/reports/staff-attendance.csv?month='.today()->format('Y-m'))->assertForbidden();

        $admin = $this->user('admin');
        User::factory()->create(['role' => 'parent', 'name' => 'Some Parent']);
        $this->actingAs($admin)->get('/staff-attendance')->assertOk()->assertDontSee('Some Parent');
    }

    public function test_self_check_in_and_out_with_late_threshold_and_idempotency(): void
    {
        $teacher = $this->user('teacher');
        config(['school.staff_late_after' => '07:45']);

        $this->travelTo(now()->setTime(8, 20));
        Livewire::actingAs($teacher)->test(Pages\Dashboard::class)->call('checkIn');
        $r = \App\Models\StaffAttendance::where('user_id', $teacher->id)->first();
        $this->assertSame(['late', '08:20:00', 'self'], [$r->status, $r->check_in_at, $r->method]);

        $this->travelTo(now()->setTime(9, 0));
        Livewire::actingAs($teacher)->test(Pages\Dashboard::class)->call('checkIn');          // second tap keeps the first time
        $this->assertSame('08:20:00', $r->fresh()->check_in_at);

        $this->travelTo(now()->setTime(14, 30));
        Livewire::actingAs($teacher)->test(Pages\Dashboard::class)->call('checkOut');
        $this->assertSame('14:30:00', $r->fresh()->check_out_at);
        $this->assertDatabaseCount('staff_attendances', 1);

        // a parent has no staff attendance; checking out without checking in does nothing
        Livewire::actingAs($this->user('parent'))->test(Pages\Dashboard::class)->call('checkIn')->assertForbidden();
        $other = $this->user('accountant');
        Livewire::actingAs($other)->test(Pages\Dashboard::class)->call('checkOut');
        $this->assertDatabaseMissing('staff_attendances', ['user_id' => $other->id]);
    }

    public function test_on_time_check_in_and_leave_is_not_overridden(): void
    {
        $this->travelTo(now()->setTime(7, 30));
        $u = $this->user('accountant');
        Livewire::actingAs($u)->test(Pages\Dashboard::class)->call('checkIn');
        $this->assertSame('present', \App\Models\StaffAttendance::where('user_id', $u->id)->value('status'));

        $v = $this->user('teacher');
        \App\Models\StaffAttendance::create(['user_id' => $v->id, 'date' => today()->toDateString(), 'status' => 'leave']);
        Livewire::actingAs($v)->test(Pages\Dashboard::class)->call('checkIn');
        $this->assertSame('leave', \App\Models\StaffAttendance::where('user_id', $v->id)->value('status'));
    }

    public function test_monthly_staff_report_numbers_and_csv(): void
    {
        $t = $this->user('teacher');
        $t->update(['name' => '=HYPERLINK("x")']);
        $month = today()->format('Y-m');
        foreach ([['01', 'present', '08:00:00', '14:00:00'], ['02', 'late', '08:30:00', '14:30:00'], ['03', 'absent', null, null], ['04', 'leave', null, null]] as [$d, $st, $in, $out]) {
            \App\Models\StaffAttendance::create(['user_id' => $t->id, 'date' => "$month-$d", 'status' => $st, 'check_in_at' => $in, 'check_out_at' => $out]);
        }
        $row = app(\App\Services\ReportService::class)->staffAttendanceSummary($month)->firstWhere('name', $t->name);
        $this->assertSame([1, 1, 1, 1, 12.0, 66.7], [$row['present'], $row['late'], $row['absent'], $row['leave'], $row['hours'], $row['rate']]);   // leave not counted in the rate

        $csv = $this->actingAs($this->user('admin'))->get('/reports/staff-attendance.csv?month='.$month)->assertOk()->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);                    // formula injection neutralised
        $this->get('/staff-attendance?tab=monthly')->assertOk()->assertSee('66.7');
    }
}
