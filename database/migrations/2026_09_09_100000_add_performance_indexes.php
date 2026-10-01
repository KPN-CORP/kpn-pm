<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Index untuk kolom-kolom yang dipakai di WHERE / JOIN pada halaman list
 * (Goals, Team Goals, Appraisal Task, Report, Calibration).
 *
 * Migration ini SENGAJA defensif. Skema produksi sudah lama diubah manual di
 * luar migration (tabel employees_pa, approval_layer_appraisals,
 * kpi_achievements dll tidak punya file migration sama sekali, dan kolom
 * seperti approval_requests.period juga ditambah manual). Jadi setiap tabel,
 * kolom, dan index dicek dulu sebelum dibuat — aman dijalankan berkali-kali
 * dan aman kalau ada environment yang skemanya berbeda.
 */
return new class extends Migration
{
    /**
     * Daftar index: nama tabel => [nama index => [kolom, ...]].
     *
     * Urutan kolom pada composite index mengikuti urutan filter di query:
     * kolom equality yang paling selektif duluan.
     */
    private function indexes(): array
    {
        return [
            // Dipakai hampir di semua layar. Lihat MyGoalController::index,
            // ReportController::getReportContent, AppService::getNotificationCounts*.
            'approval_requests' => [
                'ar_employee_category_period_idx' => ['employee_id', 'category', 'period'],
                'ar_approver_category_period_idx' => ['current_approval_id', 'category', 'period', 'status'],
                'ar_category_period_idx' => ['category', 'period'],
                'ar_deleted_at_idx' => ['deleted_at'],
            ],

            'goals' => [
                'goals_employee_period_idx' => ['employee_id', 'period'],
                'goals_period_status_idx' => ['period', 'form_status'],
                'goals_deleted_at_idx' => ['deleted_at'],
            ],

            'appraisals' => [
                'appraisals_employee_period_idx' => ['employee_id', 'period'],
                'appraisals_goals_id_idx' => ['goals_id'],
                'appraisals_period_status_idx' => ['period', 'form_status'],
                'appraisals_deleted_at_idx' => ['deleted_at'],
            ],

            // Di-query per baris di MyGoal / TeamGoal / Report (lihat perbaikan
            // N+1 di tier 2 — tetap butuh index untuk lookup bulk-nya).
            'approval_layers' => [
                'al_employee_approver_idx' => ['employee_id', 'approver_id'],
                'al_approver_idx' => ['approver_id'],
            ],

            'approval_layer_appraisals' => [
                'ala_approver_layertype_idx' => ['approver_id', 'layer_type'],
                'ala_employee_layertype_idx' => ['employee_id', 'layer_type'],
            ],

            'employees' => [
                'emp_manager_l1_idx' => ['manager_l1_id'],
                'emp_manager_l2_idx' => ['manager_l2_id'],
                'emp_work_area_idx' => ['work_area_code'],
                'emp_group_company_idx' => ['group_company'],
                'emp_contribution_level_idx' => ['contribution_level_code'],
                'emp_deleted_at_idx' => ['deleted_at'],
            ],

            'employees_pa' => [
                'emppa_employee_idx' => ['employee_id'],
                'emppa_manager_l1_idx' => ['manager_l1_id'],
                'emppa_work_area_idx' => ['work_area_code'],
                'emppa_group_company_idx' => ['group_company'],
                'emppa_contribution_level_idx' => ['contribution_level_code'],
                'emppa_deleted_at_idx' => ['deleted_at'],
            ],

            'kpi_achievements' => [
                'kpiach_goal_kpi_month_idx' => ['goal_id', 'kpi_id', 'month'],
                'kpiach_approver_idx' => ['current_approver_employee_id'],
                'kpiach_deleted_at_idx' => ['deleted_at'],
            ],

            'appraisal_contributors' => [
                'apc_employee_period_idx' => ['employee_id', 'period'],
                'apc_contributor_period_idx' => ['contributor_id', 'period'],
                'apc_appraisal_idx' => ['appraisal_id'],
            ],

            'calibrations' => [
                'cal_employee_period_status_idx' => ['employee_id', 'period', 'status'],
                'cal_approver_period_idx' => ['approver_id', 'period'],
                'cal_appraisal_idx' => ['appraisal_id'],
            ],

            // Dibaca berkali-kali per request oleh AppService::goalPeriod() dkk.
            'schedules' => [
                'sch_event_type_idx' => ['event_type'],
                'sch_event_dates_idx' => ['event_type', 'start_date', 'end_date'],
            ],

            'approvals' => [
                'approvals_request_idx' => ['request_id'],
            ],

            'approval_logs' => [
                'aplog_request_idx' => ['approval_request_id'],
            ],

            'proposed_360_transactions' => [
                'p360_employee_year_idx' => ['employee_id', 'appraisal_year'],
                'p360_status_idx' => ['status'],
            ],
        ];
    }

    public function up(): void
    {
        foreach ($this->indexes() as $table => $definitions) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($definitions as $name => $columns) {
                if (! $this->hasAllColumns($table, $columns)) {
                    continue;
                }

                if ($this->indexExists($table, $name)) {
                    continue;
                }

                Schema::table($table, function ($blueprint) use ($columns, $name) {
                    $blueprint->index($columns, $name);
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes() as $table => $definitions) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (array_keys($definitions) as $name) {
                if (! $this->indexExists($table, $name)) {
                    continue;
                }

                Schema::table($table, function ($blueprint) use ($name) {
                    $blueprint->dropIndex($name);
                });
            }
        }
    }

    private function hasAllColumns(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function indexExists(string $table, string $name): bool
    {
        $result = DB::selectOne(
            'SELECT COUNT(*) AS total
               FROM information_schema.statistics
              WHERE table_schema = DATABASE()
                AND table_name = ?
                AND index_name = ?',
            [$table, $name]
        );

        return $result && (int) $result->total > 0;
    }
};
