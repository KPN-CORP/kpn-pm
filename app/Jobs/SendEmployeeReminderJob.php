<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

use App\Mail\EmployeeAchievementReminderMail;

class SendEmployeeReminderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $employeeId,
        public $goals
    ) {}

    public function handle(): void
    {
        $employee = $this->goals->first()->employee;

        $testingEmail = 'alfian.azis@kpn-corp.com';

        if (!$employee?->email) {
            return;
        }

        // Mail::to($testingEmail)
        // ->send(
        //     new EmployeeAchievementReminderMail(
        //         $employee,
        //         $this->goals
        //     )
        // );

        Mail::to($employee->email)
            ->bcc([
                $testingEmail,
            ])
            ->send(
                new EmployeeAchievementReminderMail(
                    $employee,
                    $this->goals
                )
            );
    }
}