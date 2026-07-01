<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Demo user fixtures — staff portal accounts + named visitor archetypes.
 *
 * Every named account is idempotent (updateOrCreate keyed on email).
 * Factory-generated extras are guarded so re-running the seeder doesn't
 * pile up duplicate authors and visitors.
 *
 * Named accounts seeded (password = "password" for all):
 *   - admin@demo.com           Super Admin
 *   - staff@demo.com           Operations Manager (author portal)
 *   - employee@demo.com        Software Engineer  (author portal)
 *   - visitor@demo.com         Power-reader visitor (rich activity)
 *   - newbie@demo.com          Fresh visitor      (no activity yet)
 *   - commuter@demo.com        Mid-tier visitor   (moderate activity)
 *
 * The "newbie" + "commuter" archetypes give the visitor portal a mix
 * of empty + populated states so screenshots cover both extremes.
 */
class DemoUserSeeder extends Seeder
{
    /**
     * The number of factory-generated author employees to maintain.
     * Re-running the seeder tops up only if the current count is lower
     * than this — keeps re-seeds idempotent and fast.
     */
    private const FACTORY_AUTHOR_TARGET = 8;

    /**
     * The number of factory-generated visitor accounts to maintain
     * (in addition to the 3 named visitor archetypes above).
     */
    private const FACTORY_VISITOR_TARGET = 15;

    public function run(): void
    {
        // Ensure base roles exist.
        $adminRole = Role::query()->firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $managerRole = Role::query()->firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $employeeRole = Role::query()->firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);

        // Sample departments.
        $hrDept = Department::query()->updateOrCreate(
            ['name' => 'Human Resources'],
            ['description' => 'HR and people operations'],
        );
        $engDept = Department::query()->updateOrCreate(
            ['name' => 'Engineering'],
            ['description' => 'Software engineering team'],
        );

        // ───── Named staff accounts ─────────────────────────────────
        $admin = $this->upsertNamedUser(
            email: 'admin@demo.com',
            attributes: [
                'name' => 'Demo Admin',
                'phone' => '+1-555-0100',
                'mobile' => '+1-555-0100',
                'gender' => 'male',
                'date_of_birth' => '1990-01-01',
                'portal_type' => 'admin',
                'employee_id' => 'EMP-0001',
                'job_title' => 'Administrator',
                'department_id' => $hrDept->id,
                'hire_date' => '2024-01-01',
                'employment_type' => 'full_time',
            ],
        );
        $admin->syncRoles([$adminRole->name]);

        $manager = $this->upsertNamedUser(
            email: 'staff@demo.com',
            attributes: [
                'name' => 'Demo Staff',
                'phone' => '+1-555-0101',
                'mobile' => '+1-555-0101',
                'gender' => 'female',
                'date_of_birth' => '1988-05-15',
                'portal_type' => 'author',
                'employee_id' => 'EMP-0002',
                'job_title' => 'Operations Manager',
                'department_id' => $engDept->id,
                'hire_date' => '2024-02-01',
                'employment_type' => 'full_time',
            ],
        );
        $manager->syncRoles([$managerRole->name]);

        $employee = $this->upsertNamedUser(
            email: 'employee@demo.com',
            attributes: [
                'name' => 'Demo Employee',
                'phone' => '+1-555-0102',
                'mobile' => '+1-555-0102',
                'gender' => 'other',
                'date_of_birth' => '1995-09-20',
                'portal_type' => 'author',
                'employee_id' => 'EMP-0003',
                'job_title' => 'Software Engineer',
                'department_id' => $engDept->id,
                'manager_id' => $manager->id,
                'hire_date' => '2024-06-01',
                'employment_type' => 'full_time',
            ],
        );
        $employee->syncRoles([$employeeRole->name]);

        // ───── Factory authors — top up to target if below ─────────
        $existingAuthors = User::query()
            ->where('portal_type', 'author')
            ->whereNotIn('email', ['staff@demo.com', 'employee@demo.com'])
            ->count();
        $needAuthors = max(0, self::FACTORY_AUTHOR_TARGET - $existingAuthors);
        if ($needAuthors > 0) {
            User::factory()->count($needAuthors)->create([
                'portal_type' => 'author',
                'status' => 'active',
                'department_id' => $engDept->id,
                'manager_id' => $manager->id,
                'employment_type' => 'full_time',
            ])->each(function (User $user) use ($employeeRole): void {
                $user->syncRoles([$employeeRole->name]);
            });
        }

        // ───── Named visitor archetypes ────────────────────────────
        // 1. The "power reader" — rich engagement seeded by
        //    VisitorPortalDemoSeeder. Lands on a busy library.
        $this->upsertNamedUser(
            email: 'visitor@demo.com',
            attributes: [
                'name' => 'Demo Visitor',
                'phone' => '+1-555-0200',
                'gender' => 'other',
                'date_of_birth' => '1996-03-12',
                'portal_type' => 'visitor',
                'bio' => 'Curious about everything between politics and physics. Saves more than I finish.',
                'employment_type' => null,
            ],
        );

        // 2. The "fresh signup" — has just registered, lands on
        //    every empty state. Critical for empty-state screenshots.
        $this->upsertNamedUser(
            email: 'newbie@demo.com',
            attributes: [
                'name' => 'Quinn Park',
                'phone' => null,
                'gender' => 'other',
                'date_of_birth' => '2001-08-04',
                'portal_type' => 'visitor',
                'bio' => null,
                'employment_type' => null,
                // Recent created_at so the "Reading since" copy reads
                // as a couple of days, not months ago.
                'created_at' => now()->subDays(2),
            ],
        );

        // 3. Mid-tier reader — some activity, not power-user busy.
        $this->upsertNamedUser(
            email: 'commuter@demo.com',
            attributes: [
                'name' => 'Avery Lin',
                'phone' => '+1-555-0210',
                'gender' => 'female',
                'date_of_birth' => '1993-11-30',
                'portal_type' => 'visitor',
                'bio' => 'Subway-commute reader. Bookmarks ten things, finishes three.',
                'employment_type' => null,
                'created_at' => now()->subMonths(4),
            ],
        );

        // ───── Factory visitors — top up to target if below ────────
        $existingVisitors = User::query()
            ->where('portal_type', 'visitor')
            ->whereNotIn('email', ['visitor@demo.com', 'newbie@demo.com', 'commuter@demo.com'])
            ->count();
        $needVisitors = max(0, self::FACTORY_VISITOR_TARGET - $existingVisitors);
        if ($needVisitors > 0) {
            User::factory()
                ->visitor()
                ->withBio()
                ->count($needVisitors)
                ->create([
                    'status' => 'active',
                    'department_id' => null,
                    'manager_id' => null,
                    'employee_id' => null,
                    'job_title' => null,
                    'employment_type' => null,
                ]);
        }

        $this->command?->info(sprintf(
            'DemoUserSeeder: staff=3, authors=%d, visitors=%d (named + factory).',
            User::query()->where('portal_type', 'author')->count(),
            User::query()->where('portal_type', 'visitor')->count(),
        ));
    }

    /**
     * Create-or-update a named demo user. Always sets password=password
     * (re-hashed each run, which is fine for demo data) + verified email.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function upsertNamedUser(string $email, array $attributes): User
    {
        $defaults = [
            'password' => Hash::make('password'),
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ];

        return User::query()->updateOrCreate(
            ['email' => $email],
            array_merge($defaults, $attributes),
        );
    }
}
