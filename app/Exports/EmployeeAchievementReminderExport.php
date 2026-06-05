<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EmployeeAchievementReminderExport implements FromCollection, WithHeadings
{
    protected Collection $goals;

    public function __construct($goals)
    {
        $this->goals = collect($goals);
    }

    public function collection()
    {
        return $this->goals->flatMap(function ($goal) {

            $formData = is_array($goal->form_data)
                ? $goal->form_data
                : json_decode($goal->form_data, true);

            if (empty($formData)) {
                return collect();
            }

            return collect($formData)->map(function ($kpi) use ($goal) {

                $achievements = $goal->achievementList
                    ->where('kpi_id', $kpi['kpi_id']);

                if ($achievements->isEmpty()) {
                    return null;
                }

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
                        $achievements->first()?->current_approver_employee_id,

                    'Approver Name' =>
                        $achievements->first()?->approver->fullname,
                ];

            })->filter();
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