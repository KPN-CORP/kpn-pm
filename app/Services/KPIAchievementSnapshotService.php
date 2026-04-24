<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\KPIAchievement;
use App\Models\KPIAchievementSnapshot;

class KPIAchievementSnapshotService {
    public static function insertMany(array $kpiAchievements, $employeeID, $userID) {
        $errorTitle = "Can't insert KPI Achievement Snapshot";
        $timeNow = now();

        try {
            if (!$kpiAchievements || empty($kpiAchievements)) {
                return [
                    "status" => false,
                    "title" => $errorTitle,
                    "message" => "KPI Achievement data not found!",
                    "data" => null
                ];
            }

            $insertData = [];

            foreach ($kpiAchievements as $val) {
                $insertData[] = [
                    "kpi_achievement_id" => $val->id,
                    "goal_id" => $val->goal_id,
                    "kpi_id" => $val->kpi_id,
                    "month" => $val->month,
                    "value" => $val->value,
                    "file" => $val->file,
                    "employee_id" => $employeeID,
                    "created_by" => $userID,
                    "current_approver_employee_id" => $val->current_approver_employee_id,
                    "approval_status" => $val->approval_status,
                    "approval_date" => $val->approval_date,
                    "approval_info" => $val->approval_info,
                    "created_at" => $timeNow,
                    "updated_at" => $timeNow
                ];
            }

            KPIAchievementSnapshot::insert($insertData);

            return [
                "status" => true,
                "title" => "Success",
                "message" => "Success",
                "data" => null
            ];
        } catch (Exception $e) {
            Log::error('KPIAchievementSnapshotService', [
                'e' => $e,
            ]);

            return [
                "status" => false,
                "title" => $errorTitle,
                "message" => "General Error",
                "data" => null,
                "error" => $e
            ];
        }
    }

    public static function insertOne(KPIAchievement $kpiAchievement, $employeeID, $userID) {
        return self::insertMany([$kpiAchievement], $employeeID, $userID);
    }
}
