<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ManagerAchievementReminderExport implements FromCollection, WithHeadings
{
     protected Collection $goals;

    public function __construct($goals)
    {
        $this->goals = collect($goals);
    }

    public function collection()
    {
        return $this->goals->map(function ($goal) {

            $firstAchievement = $goal->achievementList->first();

            return [
                'Employee ID' =>
                    $goal->employee?->employee_id,

                'Employee Name' =>
                    $goal->employee?->fullname,

                'Designation' =>
                    $goal->employee?->designation_name,

                'Job Level' =>
                    $goal->employee?->job_level,

                'Location' =>
                    $goal->employee?->office_area,

                'Approval Status' =>
                    'Pending',

                'Approver ID' =>
                    $firstAchievement?->current_approver_employee_id,

                'Approver Name' =>
                    $firstAchievement?->approver?->fullname,
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