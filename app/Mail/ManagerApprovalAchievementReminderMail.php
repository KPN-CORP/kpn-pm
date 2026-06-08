<?php

namespace App\Mail;

use App\Exports\ManagerApprovalAchievementReminderExport;
use Illuminate\Mail\Mailable;
use Maatwebsite\Excel\Facades\Excel;

class ManagerApprovalAchievementReminderMail extends Mailable
{
    public function __construct(
        public $manager,
        public $achievements
    ) {}

    public function build()
    {
        $fileName =
            'pending-achievement-approval.xlsx';

        Excel::store(
            new ManagerApprovalAchievementReminderExport(
                $this->achievements
            ),
            "temp/{$fileName}", 'public'
        );

        return $this
            ->subject(
                'Pending Achievement Approval Reminder'
            )
            ->view(
                'emails.manager-approval-achievement-reminder'
            )
            ->with([
                'manager' => $this->manager,
                'count' => $this->achievements->count(),
            ])
            ->attach(
                storage_path(
                    "app/public/temp/{$fileName}"
                )
            );
    }
}