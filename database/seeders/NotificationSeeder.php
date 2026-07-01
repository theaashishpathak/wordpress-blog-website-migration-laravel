<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@demo.com')->first();
        $employee = User::query()->where('email', 'employee@demo.com')->first();

        if ($admin === null) {
            return;
        }

        $userIds = array_filter([$admin->id, $employee?->id]);

        DB::table('notifications')->where('notifiable_type', User::class)
            ->whereIn('notifiable_id', $userIds)
            ->delete();

        $rows = [];

        // NewsPilot welcome ping for the admin.
        $rows[] = $this->buildRow(
            User::class,
            $admin->id,
            \Illuminate\Notifications\DatabaseNotification::class,
            [
                'type' => 'system.welcome',
                'title' => 'Welcome to NewsPilot AI',
                'message' => 'Roles, demo posts, categories and AI prompt templates are pre-seeded. Open the Content menu to start exploring.',
                'icon' => 'sparkles',
                'color' => 'indigo',
                'url' => null,
            ],
            readMinutesAgo: null,
            createdMinutesAgo: 5,
        );

        if ($employee !== null) {
            $rows[] = $this->buildRow(
                User::class,
                $employee->id,
                \Illuminate\Notifications\DatabaseNotification::class,
                [
                    'type' => 'system.profile_incomplete',
                    'title' => 'Finish your profile',
                    'message' => 'Add a bio, avatar and social links so readers can find you on the public author page.',
                    'icon' => 'user-cog',
                    'color' => 'amber',
                    'url' => null,
                ],
                readMinutesAgo: null,
                createdMinutesAgo: 60,
            );
        }

        if ($rows !== []) {
            DB::table('notifications')->insert($rows);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildRow(
        string $notifiableType,
        int $notifiableId,
        string $type,
        array $data,
        ?int $readMinutesAgo,
        int $createdMinutesAgo,
    ): array {
        $createdAt = now()->subMinutes($createdMinutesAgo);
        $readAt = $readMinutesAgo === null ? null : now()->subMinutes($readMinutesAgo);

        return [
            'id' => (string) Str::uuid(),
            'type' => $type,
            'notifiable_type' => $notifiableType,
            'notifiable_id' => $notifiableId,
            'data' => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'read_at' => $readAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }
}
