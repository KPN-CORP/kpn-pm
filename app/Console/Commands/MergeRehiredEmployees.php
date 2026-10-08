<?php

namespace App\Console\Commands;

use App\Services\RehireMergeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Merge data id lama ke id baru untuk karyawan rehire. Normalnya berjalan
 * otomatis saat karyawan login; command ini untuk cek (--dry-run) atau
 * back-fill semua rehire sekaligus.
 *
 *   php artisan employees:merge-rehired --dry-run
 *   php artisan employees:merge-rehired --employee=01124040014
 */
class MergeRehiredEmployees extends Command
{
    protected $signature = 'employees:merge-rehired
                            {--employee= : Only this NEW employee_id}
                            {--dry-run : Report what would change, write nothing}';

    protected $description = 'Re-key PM data of rehired employees from their old employee_id to the new one';

    public function handle(RehireMergeService $service): int
    {
        $column = RehireMergeService::REHIRE_COLUMN;
        $dryRun = (bool) $this->option('dry-run');

        $newIds = $this->option('employee')
            ? [$this->option('employee')]
            : DB::table('employees')
                ->whereNull('deleted_at')
                ->whereNotNull($column)
                ->whereNotIn($column, ['', 'N.A.'])
                ->pluck('employee_id')
                ->all();

        $rows = [];
        $totals = ['moved' => [], 'skipped' => []];

        foreach ($newIds as $newId) {
            $report = $service->mergeFor($newId, $dryRun);
            if (! $report['old']) {
                continue;
            }

            foreach (['moved', 'skipped'] as $key) {
                foreach ($report[$key] as $name => $count) {
                    $totals[$key][$name] = ($totals[$key][$name] ?? 0) + $count;
                }
            }

            if ($report['moved'] || $report['skipped']) {
                $rows[] = [
                    $report['old'],
                    $report['new'],
                    array_sum($report['moved']),
                    array_sum($report['skipped']),
                    implode(',', $report['conflict_periods']),
                ];
            }
        }

        $this->table(['Old ID', 'New ID', 'Moved', 'Skipped', 'New-ID periods'], $rows);

        foreach (['moved', 'skipped'] as $key) {
            ksort($totals[$key]);
            $this->line(ucfirst($key).' per table:');
            $this->table(['Table', 'Rows'], collect($totals[$key])->map(fn ($n, $t) => [$t, $n])->values()->all());
        }

        $this->info($dryRun ? 'Dry run: nothing was written.' : 'Done. Details in employee_id_merges.');

        return self::SUCCESS;
    }
}
