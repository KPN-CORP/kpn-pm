<?php
namespace App\Exports;

use App\Models\EmployeeAppraisal;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeepaExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $permissionLocations;
    protected $permissionCompanies;
    protected $permissionGroupCompanies;

    public function __construct($permissionLocations, $permissionCompanies, $permissionGroupCompanies)
    {
        $this->permissionLocations = $permissionLocations;
        $this->permissionCompanies = $permissionCompanies;
        $this->permissionGroupCompanies = $permissionGroupCompanies;
    }

    public function collection()
    {
        $query = EmployeeAppraisal::select('employee_id', 'fullname', 'date_of_joining', 'contribution_level_code', 'unit', 'designation_name', 'job_level', 'office_area');

        if (!empty($this->permissionLocations)) {
            $query->whereIn('work_area_code', $this->permissionLocations);
        }

        if (!empty($this->permissionCompanies)) {
            $query->whereIn('contribution_level_code', $this->permissionCompanies);
        }

        if (!empty($this->permissionGroupCompanies)) {
            $query->whereIn('group_company', $this->permissionGroupCompanies);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Employee ID',
            'Full Name',
            'Join Date',
            'Company',
            'Unit',
            'Designation',
            'Job Level',
            'Office Location',
        ];
    }

    public function map($row): array
    {
        static $no = 1; // Initialize a static variable to increment the row number

        return [
            $no++, // Row number
            $row->employee_id,
            $row->fullname,
            $row->date_of_joining,
            $row->contribution_level_code,
            $row->unit,
            $row->designation_name,
            $row->job_level,
            $row->office_area,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // Add borders for each cell in the table range
        $sheet->getStyle("A1:{$highestColumn}{$highestRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '00000000'],
                ],
            ],
        ]);

        return [
            // Bold the heading row
            1 => ['font' => ['bold' => true]],
        ];
    }
}
