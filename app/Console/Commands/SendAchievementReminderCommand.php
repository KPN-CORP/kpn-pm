<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use App\Services\AchievementReminderService;
use App\Jobs\SendEmployeeReminderJob;
use App\Jobs\SendManagerReminderJob;

class SendAchievementReminderCommand extends Command
{
    protected $signature = 'reminder:achievement';

    protected $description =
        'Send achievement reminder emails';

    public function handle(
        AchievementReminderService $service
    ): int {

        /*
        |--------------------------------------------------------------------------
        | COMMAND START
        |--------------------------------------------------------------------------
        */

        Log::debug('achievement reminder command started');

        $this->info('Achievement reminder started');

        /*
        |--------------------------------------------------------------------------
        | LOAD GOALS
        |--------------------------------------------------------------------------
        */

        $goals = $service->getPendingGoals();

        Log::debug('pending goals loaded', [
            'total_goals' => $goals->count(),
        ]);

        $this->info(
            'Goals count: ' . $goals->count()
        );

        /*
        |--------------------------------------------------------------------------
        | STOP IF EMPTY
        |--------------------------------------------------------------------------
        */

        if ($goals->flatMap->achievementList->count() === 0) {

            Log::debug('no pending achievement found');

            $this->warn('No pending achievement found');

            return self::SUCCESS;
        }

        /*
        |--------------------------------------------------------------------------
        | EMPLOYEE REMINDER
        |--------------------------------------------------------------------------
        */

        $employeeGroups =
            $goals->groupBy('employee_id');

        Log::debug('employee groups generated', [
            'total_employee_groups' =>
                $employeeGroups->count(),
        ]);

        foreach (
            $employeeGroups as $employeeId => $items
        ) {

            Log::debug('employee reminder dispatched', [
                'employee_id' => $employeeId,
                'goal_count' => $items->count(),
            ]);

            SendEmployeeReminderJob::dispatch(
                $employeeId,
                $items
            );
        }

        /*
        |--------------------------------------------------------------------------
        | MANAGER REMINDER
        |--------------------------------------------------------------------------
        */
        $managerGroups = $goals->groupBy(function ($goal) {
            return $goal->achievementList->first()?->current_approver_employee_id;
        });

        Log::debug('manager groups generated', [
            'total_manager_groups' =>
                $managerGroups->count(),
        ]);

        foreach (
            $managerGroups as $managerId => $items
        ) {

            Log::debug('manager reminder dispatched', [
                'manager_id' => $managerId,
                'goal_count' => $items->count(),
            ]);

            SendManagerReminderJob::dispatch(
                $managerId,
                $items
            );
        }

        /*
        |--------------------------------------------------------------------------
        | COMMAND FINISHED
        |--------------------------------------------------------------------------
        */

        Log::debug('achievement reminder command finished');

        $this->info('Achievement reminder finished');

        return self::SUCCESS;
    }
}