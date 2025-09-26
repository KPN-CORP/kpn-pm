<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Employee;
use App\Models\PaReminder;
use Maatwebsite\Excel\Concerns\FromArray;

class ReminderExport implements FromArray
{
    protected $employee;
    protected $reminder;

    public function __construct(Employee $employee, PaReminder $reminder)
    {
        $this->employee = $employee;
        $this->reminder = $reminder;
    }

    public function array(): array
    {
        return [
            ['Employee Name', 'Email', 'Reminder'],
            [$this->employee->name, $this->employee->email, $this->reminder->reminder_name],
        ];
    }
}