<?php

namespace App\Services\Migration;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * BaseMigrationService
 *
 * WHY THIS EXISTS:
 * All four migration services (Users, Categories, Media, Posts) share the same
 * patterns: they need the Artisan command for output, they read from the WordPress
 * DB connection, and they write to the default NewsPilot DB connection.
 *
 * Putting that shared logic here means each service only contains the code
 * specific to what it migrates — no duplication.
 */
abstract class BaseMigrationService
{
    protected Command $command;

    /**
     * WHY TWO CONNECTIONS:
     * 'wordpress' reads from your `new` database (wp_posts, wp_users, etc.)
     * DB:: (default) writes to your `laravel` database (NewsPilot tables).
     * This lets us do: $this->wp()->table('wp_posts') to read WordPress
     * and DB::table('posts') to write to NewsPilot — in the same PHP class.
     */
    protected function wp(): \Illuminate\Database\ConnectionInterface
    {
        return DB::connection('wordpress');
    }

    public function setCommand(Command $command): static
    {
        $this->command = $command;
        return $this;
    }

    abstract public function run(): void;

    protected function info(string $msg): void
    {
        $this->command->info($msg);
    }

    protected function warn(string $msg): void
    {
        $this->command->warn($msg);
    }

    protected function error(string $msg): void
    {
        $this->command->error($msg);
    }
}
