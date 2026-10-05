<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

/** Wipes the whole database and re-seeds sample data, so a public trial never fills up with visitors' leftovers. */
class ResetDemo extends Command
{
    protected $signature = 'demo:reset {--if-empty : Only seed when there are no users yet (used at boot)}';

    protected $description = 'DEMO MODE ONLY: wipe all data and load fresh sample data';

    public function handle(): int
    {
        // The one safeguard that protects a real school: this command is inert unless demo mode is switched on explicitly.
        if (! config('school.demo_mode')) {
            $this->error('Refusing to run: SCHOOL_DEMO_MODE is not enabled. This command erases ALL data.');

            return self::FAILURE;
        }

        if ($this->option('if-empty') && $this->tableExists() && User::exists()) {
            $this->info('Demo data already present; nothing to do.');

            return self::SUCCESS;
        }

        $this->call('migrate:fresh', ['--force' => true]);
        $this->call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);
        $this->info('Demo data reset at '.now()->toDateTimeString().'.');

        return self::SUCCESS;
    }

    private function tableExists(): bool
    {
        return \Illuminate\Support\Facades\Schema::hasTable('users');
    }
}
