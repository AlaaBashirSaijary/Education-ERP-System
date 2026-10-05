<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateAdmin extends Command
{
    protected $signature = 'school:create-admin
        {email? : Defaults to $ADMIN_EMAIL}
        {--name= : Defaults to $ADMIN_NAME or "Admin"}
        {--password= : Defaults to $ADMIN_PASSWORD; prompted when omitted on a terminal}
        {--if-missing : Do nothing when an admin already exists (for container boot)}';

    protected $description = 'Create the first admin account without a hard-coded password';

    public function handle(): int
    {
        if ($this->option('if-missing') && User::where('role', 'admin')->exists()) {
            return self::SUCCESS;
        }

        // getenv() rather than env(): env() returns null once config is cached.
        $email = $this->argument('email') ?: getenv('ADMIN_EMAIL');
        $password = $this->option('password') ?: getenv('ADMIN_PASSWORD');

        if (! $email) {
            if ($this->option('if-missing')) {
                $this->warn('No admin exists and ADMIN_EMAIL/ADMIN_PASSWORD are not set; skipping.');

                return self::SUCCESS;
            }
            $email = $this->ask('Admin email');
        }
        if (! $password && $this->input->isInteractive()) {
            $password = $this->secret('Password (min 8 characters)');
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen((string) $password) < 8) {
            $this->error('A valid email and a password of at least 8 characters are required.');

            return self::FAILURE;
        }

        User::updateOrCreate(['email' => $email], [
            'name' => $this->option('name') ?: (getenv('ADMIN_NAME') ?: 'Admin'),
            'role' => 'admin', 'password' => $password,
        ]);
        $this->info("Admin {$email} is ready.");

        return self::SUCCESS;
    }
}
