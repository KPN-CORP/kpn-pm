<?php

namespace App\Exports;

use App\Models\ApprovalRequest;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;

class GoalExport implements FromView, WithStyles
{
    use Exportable;

    protected $groupCompany;
    protected $location;
    protected $company;
    protected $admin;
    protected $permissionGroupCompanies;
    protected $permissionCompanies;
    protected $permissionLocations;

    public function __construct($groupCompany, $location, $company, $admin, $permissionLocations, $permissionCompanies, $permissionGroupCompanies)
    {
        $this->groupCompany = $groupCompany;
        $this->location = $location;
        $this->company = $company;
        $this->admin = $admin;

        $this->permissionLocations = $permissionLocations;
        $this->permissionCompanies = $permissionCompanies;
        $this->permissionGroupCompanies = $permissionGroupCompanies;
  
    }

    public function view(): View
    {
        $query = ApprovalRequest::query();

        $query->where('category', 'Goals');

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

        $goals = $query->with(['employee', 'manager', 'goal', 'initiated', 'approvalLayer'])->get();

        return view('exports.goal', [
            'goals' => $goals
        ]);
    }

    public function styles($sheet)
    {
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);

        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FFFF00']]
            ],
        ];
    }
}
