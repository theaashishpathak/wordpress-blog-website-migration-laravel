<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Permissions + base settings always run first.
        $this->call(PermissionSeeder::class);
        $this->call(SettingsPermissionSeeder::class);
        $this->call(SettingsSeeder::class);

        // Multi-language foundation (Phase 1) — must run before any
        // translatable content seeders (posts, pages, categories).
        $this->call(LanguageSeeder::class);

        // AI prompt templates depend on languages being present
        // (locale codes match languages.code). Seed after LanguageSeeder.
        $this->call(AIPromptTemplateSeeder::class);

        // Demo data only outside production (or when explicitly allowed).
        $shouldSeedDemo = env('SEED_DEMO_DATA') === 'true'
            || app()->environment(['local', 'testing', 'staging', 'demo']);

        if ($shouldSeedDemo) {
            $this->call(DemoDataSeeder::class);

            // Rich NewsPilot demo — content, ads, RSS, subscribers.
            // Toggle off by setting `SEED_NEWSPILOT_DEMO=false` for
            // installs that just want the bare admin shell.
            if (env('SEED_NEWSPILOT_DEMO', 'true') !== 'false') {
                $this->call(NewsPilotDemoSeeder::class);

                // Visitor portal engagement seed — bookmarks, history,
                // follows, comments, reactions, highlights, notifications.
                // Depends on users + posts already existing.
                $this->call(VisitorPortalDemoSeeder::class);
            }

            // Final summary banner — only when demo data is in place.
            // Keeps the credentials cheatsheet out of production logs.
            $this->printDemoCheatsheet();
        }
    }

    /**
     * Print the demo-credentials cheatsheet + a few quick counts so
     * developers can find their way around a fresh install without
     * digging through the seeder source.
     */
    private function printDemoCheatsheet(): void
    {
        if (! $this->command) {
            return;
        }

        $users = \App\Models\User::query()->count();
        $posts = \App\Models\Post::query()->count();
        $comments = \App\Models\Comment::query()->count();
        $subscribers = \App\Models\NewsletterSubscriber::query()->count();

        $line = str_repeat('═', 64);
        $this->command->info('');
        $this->command->info($line);
        $this->command->info('  NewsPilot demo seed complete');
        $this->command->info($line);
        $this->command->info(sprintf(
            '  %d users  ·  %d posts  ·  %d comments  ·  %d subscribers',
            $users, $posts, $comments, $subscribers,
        ));
        $this->command->info('');
        $this->command->info('  Demo accounts (password = "password" for all):');
        $this->command->info('');
        $this->command->info('    Admin portal');
        $this->command->info('      admin@demo.com         Super Admin');
        $this->command->info('      staff@demo.com         Operations Manager (author)');
        $this->command->info('      employee@demo.com      Software Engineer  (author)');
        $this->command->info('');
        $this->command->info('    Visitor portal — three archetypes');
        $this->command->info('      visitor@demo.com       Power reader   (rich activity)');
        $this->command->info('      commuter@demo.com      Mid-tier       (moderate activity)');
        $this->command->info('      newbie@demo.com        Fresh signup   (empty states)');
        $this->command->info('');
        $this->command->info('    NewsPilot editorial team');
        $this->command->info('      superadmin@newspilot.test    Super Admin');
        $this->command->info('      admin@newspilot.test         Admin');
        $this->command->info('      editor@newspilot.test        Editor');
        $this->command->info('      jane.reporter@newspilot.test Author');
        $this->command->info('      …plus 7 more authors. See NewsPilotDemoSeeder.');
        $this->command->info('');
        $this->command->info($line);
        $this->command->info('');
    }
}
