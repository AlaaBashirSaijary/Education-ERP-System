<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Never ship a known password to production; use `php artisan school:create-admin` there.
        if (app()->isProduction()) {
            $this->command?->warn('Production: skipped. Run `php artisan school:create-admin` instead.');

            return;
        }

        // Change this password immediately after first login.
        User::firstOrCreate(['email' => 'admin@school.test'], [
            'name' => 'Admin', 'password' => 'change-me-now', 'role' => 'admin',
        ]);
    }
}
