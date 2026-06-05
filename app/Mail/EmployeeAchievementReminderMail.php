<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Storage;

use Maatwebsite\Excel\Facades\Excel;

use App\Exports\EmployeeAchievementReminderExport;

class EmployeeAchievementReminderMail extends Mailable
{
    public function __construct(
        public $employee,
        public $goals
    ) {}

    public function build()
    {
        // $fileName = 'employee-achievement-reminder.xlsx';

        // Excel::store(
        //     new EmployeeAchievementReminderExport(
        //         $this->goals
        //     ),
        //     "temp/{$fileName}"
        // );

        // return $this
        //     ->subject('Achievement Reminder')
        //     ->view('emails.employee-achievement-reminder')
        //     ->with([
        //         'employee' => $this->employee,
        //     ])
        //     ->attach(
        //         storage_path("app/temp/{$fileName}")
        //     );

        return $this
            ->subject('Achievement Reminder')
            ->view('emails.employee-achievement-reminder')
            ->with([
                'employee' => $this->employee,
            ]);
    }
}