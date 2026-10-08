<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Karyawan rehire mendapat employee_id BARU; id lama ada di kolom
 * employees.`old_employee_id_(rehired)` (isi "N.A." kalau bukan rehire).
 * Service ini memindahkan (re-key) semua data PM milik id lama ke id baru,
 * supaya karyawan melihat histori goal/appraisal-nya dan bisa melanjutkan
 * periode berjalan (update achievement, revise goal, dll) dengan id baru.
 *
 * Aturan:
 * - Data milik karyawan (goals, appraisals, calibrations, ...) dipindah ke id
 *   baru untuk semua periode, KECUALI periode di mana id baru sudah punya
 *   goal/appraisal sendiri — di periode itu data id baru yang dipakai dan data
 *   id lama ditinggal (tidak dihapus) dan dicatat sebagai `skipped`.
 * - Kolom di mana karyawan berperan sebagai approver / kontributor / manager
 *   selalu dipindah (orangnya sama).
 * - Tidak disentuh: approval_logs (audit trail, immutable), approval layer
 *   milik id lama (id baru sudah punya layer sendiri dari sync), baris
 *   employees / employees_pa / users id lama, created_by/updated_by (users.id),
 *   dan tabel aplikasi HCIS lain yang berbagi database.
 * - Setiap baris yang dipindah / ditinggal dicatat di employee_id_merges.
 *
 * Dipanggil saat login (event Login + SSO JWT) dan oleh command
 * `employees:merge-rehired`.
 */
class RehireMergeService
{
    public const REHIRE_COLUMN = 'old_employee_id_(rehired)';

    // Setelah merge sukses, login berikutnya dalam jangka waktu ini tidak
    // menjalankan merge lagi (beberapa kolom di-scan tanpa index).
    private const LOGIN_CACHE_HOURS = 6;

    // Data milik karyawan: tabel => kolom periode.
    private const OWNED = [
        'goals' => 'period',
        'appraisals' => 'period',
        'calibrations' => 'period',
        'achievements' => 'period',
        'kpi_units' => 'periode',
        'proposed_360_transactions' => 'appraisal_year',
    ];

    // Kolom di mana id karyawan dipakai sebagai approver / kontributor / manager.
    private const ACTOR = [
        ['approval_layers', 'approver_id'],
        ['approval_layer_appraisals', 'approver_id'],
        ['approval_requests', 'current_approval_id'],
        ['approval_requests', 'sendback_to'],
        ['approvals', 'approver_id'],
        ['calibrations', 'approver_id'],
        ['appraisal_contributors', 'contributor_id'],
        ['kpi_achievements', 'current_approver_employee_id'],
        ['proposed_360_transactions', 'proposer_employee_id'],
        ['employees_pa', 'manager_l1_id'],
        ['employees_pa', 'manager_l2_id'],
    ];

    // Kolom JSON berisi array employee_id (current_approval_id di path 360
    // menyimpan JSON array, lihat ApprovalController::processAction).
    private const JSON_LISTS = [
        ['approval_requests', 'current_approval_id'],
        ['proposed_360_transactions', 'managers'],
        ['proposed_360_transactions', 'peers'],
        ['proposed_360_transactions', 'subordinates'],
    ];

    private array $columnCache = [];

    // Dry run tidak menulis apa pun (tidak ada UPDATE / INSERT sama sekali).
    private bool $dryRun = false;

