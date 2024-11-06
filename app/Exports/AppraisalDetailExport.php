<?php

namespace App\Exports;

use App\Models\AppraisalContributor;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AppraisalDetailExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $appraisalData;

    public function __construct($appraisalData)
    {
        $this->appraisalData = $appraisalData;
    }

    public function collection()
    {
        return $this->appraisalData;
    }

    public function map($row): array
    {
        return [
            // Map your columns here, matching your DataTable columns
            // Exclude the last column (Details) as per your configuration
            'employee_id' => $row->employee_id,
            'employee_name' => $row->employee->name ?? '',
            // Add all other columns that match your DataTable
        ];
    }

    public function headings(): array
    {
        return [
            // Match your DataTable headers
            'Employee ID',
            'Employee Name',
            // Add all other headers that match your DataTable
        ];
    }
}