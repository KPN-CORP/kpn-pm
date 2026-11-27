<?php
namespace App\Services;

use App\Models\KPIAchievement;
use Illuminate\Support\Collection;

class KPIAchievementService
{
    /**
     * Achievement satu goal, dikelompokkan per kpi_id.
     *
     * Kalau kamu memproses banyak goal sekaligus (halaman list / report),
     * JANGAN panggil ini di dalam loop — pakai getByGoals() supaya cukup
     * satu query untuk semua goal.
     */
    public static function getByGoal($goalId)
    {
        return self::getByGoals([$goalId])[$goalId] ?? [];
    }

    /**
     * Versi bulk: satu query untuk banyak goal sekaligus.
     *
     * @param  iterable  $goalIds
     * @return array<string, array<string, array>>  goal_id => kpi_id => [ach, attachment, approval_status]
     */
    public static function getByGoals($goalIds): array
    {
        $goalIds = collect($goalIds)->filter()->unique()->values();

        if ($goalIds->isEmpty()) {
            return [];
        }

        $rows = KPIAchievement::query()
            ->select(['goal_id', 'kpi_id', 'month', 'value', 'file', 'approval_status'])
            ->whereIn('goal_id', $goalIds)
            ->get();

        $result = [];

        foreach ($rows->groupBy('goal_id') as $goalId => $goalRows) {
            $result[$goalId] = self::shapeRows($goalRows);
        }

        // Goal tanpa achievement tetap muncul sebagai array kosong, supaya
        // pemanggil bisa membedakan "belum di-query" dan "tidak ada data".
        foreach ($goalIds as $goalId) {
            $result[$goalId] ??= [];
        }

        return $result;
    }

    /**
     * Susun baris achievement satu goal menjadi array 12 bulan per KPI.
     */
    private static function shapeRows(Collection $rows): array
    {
        $result = [];

        foreach ($rows->groupBy('kpi_id') as $kpiId => $kpiRows) {
            $months = array_fill(1, 12, null);
            $attachments = array_fill(1, 12, null);
            $approvalStatuses = array_fill(1, 12, null);

            foreach ($kpiRows as $row) {
                $month = (int) $row->month; // langsung 1-12

                if ($month < 1 || $month > 12) {
                    continue;
                }

                $months[$month] = $row->value;
                $attachments[$month] = $row->file ?? null;
                $approvalStatuses[$month] = $row->approval_status ?? null;
            }

            $result[$kpiId] = [
                'ach' => $months,
                'attachment' => $attachments,
                'approval_status' => $approvalStatuses,
            ];
        }

        return $result;
    }
}
