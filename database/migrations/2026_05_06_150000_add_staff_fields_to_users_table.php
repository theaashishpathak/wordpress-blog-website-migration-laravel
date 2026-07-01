<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('employee_id')->nullable()->after('email')->index();
            $table->string('job_title')->nullable()->after('employee_id');
            $table->foreignId('department_id')->nullable()->after('job_title')->constrained('departments')->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->after('department_id')->constrained('users')->nullOnDelete();
            $table->date('hire_date')->nullable()->after('manager_id');
            $table->enum('employment_type', ['full_time', 'part_time', 'contractor', 'intern', 'consultant'])
                ->nullable()
                ->after('hire_date');
            $table->json('working_hours')->nullable()->after('employment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['manager_id']);
            $table->dropColumn([
                'employee_id',
                'job_title',
                'department_id',
                'manager_id',
                'hire_date',
                'employment_type',
                'working_hours',
            ]);
        });
    }
};
