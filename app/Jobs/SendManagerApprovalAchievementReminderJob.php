<?php

namespace App\Jobs;

use App\Mail\ManagerApprovalAchievementReminderMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

use App\Models\Employee;

class SendManagerApprovalAchievementReminderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $approverId,
        public $achievements
    ) {}

    public function handle(): void
    {
        $manager = Employee::where(
            'employee_id',
            $this->approverId
        )->first();

        if (!$manager) {
            return;
        }

        Log::debug('email triggered', [
            'type' => 'manager-approval',
            'approver_id' => $this->approverId,
            'count' => $this->achievements->count(),
            'email' => $manager->email,
        ]);

        $testingEmail = 'alfian.azis@kpn-corp.com';

        Mail::to($testingEmail)
            ->bcc([
                $testingEmail,
            ])
            ->send(
            new ManagerApprovalAchievementReminderMail(
                $manager,
                $this->achievements
            )
        );
    }
}