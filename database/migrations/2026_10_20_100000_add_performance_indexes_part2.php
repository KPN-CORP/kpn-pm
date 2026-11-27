<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lanjutan 2026_09_09_100000_add_performance_indexes: index yang belum
 * tercakup di sana, hasil audit query vs skema stage.
 *
 * Sama defensifnya: tabel/kolom/index dicek dulu, aman dijalankan ulang.
 * Sengaja index biasa (bukan UNIQUE) — employees.employee_id misalnya
 * seharusnya unik, tapi kalau di produksi ternyata ada duplikat, UNIQUE
 * akan membuat migration gagal di tengah jalan.
 */
return new class extends Migration
{
    private function indexes(): array
    {
        return [
            // whereHas('employee') di hitungan notifikasi dan join di
            // LayerController / MyGoalController. Di DB stage kolom ini
            // tidak punya index sama sekali.
            'employees' => [
                'emp_employee_id_idx' => ['employee_id'],
            ],

            'employees_pa' => [
                'emppa_manager_l2_idx' => ['manager_l2_id'],
            ],

            // Lookup layer berikutnya saat approve (OnBehalfController,
            // MyGoalController ->orderBy('layer')).
            'approval_layers' => [
                'al_employee_layer_idx' => ['employee_id', 'layer'],
            ],

            // Cari calibrator / layer tertentu per karyawan
            // (AppraisalTaskController, AppService::ratingAllowedCheck).
            'approval_layer_appraisals' => [
                'ala_emp_type_layer_idx' => ['employee_id', 'layer_type', 'layer'],
            ],

            'appraisal_contributors' => [
                'apc_appraisal_contrib_idx' => ['appraisal_id', 'contributor_id'],
            ],

            'calibrations' => [
                'cal_appraisal_creator_status_idx' => ['appraisal_id', 'created_by', 'status'],
            ],

            // SendbackController, LayerController, OnBehalfController.
            'approvals' => [
                'approvals_request_approver_idx' => ['request_id', 'approver_id'],
            ],

            // Snapshot terakhir per form (export detail appraisal, detail
            // admin, AppService::suggestedRating). Tabel ini tidak punya
            // index apa pun di stage.
            'approval_snapshots' => [
                'snap_form_creator_idx' => ['form_id', 'created_by'],
            ],

            // AppService::checkKpiUnit (sidebar, setiap halaman) dan
            // OnBehalfController rating.
            'kpi_units' => [
                'kpiu_employee_periode_idx' => ['employee_id', 'periode'],
            ],

            'flows' => [
                'flows_module_transaction_idx' => ['module_transaction'],
            ],

            'schedules' => [
                'sch_start_date_idx' => ['start_date'],
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
