<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

use Maatwebsite\Excel\Facades\Excel;

use App\Exports\ManagerAchievementReminderExport;

class ManagerAchievementReminderMail extends Mailable
{
    public function __construct(
        public $manager,
        public $goals
    ) {}

    public function build()
    {
        $fileName = 'manager-achievement-reminder.xlsx';

        Excel::store(
            new ManagerAchievementReminderExport(
                $this->goals
            ),
            "temp/{$fileName}", 'public'
        );

        return $this
            ->subject('Team Achievement Reminder')
            ->view('emails.manager-achievement-reminder')
            ->with([
                'manager' => $this->manager,
            ])
            ->attach(
                storage_path("app/public/temp/{$fileName}")
            );
    }
}