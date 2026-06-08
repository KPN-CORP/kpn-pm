<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

use App\Mail\ManagerAchievementReminderMail;
use App\Models\Employee;

class SendManagerReminderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $managerId,
        public $goals
    ) {}

    public function handle(): void
    {
        $managerId = $this->goals
        ->first()
        ?->achievementList
        ?->first()
        ?->current_approver_employee_id;

        $manager = Employee::where(
            'employee_id',
            $managerId
        )->first();

        $testingEmail = 'alfian.azis@kpn-corp.com';

        if (!$manager?->email) {
            return;
        }

        Mail::to($testingEmail)
        ->send(
            new ManagerAchievementReminderMail(
                $manager,
                $this->goals
            )
        );

        // Mail::to($manager->email)
        //     ->bcc([
        //         $testingEmail,
        //     ])
        //     ->send(
        //         new ManagerAchievementReminderMail(
        //             $manager,
        //             $this->goals
        //         )
        //     );
    }
}