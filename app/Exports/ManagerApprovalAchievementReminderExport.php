<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ManagerApprovalAchievementReminderExport
implements FromCollection, WithHeadings
{
    public function __construct(
        protected $achievements
    ) {}

    public function collection()
    {
        return $this->achievements->map(function ($achievement) {

            return [
                'Employee ID' =>
                    $achievement->goal?->employee?->employee_id,

                'Employee Name' =>
                    $achievement->goal?->employee?->fullname,

                'Designation' =>
                    $achievement->goal?->employee?->designation_name,

                'Job Level' =>
                    $achievement->goal?->employee?->job_level,

                'Location' =>
                    $achievement->goal?->employee?->office_area,

                'Approval Status' =>
                    'Pending',

                'Approver ID' =>
                    $achievement?->current_approver_employee_id,

                'Approver Name' =>
                    $achievement?->approver?->fullname,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Designation',
            'Job Level',
            'Location',
            'Approval Status',
            'Current Approver ID',
            'Current Approver Name',
        ];
    }

}