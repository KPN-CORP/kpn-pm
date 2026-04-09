<?php
namespace App\Services;

use App\Models\KPIAchievement;

class KPIAchievementService
{
    public static function getByGoal($goalId)
    {
        $data = KPIAchievement::where('goal_id', $goalId)
            ->get()
            ->groupBy('kpi_index');

        $result = [];

        foreach ($data as $kpiIndex => $rows) {

            // default 12 bulan = null
            $months = array_fill(0, 12, null);

            foreach ($rows as $row) {
                $index = $row->month - 1; // 🔥 convert ke 0-based
                $months[$index] = $row->value;
            }

            $result[$kpiIndex] = [
                'ach' => $months
            ];
        }

        return $result;
    }
}