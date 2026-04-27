<?php
namespace App\Services;

use App\Models\KPIAchievement;

class KPIAchievementService
{
    public static function getByGoal($goalId)
    {
        $data = KPIAchievement::where('goal_id', $goalId)
            ->get()
            ->groupBy('kpi_id');

        $result = [];

        foreach ($data as $kpiId => $rows) {

            $months = array_fill(1, 12, null);
            $attachments = array_fill(1, 12, null);
            $approvalStatuses = array_fill(1, 12, null);

            foreach ($rows as $row) {
                $month = (int) $row->month; // langsung 1-12

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
