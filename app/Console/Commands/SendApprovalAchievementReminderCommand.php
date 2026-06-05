<?php

namespace App\Console\Commands;

use App\Jobs\SendManagerApprovalAchievementReminderJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use App\Services\AchievementApprovalReminderService;

class SendApprovalAchievementReminderCommand extends Command
{
    protected $signature = 'reminder:approval-achievement';

    protected $description = 'Send pending achievement approval reminder to managers';

    public function handle(
        AchievementApprovalReminderService $service
    ): int {

        Log::debug('approval achievement reminder command started');

        $achievements = $service->getPendingApprovals();

        Log::debug('pending approvals loaded', [
            'count' => $achievements->count(),
        ]);

        if ($achievements->isEmpty()) {

            Log::debug('no pending approvals found');

            return self::SUCCESS;
        }

        $groups = $achievements->groupBy(
            'current_approver_employee_id'
        );

        foreach ($groups as $approverId => $items) {

            SendManagerApprovalAchievementReminderJob::dispatch(
                $approverId,
                $items
            );

            Log::debug('approval reminder queued', [
                'approver_id' => $approverId,
                'achievement_count' => $items->count(),
            ]);
        }

        Log::debug('approval achievement reminder command finished');

        return self::SUCCESS;
    }
}