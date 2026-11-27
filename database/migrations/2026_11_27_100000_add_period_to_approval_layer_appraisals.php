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

    /** Siklus yang sudah berjalan saat kolom period ditambahkan. */
    private const CURRENT_PERIOD = 2026;

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

            $this->copyCurrentCycleLayers();
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
     * Layer yang sebenarnya sudah disiapkan untuk siklus berikutnya ikut
     * tercap 2025 karena kolom period belum ada. Seluruh layer (manager,
     * calibrator, peers, subordinate) karyawan berikut disalin ke periodenya:
     *
     *  - karyawan dengan Propose 360 APPROVED untuk tahun > 2025
     *    (approval 360 menulis ulang semua layer karyawan tsb) → appraisal_year;
     *  - karyawan yang layer-nya diubah sejak jadwal PA 2026 dimulai
     *    (updated_at >= start jadwal) → 2026. Perubahan Jan–Feb 2026 masih
     *    koreksi siklus 2025, jadi tidak ikut.
     *
     * Jenis layer yang sudah ada di periode tujuan dilewati; created_by /
     * updated_by / tanggal disalin apa adanya. Aman dijalankan ulang.
     */
    private function copyCurrentCycleLayers(): void
    {
        $targets = []; // employee_id => period

        if (Schema::hasTable('proposed_360_transactions')) {
            DB::table('proposed_360_transactions')
                ->where('status', 'APPROVED')
                ->where('appraisal_year', '>', self::LEGACY_PERIOD)
                ->whereNull('deleted_at')
                ->orderBy('updated_at')
                ->get(['employee_id', 'appraisal_year'])
                ->each(function ($trx) use (&$targets) {
                    $targets[(string) $trx->employee_id] = (int) $trx->appraisal_year;
                });
        }

        $cycleStart = $this->cycleStart(self::CURRENT_PERIOD);
        DB::table('approval_layer_appraisals')
            ->where('period', self::LEGACY_PERIOD)
            ->where('updated_at', '>=', $cycleStart)
            ->distinct()
            ->pluck('employee_id')
            ->each(function ($employeeId) use (&$targets) {
                $targets[(string) $employeeId] ??= self::CURRENT_PERIOD;
            });

        foreach ($targets as $employeeId => $year) {
            $source = DB::table('approval_layer_appraisals as src')
                ->where('src.period', self::LEGACY_PERIOD)
                ->where('src.employee_id', $employeeId)
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))
                    ->from('approval_layer_appraisals as dst')
                    ->where('dst.period', $year)
                    ->whereColumn('dst.employee_id', 'src.employee_id')
                    ->whereColumn('dst.layer_type', 'src.layer_type'))
                ->orderBy('src.id')
                ->select([
                    'src.employee_id', 'src.approver_id', 'src.layer_type', 'src.layer',
                    DB::raw((int) $year),
                    'src.created_by', 'src.created_at', 'src.updated_by', 'src.updated_at',
                ]);

            DB::table('approval_layer_appraisals')->insertUsing(
                ['employee_id', 'approver_id', 'layer_type', 'layer', 'period', 'created_by', 'created_at', 'updated_by', 'updated_at'],
                $source
            );
        }
    }

    /**
     * Awal siklus PA suatu periode: tanggal mulai jadwal PA paling awal
     * (schedulepa / masterschedulepa); jadwal Goals tidak dipakai karena
     * mid-year review jatuh di tengah tahun. Tanpa jadwal: 1 Nov.
     */
    private function cycleStart(int $period): string
    {
        $start = Schema::hasTable('schedules')
            ? DB::table('schedules')
                ->where('schedule_periode', $period)
                ->whereIn('event_type', ['schedulepa', 'masterschedulepa'])
                ->whereNull('deleted_at')
                ->min('start_date')
            : null;

        return $start ?: $period.'-11-01';
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
