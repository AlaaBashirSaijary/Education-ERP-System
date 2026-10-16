<?php

namespace Tests\Feature;

use App\Livewire\AuditTrail;
use App\Livewire\Messages;
use App\Livewire\StudentImport;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\Fee;
use App\Models\Mark;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\Messaging\TwilioSmsChannel;
use App\Services\Messaging\WhatsAppCloudChannel;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'name' => 'Principal']);
    }

    private function csv(string $content, string $name = 'students.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $content);
    }

    // ---- audit trail -----------------------------------------------------

    public function test_changing_a_mark_records_who_old_and_new_value(): void
    {
        $class = SchoolClass::create(['name' => 'Grade 5', 'section' => 'A']);
        $student = Student::create(['student_no' => 'S1', 'name' => 'Sara', 'school_class_id' => $class->id]);
        $exam = Exam::create(['name' => 'Midterm', 'school_class_id' => $class->id, 'subject_id' => Subject::create(['name' => 'Maths', 'code' => 'M'])->id, 'max_mark' => 100, 'date' => today()]);
        $mark = Mark::create(['exam_id' => $exam->id, 'student_id' => $student->id, 'mark' => 60]);

        $this->assertSame(0, AuditLog::count(), 'nobody signed in → nothing logged (seeders, jobs)');

        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Huda']);
        $this->actingAs($teacher);
        $mark->update(['mark' => 95]);

        $log = AuditLog::firstOrFail();
        $this->assertSame('updated', $log->event);
        $this->assertSame('Mark', $log->auditable_type);
        $this->assertSame('Huda', $log->user_name);
        $this->assertEquals(60, $log->old_values['mark']);
        $this->assertEquals(95, $log->new_values['mark']);
        $this->assertStringContainsString('Sara', $log->label);
    }

    public function test_deleting_a_payment_is_recorded_and_secrets_never_are(): void
    {
        $admin = $this->admin();
        $class = SchoolClass::create(['name' => 'G', 'section' => null]);
        $student = Student::create(['student_no' => 'S1', 'name' => 'Sara', 'school_class_id' => $class->id]);
        $fee = Fee::create(['student_id' => $student->id, 'title' => 'Term 1', 'amount' => 500, 'due_date' => today()]);
        $this->actingAs($admin);

        $payment = Payment::create(['fee_id' => $fee->id, 'amount' => 200, 'method' => 'cash', 'paid_at' => now(), 'received_by' => $admin->id]);
        $payment->delete();
        $this->assertSame(['created', 'deleted'], AuditLog::where('auditable_type', 'Payment')->orderBy('id')->pluck('event')->all());
        $this->assertEquals(200, AuditLog::where('event', 'deleted')->first()->old_values['amount']);

        $u = User::factory()->create(['role' => 'teacher']);
        $u->update(['password' => bcrypt('new-secret'), 'name' => 'Renamed']);
        $dump = json_encode(AuditLog::where('auditable_type', 'User')->get()->toArray());
        $this->assertStringNotContainsString('password', $dump);
        $this->assertStringNotContainsString('qr_token', json_encode(AuditLog::where('auditable_type', 'Student')->get()->toArray()));
    }

    public function test_only_admin_sees_the_audit_page_and_it_renders_changes(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $class = SchoolClass::create(['name' => 'Grade 5', 'section' => 'A']);
        $s = Student::create(['student_no' => 'S1', 'name' => 'Sara', 'school_class_id' => $class->id]);
        $s->update(['name' => 'Sarah']);

        Livewire::test(AuditTrail::class)->assertSee('Sarah')->assertSee('Principal')
            ->set('type', 'Fee')->assertDontSee('Sarah');

        $this->actingAs(User::factory()->create(['role' => 'teacher']))->get('/audit')->assertForbidden();
        $this->actingAs($admin)->get('/audit')->assertOk();
    }

    // ---- student import --------------------------------------------------

    public function test_csv_import_creates_students_with_classes_phones_and_numbers(): void
    {
        $this->actingAs($this->admin());
        SchoolClass::create(['name' => 'Grade 5', 'section' => 'A']);
        $csv = "student_no,name,class,section,parent_phone\nS500,Sara Ahmad,Grade 5,A,+962791234567\n,Omar Hassan,Grade 5,A,0792345678\n";

        config(['school.country_code' => '962']);
        Livewire::test(StudentImport::class)->set('file', $this->csv($csv))->call('import')
            ->assertSet('result.created', 2)->assertSet('result.errors', 0);

        $sara = Student::where('student_no', 'S500')->firstOrFail();
        $this->assertSame('+962791234567', $sara->parent_phone);
        $this->assertNotNull($sara->qr_token);
        $this->assertSame(1, $sara->enrollments()->count(), 'class history is created too');

        $omar = Student::where('name', 'Omar Hassan')->firstOrFail();
        $this->assertMatchesRegularExpression('/^S\d+$/', $omar->student_no);
        $this->assertSame('+962792345678', $omar->parent_phone, 'local number completed with the country code');
    }

    public function test_arabic_headings_semicolons_and_windows_1256_files_are_understood(): void
    {
        $this->actingAs($this->admin());
        SchoolClass::create(['name' => 'الصف الخامس', 'section' => 'أ']);
        $utf8 = "الرقم;الاسم;الصف;الشعبة;هاتف ولي الأمر\nA1;سارة أحمد;الصف الخامس;أ;٠٧٩١٢٣٤٥٦٧\n";

        foreach ([$utf8, "\xEF\xBB\xBF".$utf8, iconv('UTF-8', 'CP1256', str_replace('٠٧٩١٢٣٤٥٦٧', '0791234567', $utf8))] as $i => $content) {
            Student::query()->delete();
            Livewire::test(StudentImport::class)->set('file', $this->csv($content))->call('import')
                ->assertSet('result.created', 1);
            $this->assertSame('سارة أحمد', Student::firstOrFail()->name, "variant $i");
            $this->assertSame('0791234567', Student::first()->parent_phone, 'Arabic digits converted');
        }
    }

    public function test_import_reports_bad_rows_and_skips_or_updates_existing(): void
    {
        $this->actingAs($this->admin());
        $class = SchoolClass::create(['name' => 'Grade 5', 'section' => 'A']);
        Student::create(['student_no' => 'S1', 'name' => 'Old name', 'school_class_id' => $class->id]);

        $csv = "student_no,name,class,section,parent_phone\n"
            ."S1,New name,Grade 5,A,\n"           // exists
            ."S2,Dup A,Grade 5,A,\nS2,Dup B,Grade 5,A,\n"   // repeated in file
            ."S3,,Grade 5,A,\n"                   // no name
            ."S4,Lost,Grade 9,Z,\n"               // unknown class
            ."S5,Bad phone,Grade 5,A,12\n"        // phone too short
            ."S6,Fine,Grade 5,A,+962791234567\n";

        $c = Livewire::test(StudentImport::class)->set('file', $this->csv($csv));
        $statuses = collect($c->viewData('a')['rows'])->pluck('status')->all();
        $this->assertSame(['skip', 'new', 'error', 'error', 'error', 'error', 'new'], $statuses);

        $c->call('import')->assertSet('result.created', 2)->assertSet('result.skipped', 1)->assertSet('result.errors', 4);
        $this->assertSame('Old name', Student::where('student_no', 'S1')->value('name'));

        Livewire::test(StudentImport::class)->set('updateExisting', true)->set('file', $this->csv("name,class,student_no\nNew name,Grade 5 A,S1\n"))->call('import')
            ->assertSet('result.updated', 1);
        $this->assertSame('New name', Student::where('student_no', 'S1')->value('name'));
    }

    public function test_missing_required_columns_and_unknown_classes_are_explained_and_classes_can_be_created(): void
    {
        $this->actingAs($this->admin());
        Livewire::test(StudentImport::class)->set('file', $this->csv("foo,bar\n1,2\n"))
            ->assertSee(__('These required columns were not found: :c', ['c' => __('Full name').'، '.__('Class')]));
        $this->assertDatabaseCount('students', 0);

        Livewire::test(StudentImport::class)->set('createClasses', true)->set('file', $this->csv("name,class,section\nNoor,Grade 7,B\n"))->call('import')
            ->assertSet('result.created', 1);
        $this->assertTrue(SchoolClass::where(['name' => 'Grade 7', 'section' => 'B'])->exists());
    }

    public function test_import_is_admin_only_and_the_template_downloads(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'teacher']))->get('/students/import')->assertForbidden();
        $this->actingAs($this->admin())->get('/students/import')->assertOk();
        $this->get('/students/import/template')->assertOk()->assertHeader('Content-Disposition', 'attachment; filename="students-template.csv"');
    }

    // ---- messaging -------------------------------------------------------

    public function test_whatsapp_request_matches_the_cloud_api_and_failures_keep_the_providers_reason(): void
    {
        config(['services.whatsapp' => ['token' => 'TKN', 'phone_number_id' => '1234'], 'school.country_code' => '962']);
        Http::fake(['graph.facebook.com/*' => Http::sequence()->push(['messages' => [['id' => 'x']]], 200)
            ->push(['error' => ['message' => 'Recipient phone number not in allowed list']], 400)]);

        (new WhatsAppCloudChannel)->send('0791234567', 'hi');
        Http::assertSent(fn ($r) => $r->url() === 'https://graph.facebook.com/v20.0/1234/messages'
            && $r->hasHeader('Authorization', 'Bearer TKN')
            && $r['messaging_product'] === 'whatsapp' && $r['to'] === '962791234567' && $r['type'] === 'text' && $r['text']['body'] === 'hi');

        try {
            (new WhatsAppCloudChannel)->send('+962791234567', 'hi');
            $this->fail('should throw');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('not in allowed list', $e->getMessage());
        }
    }

    public function test_twilio_request_and_unconfigured_or_countryless_numbers_fail_clearly(): void
    {
        config(['services.twilio' => ['sid' => 'AC1', 'token' => 'T', 'from' => '+1555'], 'school.country_code' => '']);
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM1'], 201)]);

        (new TwilioSmsChannel)->send('00962791234567', 'hello');
        Http::assertSent(fn ($r) => str_contains($r->url(), '/Accounts/AC1/Messages.json') && $r['To'] === '+962791234567' && $r['From'] === '+1555' && $r['Body'] === 'hello');

        $this->expectExceptionMessage('no country code');
        (new TwilioSmsChannel)->send('0791234567', 'hello');
    }

    public function test_channels_refuse_to_run_without_credentials(): void
    {
        config(['services.whatsapp' => ['token' => null, 'phone_number_id' => null]]);
        $this->expectExceptionMessage('WHATSAPP_TOKEN');
        (new WhatsAppCloudChannel)->send('+962791234567', 'x');
    }

    public function test_phone_normalisation(): void
    {
        config(['school.country_code' => '962']);
        $this->assertSame('+962791234567', PhoneNumber::normalize('0791234567'));
        $this->assertSame('+962791234567', PhoneNumber::normalize('+962 79 123 4567'));
        $this->assertSame('+962791234567', PhoneNumber::normalize('00962791234567'));
        $this->assertSame('+962791234567', PhoneNumber::normalize('٠٧٩١٢٣٤٥٦٧'));
        $this->assertNull(PhoneNumber::normalize('  '));
        $this->assertFalse(PhoneNumber::isValid('123'));
    }

    public function test_admin_can_send_a_test_message_and_sees_the_result(): void
    {
        $this->actingAs($this->admin());
        config(['messaging.channel' => 'whatsapp', 'services.whatsapp' => ['token' => 'T', 'phone_number_id' => '9'], 'school.country_code' => '962']);
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Token expired']], 401)]);

        Livewire::test(Messages::class)->set('testPhone', '0791234567')->call('sendTest')
            ->assertSet('testResult.ok', false)->assertSee('Token expired');

        config(['services.whatsapp.token' => null]);
        Livewire::test(Messages::class)->assertSee('WHATSAPP_TOKEN');

        config(['messaging.channel' => 'log']);
        Livewire::test(Messages::class)->set('testPhone', '0791234567')->call('sendTest')->assertSet('testResult.ok', true);

        $this->actingAs(User::factory()->create(['role' => 'teacher']))->get('/messages')->assertForbidden();
    }
}