    /**
     * Dipanggil saat login. Tidak pernah melempar exception supaya login tidak
     * gagal karena merge.
     */
    public function mergeOnLogin(?string $employeeId): void
    {
        if (! $employeeId) {
            return;
        }

        $cacheKey = 'rehire-merge:'.$employeeId;

        try {
            if (Cache::has($cacheKey)) {
                return;
            }

            $this->mergeFor($employeeId);

            Cache::put($cacheKey, true, now()->addHours(self::LOGIN_CACHE_HOURS));
        } catch (\Throwable $e) {
            Log::error('Rehire merge failed', [
                'employee_id' => $employeeId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Id lama untuk employee_id baru ini, atau null kalau bukan rehire / tidak
     * aman di-merge.
     */
    public function oldEmployeeIdFor(string $newEmployeeId): ?string
    {
        $employee = DB::table('employees')
            ->where('employee_id', $newEmployeeId)
            ->whereNull('deleted_at')
            ->first(['employee_id', self::REHIRE_COLUMN]);

        $oldId = trim((string) ($employee->{self::REHIRE_COLUMN} ?? ''));

        if ($oldId === '' || strtoupper($oldId) === 'N.A.' || $oldId === $newEmployeeId) {
            return null;
        }

        // Jangan pernah mengambil data karyawan yang masih aktif.
        $oldStillActive = DB::table('employees')
            ->where('employee_id', $oldId)
            ->whereNull('deleted_at')
            ->exists();

        if ($oldStillActive) {
            Log::warning('Rehire merge skipped: old employee_id is still active', [
                'old_employee_id' => $oldId,
                'new_employee_id' => $newEmployeeId,
            ]);

            return null;
        }

        return $oldId;
    }

    /**
     * Pindahkan data id lama ke id baru. Dengan $dryRun tidak ada yang
     * ditulis; hanya laporannya yang dikembalikan.
     *
     * @return array{old: string|null, new: string, conflict_periods: array, moved: array, skipped: array}
     */
    public function mergeFor(string $newEmployeeId, bool $dryRun = false): array
    {
        $report = [
            'old' => null,
            'new' => $newEmployeeId,
            'conflict_periods' => [],
            'moved' => [],
            'skipped' => [],
        ];

        $oldId = $this->oldEmployeeIdFor($newEmployeeId);
        if (! $oldId) {
            return $report;
        }

        // Tanpa tabel log tidak ada jejak untuk rollback — jangan merge.
        if (! $dryRun && ! Schema::hasTable('employee_id_merges')) {
            throw new \RuntimeException('Table employee_id_merges does not exist; run its migration first.');
        }
        $report['old'] = $oldId;

        $this->dryRun = $dryRun;

        if ($dryRun) {
            $this->runMerge($oldId, $newEmployeeId, $report);

            return $report;
        }

        DB::transaction(function () use ($oldId, $newEmployeeId, &$report) {
            $this->runMerge($oldId, $newEmployeeId, $report);
        });

        if ($report['moved'] || $report['skipped']) {
            Log::channel('audit')->info('rehire_merge', $report);
        }

        return $report;
    }

    private function runMerge(string $oldId, string $newId, array &$report): void
    {
        // Periode di mana id baru sudah punya form sendiri: id baru menang.
        $conflictPeriods = collect(['goals', 'appraisals'])
            ->flatMap(fn ($table) => $this->liveQuery($table)
                ->where('employee_id', $newId)
                ->distinct()
                ->pluck('period'))
            ->map(fn ($p) => (string) $p)
            ->unique()
            ->values()
            ->all();
        $report['conflict_periods'] = $conflictPeriods;

        // 1. Data milik karyawan.
        $skippedFormIds = [];
        foreach (self::OWNED as $table => $periodColumn) {
            if (! $this->hasColumns($table, ['employee_id', $periodColumn])) {
                continue;
            }

            // Periode yang sudah ada di tabel ini untuk id baru juga ditinggal,
            // supaya tidak ada dua baris untuk periode yang sama.
            $skipPeriods = array_unique(array_merge(
                $conflictPeriods,
                $this->liveQuery($table)
                    ->where('employee_id', $newId)
                    ->distinct()
                    ->pluck($periodColumn)
                    ->map(fn ($p) => (string) $p)
                    ->all()
            ));

            $rows = DB::table($table)->where('employee_id', $oldId)->get(['id', $periodColumn]);
            [$skip, $move] = $rows->partition(fn ($r) => in_array((string) $r->{$periodColumn}, $skipPeriods, true));

            if (in_array($table, ['goals', 'appraisals'])) {
                $skippedFormIds = array_merge($skippedFormIds, $skip->pluck('id')->map(fn ($id) => (string) $id)->all());
            }

            $this->move($table, 'employee_id', $move->pluck('id')->all(), $oldId, $newId, $report);
            $this->logSkipped($table, 'employee_id', $skip->pluck('id')->all(), $oldId, $newId, 'period already exists on new employee_id', $report);
        }

        // 2. Data yang ikut induknya (form) — ditinggal kalau formnya ditinggal.
        $dependents = [
            ['approval_requests', 'form_id', 'period'],
            ['approval_snapshots', 'form_id', null],
            ['appraisal_contributors', 'appraisal_id', 'period'],
        ];
        foreach ($dependents as [$table, $parentColumn, $periodColumn]) {
            if (! $this->hasColumns($table, array_filter(['employee_id', $parentColumn, $periodColumn]))) {
                continue;
            }

            $rows = DB::table($table)->where('employee_id', $oldId)->get(array_filter(['id', $parentColumn, $periodColumn]));
            [$skip, $move] = $rows->partition(fn ($r) => in_array((string) $r->{$parentColumn}, $skippedFormIds, true)
                || ($periodColumn && in_array((string) $r->{$periodColumn}, $conflictPeriods, true)));

            $this->move($table, 'employee_id', $move->pluck('id')->all(), $oldId, $newId, $report);
            $this->logSkipped($table, 'employee_id', $skip->pluck('id')->all(), $oldId, $newId, 'parent form kept on old employee_id', $report);
        }

        // 3. Peran sebagai approver / kontributor / manager.
        foreach (self::ACTOR as [$table, $column]) {
            if (! $this->hasColumns($table, [$column])) {
                continue;
            }

            $ids = DB::table($table)->where($column, $oldId)->pluck('id')->all();
            $this->move($table, $column, $ids, $oldId, $newId, $report);
        }

        // 4. Id di dalam JSON array.
        foreach (self::JSON_LISTS as [$table, $column]) {
            if (! $this->hasColumns($table, [$column])) {
                continue;
            }

            $needle = '"'.$oldId.'"';
            $ids = DB::table($table)->where($column, 'like', '%'.$needle.'%')->pluck('id')->all();
            if (! $ids) {
                continue;
            }

            if (! $this->dryRun) {
                DB::table($table)->whereIn('id', $ids)->update([
                    $column => DB::raw('REPLACE(`'.$column.'`, '.DB::getPdo()->quote($needle).', '.DB::getPdo()->quote('"'.$newId.'"').')'),
                ]);
            }
            $this->log($table, $column, $ids, $oldId, $newId, 'moved', 'json list');
            $report['moved']["$table.$column"] = ($report['moved']["$table.$column"] ?? 0) + count($ids);
        }
    }

    private function move(string $table, string $column, array $ids, string $oldId, string $newId, array &$report): void
    {
        if (! $ids) {
            return;
        }

        if (! $this->dryRun) {
            foreach (array_chunk($ids, 500) as $chunk) {
                DB::table($table)->whereIn('id', $chunk)->where($column, $oldId)->update([$column => $newId]);
            }
        }

        $this->log($table, $column, $ids, $oldId, $newId, 'moved');
        $report['moved']["$table.$column"] = ($report['moved']["$table.$column"] ?? 0) + count($ids);
    }

    private function logSkipped(string $table, string $column, array $ids, string $oldId, string $newId, string $note, array &$report): void
    {
        if (! $ids) {
            return;
        }

        // Login berikutnya menemukan baris yang sama lagi; cukup dicatat sekali.
        $alreadyLogged = ! Schema::hasTable('employee_id_merges') ? [] : DB::table('employee_id_merges')
            ->where('old_employee_id', $oldId)
            ->where('new_employee_id', $newId)
            ->where('table_name', $table)
            ->where('action', 'skipped')
            ->whereIn('row_id', $ids)
            ->pluck('row_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $ids = array_values(array_diff(array_map('strval', $ids), $alreadyLogged));
        if (! $ids) {
            return;
        }

        $this->log($table, $column, $ids, $oldId, $newId, 'skipped', $note);
        $report['skipped'][$table] = ($report['skipped'][$table] ?? 0) + count($ids);
    }

    private function log(string $table, string $column, array $ids, string $oldId, string $newId, string $action, ?string $note = null): void
    {
        if ($this->dryRun) {
            return;
        }

        $now = now();

        foreach (array_chunk($ids, 500) as $chunk) {
            DB::table('employee_id_merges')->insert(array_map(fn ($id) => [
                'old_employee_id' => $oldId,
                'new_employee_id' => $newId,
                'table_name' => $table,
                'column_name' => $column,
                'row_id' => (string) $id,
                'action' => $action,
                'note' => $note,
                'created_at' => $now,
            ], $chunk));
        }
    }

    private function liveQuery(string $table)
    {
        return DB::table($table)->when(
            $this->hasColumns($table, ['deleted_at']),
            fn ($q) => $q->whereNull('deleted_at')
        );
    }

    private function hasColumns(string $table, array $columns): bool
    {
        if (! array_key_exists($table, $this->columnCache)) {
            $this->columnCache[$table] = Schema::hasTable($table) ? Schema::getColumnListing($table) : [];
        }

        return $this->columnCache[$table] !== []
            && array_diff(array_merge(['id'], $columns), $this->columnCache[$table]) === [];
    }
}
