<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * employees_pa was a full copy of employees (incl. KTP, bank account, address)
 * that stopped being refreshed, so new joiners were missing and HR changes
 * never arrived. It becomes:
 *
 * - employee_pa_profiles: only PA-specific data per employee: PA access_menu
 *   and PA termination (deleted_at). Employee information is never changed
 *   from PA; it always comes from employees.
 * - employees_pa: a VIEW over employees LEFT JOIN employee_pa_profiles that
 *   exposes the columns the app reads, so existing queries keep working.
 * - employees_pa_legacy: the old table, renamed and kept as a backup.
 *
 * Employees deleted in employees are out of PA (deleted_at falls back to
 * employees.deleted_at).
 *
 * Run by path (migrations table is out of sync):
 *   php artisan migrate --force --path=database/migrations/2026_11_20_100000_split_employees_pa_into_profiles.php
 * Safe to re-run: every step checks the current state first.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->createProfilesTable();

        if ($this->tableType('employees_pa') === 'BASE TABLE') {
            if (Schema::hasTable('employees_pa_legacy')) {
                throw new RuntimeException('Both employees_pa (table) and employees_pa_legacy exist; resolve manually.');
            }
            DB::statement('RENAME TABLE `employees_pa` TO `employees_pa_legacy`');
        }

        if (Schema::hasTable('employees_pa_legacy') && ! DB::table('employee_pa_profiles')->exists()) {
            DB::statement('
                INSERT INTO employee_pa_profiles (employee_id, access_menu, deleted_at, created_at, updated_at)
                SELECT l.employee_id, l.access_menu, l.deleted_at, l.created_at, l.updated_at
                FROM employees_pa_legacy l
                JOIN employees e ON e.employee_id = l.employee_id
            ');
        }

        DB::statement('CREATE OR REPLACE SQL SECURITY INVOKER VIEW `employees_pa` AS
            SELECT
                e.`id`,
                e.`employee_id`,
                e.`fullname`,
                e.`gender`,
                e.`email`,
                e.`group_company`,
                e.`designation`,
                e.`designation_code`,
                e.`designation_name`,
                e.`job_level`,
                e.`company_name`,
                e.`contribution_level_code`,
                e.`work_area_code`,
                e.`office_area`,
                e.`manager_l1_id`,
                e.`manager_l2_id`,
                e.`employee_type`,
                e.`unit`,
                e.`date_of_joining`,
                e.`users_id`,
                COALESCE(p.`access_menu`, e.`access_menu`) AS `access_menu`,
                e.`created_at`,
                GREATEST(e.`updated_at`, COALESCE(p.`updated_at`, e.`updated_at`)) AS `updated_at`,
                COALESCE(p.`deleted_at`, e.`deleted_at`) AS `deleted_at`
            FROM `employees` e
            LEFT JOIN `employee_pa_profiles` p ON p.`employee_id` = e.`employee_id`');
    }

    public function down(): void
    {
        if ($this->tableType('employees_pa') === 'VIEW') {
            DB::statement('DROP VIEW `employees_pa`');
        }

        // Changes made through the app after up() (access_menu, terminations)
        // live only in employee_pa_profiles and are NOT copied back.
        if (Schema::hasTable('employees_pa_legacy') && ! Schema::hasTable('employees_pa')) {
            DB::statement('RENAME TABLE `employees_pa_legacy` TO `employees_pa`');
        }

        Schema::dropIfExists('employee_pa_profiles');
    }

    private function createProfilesTable(): void
    {
        if (Schema::hasTable('employee_pa_profiles')) {
            return;
        }

        Schema::create('employee_pa_profiles', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->string('employee_id', 25)->unique();
            $table->longText('access_menu')->nullable();
            $table->string('deleted_by', 36)->nullable();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    private function tableType(string $name): ?string
    {
        return DB::table('information_schema.tables')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $name)
            ->value('table_type');
    }
};
