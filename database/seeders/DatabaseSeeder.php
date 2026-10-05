<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Change this password immediately after first login.
        User::firstOrCreate(['email' => 'admin@school.test'], [
            'name' => 'Admin', 'password' => 'change-me-now', 'role' => 'admin',
        ]);
    }
}
