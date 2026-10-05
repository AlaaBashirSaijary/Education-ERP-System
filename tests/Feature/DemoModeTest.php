<?php

namespace Tests\Feature;

use App\Livewire;
use App\Models\NotificationLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire as LW;
use Tests\TestCase;

/** DatabaseMigrations (not RefreshDatabase): demo:reset runs migrate:fresh, which must not leak into other tests. */
class DemoModeTest extends TestCase
{
    use DatabaseMigrations;

    private function demoUsers(): void
    {
        foreach (['admin', 'teacher', 'accountant', 'parent'] as $role) {
            User::factory()->create(['email' => "$role@school.test", 'role' => $role, 'name' => ucfirst($role)]);
        }
    }

    public function test_everything_demo_is_off_by_default(): void
    {
        $this->demoUsers();
        config(['school.demo_mode' => false]);

        $this->get('/')->assertRedirect('/login');                       // no landing page
        $this->post('/demo/login/admin')->assertNotFound();               // no one-click login
        $this->assertGuest();

        // the wipe command refuses and nothing is erased
        $this->artisan('demo:reset')->assertFailed();
        $this->assertSame(4, User::count());

        $this->actingAs(User::where('role', 'admin')->first())->get('/dashboard')->assertOk()->assertDontSee('نسخة تجريبية');
    }

    public function test_landing_page_and_one_click_logins(): void
    {
        $this->demoUsers();
        config(['school.demo_mode' => true, 'school.contact_whatsapp' => '962791234567', 'school.demo_reset_hours' => 6]);

        $html = $this->get('/')->assertOk()->assertSee('جرّب النسخة التجريبية')->assertSee('https://wa.me/962791234567', false)->getContent();
        foreach (['admin', 'teacher', 'accountant', 'parent'] as $role) {
            $this->assertStringContainsString("/demo/login/$role", $html);
        }

        $this->post('/demo/login/teacher')->assertRedirect('/dashboard');
        $this->assertSame('teacher', auth()->user()->role);
        $this->get('/dashboard')->assertOk()->assertSee('نسخة تجريبية');   // banner explains this is a demo

        $this->post('/logout');
        $this->post('/demo/login/superuser')->assertNotFound();           // only the four fixed roles
        $this->assertGuest();
        $this->withSession(['locale' => 'en'])->get('/')->assertOk()->assertSee('Run your whole school');
    }

    public function test_shared_demo_accounts_cannot_be_locked_out_or_removed(): void
    {
        $this->demoUsers();
        config(['school.demo_mode' => true]);
        $admin = User::where('email', 'admin@school.test')->first();
        $parent = User::where('email', 'parent@school.test')->first();
        $before = $parent->password;

        LW::actingAs($parent)->test(Livewire\Profile::class)
            ->set('current_password', 'password')->set('password', 'hijacked-123')->set('password_confirmation', 'hijacked-123')
            ->call('changePassword')->assertHasErrors('current_password');
        $this->assertSame($before, $parent->fresh()->password);

        LW::actingAs($admin)->test(Livewire\Users::class)
            ->call('edit', $parent->id)->set('password', 'hijacked-123')->call('save');
        $this->assertSame($before, $parent->fresh()->password);
        LW::actingAs($admin)->test(Livewire\Users::class)->call('delete', $parent->id);
        $this->assertDatabaseHas('users', ['id' => $parent->id]);

        // visitors can still create and manage their own test users
        $own = User::factory()->create(['email' => 'visitor@example.com', 'role' => 'teacher']);
        LW::actingAs($admin)->test(Livewire\Users::class)->call('delete', $own->id);
        $this->assertDatabaseMissing('users', ['id' => $own->id]);
    }

    public function test_demo_mode_never_sends_real_messages(): void
    {
        Queue::fake();
        config(['school.demo_mode' => true, 'messaging.channel' => 'whatsapp']);
        $class = \App\Models\SchoolClass::create(['name' => 'G', 'section' => 'A']);
        $s = Student::create(['student_no' => 'D1', 'name' => 'Dina', 'school_class_id' => $class->id, 'parent_phone' => '+962790000000']);

        app(\App\Services\Messaging\ParentNotifier::class)->notify($s, 'announcement', 'hi');
        $this->assertSame('log', NotificationLog::first()->channel);

        config(['school.demo_mode' => false]);
        app(\App\Services\Messaging\ParentNotifier::class)->notify($s, 'announcement', 'again');
        $this->assertSame('whatsapp', NotificationLog::latest('id')->first()->channel);   // normal behaviour untouched
    }

    public function test_reset_wipes_visitor_data_reseeds_and_uses_random_passwords(): void
    {
        config(['school.demo_mode' => true]);
        User::factory()->create(['email' => 'visitor@example.com', 'role' => 'admin']);   // leftovers from a visitor

        $this->artisan('demo:reset')->assertSuccessful();

        $this->assertDatabaseMissing('users', ['email' => 'visitor@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'admin@school.test', 'role' => 'admin']);
        $this->assertGreaterThan(5, Student::count());
        $this->assertFalse(Hash::check('change-me-now', User::where('email', 'admin@school.test')->value('password')));
        $this->assertFalse(Hash::check('password', User::where('email', 'teacher@school.test')->value('password')));

        // --if-empty (used at boot) must not wipe a populated demo
        $marker = User::factory()->create(['email' => 'marker@example.com']);
        $this->artisan('demo:reset --if-empty')->assertSuccessful();
        $this->assertDatabaseHas('users', ['id' => $marker->id]);
    }

    public function test_seeder_refuses_to_run_in_production_unless_demo_mode(): void
    {
        config(['school.demo_mode' => false]);
        $this->app['env'] = 'production';

        $refused = false;
        try {
            (new \Database\Seeders\DemoSeeder)->setContainer($this->app)->run();
        } catch (\Throwable) {
            $refused = true;
        } finally {
            $this->app['env'] = 'testing';   // restore before teardown, which rolls migrations back
        }

        $this->assertTrue($refused);
        $this->assertSame(0, User::count());
    }
}
