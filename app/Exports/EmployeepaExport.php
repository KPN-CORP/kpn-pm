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
    protected $groupCompany;
    protected $location;
    protected $company;
    protected $permissionLocations;
    protected $permissionCompanies;
    protected $permissionGroupCompanies;
    //ss

    public function __construct($groupCompany, $location, $company,$permissionLocations, $permissionCompanies, $permissionGroupCompanies)
    {
        $this->groupCompany = $groupCompany;
        $this->location = $location;
        $this->company = $company;
        
        $this->permissionLocations = $permissionLocations;
        $this->permissionCompanies = $permissionCompanies;
        $this->permissionGroupCompanies = $permissionGroupCompanies;
    }

    public function collection()
    {
        $query = EmployeeAppraisal::select('employee_id', 'fullname', 'date_of_joining', 'contribution_level_code', 'unit', 'designation_name', 'job_level', 'office_area');

        $groupCompany = is_array($this->groupCompany) ? $this->groupCompany : explode(',', $this->groupCompany);
        $location = is_array($this->location) ? $this->location : explode(',', $this->location);
        $company = is_array($this->company) ? $this->company : explode(',', $this->company);

        if (!empty($this->location)) {
            $query->whereIn('work_area_code', $location);
        }

        if (!empty($this->company)) {
            $query->whereIn('contribution_level_code', $company);
            // dd($query);
        }

        if (!empty($this->groupCompany)) {
            $query->whereIn('group_company', $groupCompany);
        }

        $permissionLocations = $this->permissionLocations;
        $permissionCompanies = $this->permissionCompanies;
        $permissionGroupCompanies = $this->permissionGroupCompanies;

        $criteria = [
            'work_area_code' => $permissionLocations,
            'group_company' => $permissionGroupCompanies,
            'contribution_level_code' => $permissionCompanies,
        ];

        $query->where(function ($query) use ($criteria) {
            foreach ($criteria as $key => $value) {
                if ($value !== null && !empty($value)) {
                    $query->whereIn($key, $value);
                }
            }
        });

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
