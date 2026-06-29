<?php

namespace App\Exports;

use App\Models\ApprovalRequest;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class GoalExport implements FromView, WithStyles
{
    use Exportable;

    protected $groupCompany;
    protected $location;
    protected $company;
    protected $period;
    protected $admin;
    protected $permissionGroupCompanies;
    protected $permissionCompanies;
    protected $permissionLocations;
    protected $goals;

    public function __construct($period, $groupCompany, $location, $company, $admin, $permissionLocations, $permissionCompanies, $permissionGroupCompanies)
    {
        $this->groupCompany = $groupCompany;
        $this->location = $location;
        $this->company = $company;
        $this->admin = $admin;
        $this->period = $period;

        $this->permissionLocations = $permissionLocations;
        $this->permissionCompanies = $permissionCompanies;
        $this->permissionGroupCompanies = $permissionGroupCompanies;
  
    }
    

    public function view(): View
    {
        $query = ApprovalRequest::query();

        $query->where('category', 'Goals')->where('period', $this->period);

        if (!$this->admin) {
            $query->whereHas('approvalLayer', function ($query) {
                $query->where('approver_id', Auth()->user()->employee_id)
                ->orWhere('employee_id', Auth()->user()->employee_id);
            });
        }

        // Apply filters if they are provided
        if ($this->groupCompany) {
            $query->whereHas('employee', function ($query) {
                $query->where('group_company', $this->groupCompany);
            });
        }

        if ($this->location) {
            $query->whereHas('employee', function ($query) {
                $query->where('work_area_code', $this->location);
            });
        }

        if ($this->company) {
            $query->whereHas('employee', function ($query) {
                $query->where('contribution_level_code', $this->company);
            });
        }

        $permissionLocations = $this->permissionLocations;
        $permissionCompanies = $this->permissionCompanies;
        $permissionGroupCompanies = $this->permissionGroupCompanies;

        $criteria = [
            'work_area_code' => $permissionLocations,
            'group_company' => $permissionGroupCompanies,
            'contribution_level_code' => $permissionCompanies,
        ];

        // Update query to include criteria
        $query->where(function ($query) use ($criteria) {
            foreach ($criteria as $key => $value) {
                if ($value !== null && !empty($value)) {
                    $query->orWhereHas('employee', function ($subquery) use ($key, $value) {
                        $subquery->whereIn($key, $value);
                    });
                }
            }
        });

        $this->goals = $query->with(['employee', 'manager', 'goal', 'initiated', 'approvalLayer'])->get();

        return view('exports.goal', [
        'goals' => $this->goals,
        'periodMap' => [
            1 => 'Monthly', 
            2 => 'Bi-Monthly', 
            3 => 'Quarterly', 
            6 => 'Semester', 
            12 => 'Annual'
        ]
    ]);
    }

    // public function styles($sheet)
    // {
    //     $sheet->getStyle('A1:K1')->getFont()->setBold(true);

    //     // Count total rows from $data and multiply by 10
    //     $totalRows = isset($this->goals) ? count($this->goals) * 10 : 10; // Default to 10 if no data

    //     // Apply dropdown selection (Lower Better, Higher Better, Exact Value) to column D
    //     $validation = $sheet->getCell('H2')->getDataValidation(); // Start from row 2
    //     $validation->setType(DataValidation::TYPE_LIST);
    //     $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
    //     $validation->setAllowBlank(false);
    //     $validation->setShowDropDown(true);
    //     $validation->setFormula1('"Lower Better,Higher Better,Exact Value"'); // Dropdown options

    //     // Apply to all rows in column G (Adjust range as needed)
    //     for ($row = 2; $row <= $totalRows; $row++) { // Adjust 100 based on data size
    //         $sheet->getCell("H$row")->setDataValidation(clone $validation);
    //     }

    //     $sheet->getStyle('I:I')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);

    //     return [
    //         1 => [
    //             'font' => ['bold' => true],
    //             'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FFFF00']]
    //         ],
    //     ];
    // }
    
    public function styles($sheet)
{
    $sheet->getStyle('A1:T1')->getFont()->setBold(true);
    $totalRows = isset($this->goals) ? (count($this->goals) * 10) + 1 : 20;
    
    // Dropdown Review Period (Kolom I)
    $validationPeriod = $sheet->getCell('I2')->getDataValidation();
    $validationPeriod->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
    $validationPeriod->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
    $validationPeriod->setAllowBlank(false);
    $validationPeriod->setShowDropDown(true);
    // PASTIKAN INI SAMA PERSIS DENGAN ARRAY DI VIEW
    $validationPeriod->setFormula1('"Monthly,Bi-Monthly,Quarterly,Semester,Annual"');
    
    // Validasi Calculation Method (Kolom J)
    $valMethod = $sheet->getCell('J2')->getDataValidation();
    $valMethod->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
    $valMethod->setFormula1('"Average,Sum,Last,Max,Min"');
    $valMethod->setShowDropDown(true);

    // Validasi Type (Kolom L)
    $valType = $sheet->getCell('L2')->getDataValidation();
    $valType->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
    $valType->setFormula1('"Lower Better,Higher Better,Exact Value"');
    $valType->setShowDropDown(true);

    for ($row = 2; $row <= $totalRows; $row++) {
        $sheet->getCell("J$row")->setDataValidation(clone $valMethod);
        $sheet->getCell("L$row")->setDataValidation(clone $valType);
        $sheet->getCell("I$row")->setDataValidation(clone $validationPeriod);
    }

    // Format Persentase (Kolom K - Weightage)
    $sheet->getStyle("K2:K$totalRows")->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);

    return [
        1 => [
            'font' => ['bold' => true],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FFFF00']]
        ],
    ];
}
}
