<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Employee;
use App\Models\PaReminder;

class ReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $employee;
    public $reminder;
    public $filePath;

    public function __construct(Employee $employee, PaReminder $reminder, $filePath)
    {
        $this->employee = $employee;
        $this->reminder = $reminder;
        $this->filePath = $filePath;
    }

    public function build()
    {
        return $this->subject($this->reminder->reminder_name)
            ->view('emails.reminder')
            ->with([
                'employee' => $this->employee,
                'reminder' => $this->reminder,
            ])
            ->attach($this->filePath);
    }
}
