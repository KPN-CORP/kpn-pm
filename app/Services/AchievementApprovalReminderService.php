<?php

namespace App\Services;

use App\Models\KPIAchievement;
use Illuminate\Support\Collection;

class AchievementApprovalReminderService
{
    public function getPendingApprovals(): Collection
    {
        return KPIAchievement::query()
            ->with([
                'goal.employee',
                'approver',
            ])
            ->where('approval_status', 'Pending')
            ->whereNotNull('current_approver_employee_id')
            ->get()
            ->unique(fn ($item) => $item->goal->employee_id)
            ->values();
    }
}