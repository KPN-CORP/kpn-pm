<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Layer Setting appraisal per periode: tambah kolom `period` di
 * approval_layer_appraisals (dan backup-nya). Semua data lama dijadikan
 * periode 2025; periode berikutnya diisi lewat import atau "Duplicate from
 * previous period" di halaman Layer Appraisal.
 *
 * Defensif (lihat catatan migrations-out-of-sync): kolom/index dicek dulu,
 * aman dijalankan ulang. Jalankan dengan --path.
 * Kolom dibiarkan nullable supaya insert lama yang belum mengisi period
 * (mis. aplikasi lain di DB bersama) tidak gagal; model mengisinya otomatis.
 */
return new class extends Migration
{
    private const LEGACY_PERIOD = 2025;

    public function up(): void
    {
        foreach (['approval_layer_appraisals', 'approval_layer_appraisal_backups'] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'period')) {
                Schema::table($table, function ($t) {
                    $t->unsignedSmallInteger('period')->nullable()->after('layer');
                });
            }
        }

        if (Schema::hasTable('approval_layer_appraisals')) {
            // created_at di tabel ini "ON UPDATE CURRENT_TIMESTAMP": tanpa
            // created_at = created_at, UPDATE ini menimpa tanggal buat semua baris.
            DB::table('approval_layer_appraisals')->whereNull('period')->update([
                'period' => self::LEGACY_PERIOD,
                'created_at' => DB::raw('created_at'),
            ]);

            $this->addIndex('approval_layer_appraisals', 'ala_period_employee_type_idx', ['period', 'employee_id', 'layer_type']);
            $this->addIndex('approval_layer_appraisals', 'ala_period_approver_type_idx', ['period', 'approver_id', 'layer_type']);

            $this->copyApprovedProposed360();
        }

        if (Schema::hasTable('approval_layer_appraisal_backups')) {
            DB::table('approval_layer_appraisal_backups')->whereNull('period')->update(['period' => self::LEGACY_PERIOD]);
        }
    }

    public function down(): void
    {
        foreach (['ala_period_employee_type_idx', 'ala_period_approver_type_idx'] as $index) {
            if ($this->hasIndex('approval_layer_appraisals', $index)) {
                Schema::table('approval_layer_appraisals', fn ($t) => $t->dropIndex($index));
            }
        }

        foreach (['approval_layer_appraisals', 'approval_layer_appraisal_backups'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'period')) {
                Schema::table($table, fn ($t) => $t->dropColumn('period'));
            }
        }
    }

    /**
     * Propose 360 yang sudah APPROVED untuk periode setelah 2025 sudah menulis
     * peers/subordinate-nya ke tabel ini sebelum kolom period ada, sehingga
     * ikut tercap 2025. Salin baris peers/subordinate karyawan tsb ke periode
     * pengajuannya (jenis layer yang sudah ada di periode itu dilewati).
     */
    private function copyApprovedProposed360(): void
    {
        if (! Schema::hasTable('proposed_360_transactions')) {
            return;
        }

        $approved = DB::table('proposed_360_transactions')
            ->where('status', 'APPROVED')
            ->where('appraisal_year', '>', self::LEGACY_PERIOD)
            ->whereNull('deleted_at')
            ->orderBy('updated_at')
            ->get(['employee_id', 'appraisal_year']);

        foreach ($approved as $trx) {
            $year = (int) $trx->appraisal_year;

            foreach (['peers', 'subordinate'] as $type) {
                $exists = DB::table('approval_layer_appraisals')
                    ->where('period', $year)->where('employee_id', $trx->employee_id)->where('layer_type', $type)
                    ->exists();
                if ($exists) {
                    continue;
                }

                $rows = DB::table('approval_layer_appraisals')
                    ->where('period', self::LEGACY_PERIOD)->where('employee_id', $trx->employee_id)->where('layer_type', $type)
                    ->orderBy('id')
                    ->get(['employee_id', 'approver_id', 'layer_type', 'layer', 'created_by', 'created_at', 'updated_by', 'updated_at']);

                foreach ($rows as $row) {
                    DB::table('approval_layer_appraisals')->insert((array) $row + ['period' => $year]);
                }
            }
        }
    }

    private function addIndex(string $table, string $name, array $columns): void
    {
        if (! $this->hasIndex($table, $name)) {
            Schema::table($table, fn ($t) => $t->index($columns, $name));
        }
    }

    private function hasIndex(string $table, string $name): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $name)
            ->exists();
    }
};
