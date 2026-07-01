<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoDataSeeder extends Seeder
{
    /**
     * Tables that should be wiped before re-seeding demo content.
     * FK checks are disabled during truncate, so order is informational only.
     *
     * @var list<string>
     */
    protected array $demoTables = [
        'activity_logs',
        'login_logs',
        'notifications',
        'tags',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Production guard
        if (app()->environment('production') && env('ALLOW_DEMO_SEED') !== 'true') {
            $this->command?->warn('DemoDataSeeder skipped: production environment.');

            return;
        }

        $this->truncateDemoTables();

        // 1. Users + departments (DemoUserSeeder creates departments)
        $this->call(DemoUserSeeder::class);
        $this->call(TagSeeder::class);

        // 2. Notifications + audit logs
        $this->call(NotificationSeeder::class);
        $this->call(LoginLogSeeder::class);
    }

    /**
     * Truncate tables that hold demo content (keeps users/permissions intact).
     */
    protected function truncateDemoTables(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($this->demoTables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
