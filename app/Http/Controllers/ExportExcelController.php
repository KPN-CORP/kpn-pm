<?php

namespace App\Http\Controllers;

use App\Exports\EmployeeDetailExport;
use App\Exports\EmployeeExport;
use App\Exports\GoalExport;
use App\Exports\InitiatedExport;
use App\Exports\NotInitiatedExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EmployeepaExport;
use App\Services\AppService;
use Illuminate\Support\Facades\Auth;

class ExportExcelController extends Controller
{
    protected $permissionGroupCompanies;
    protected $permissionCompanies;
    protected $permissionLocations;
    protected $roles;
    protected $appService;
    
    public function __construct(AppService $appService)
    {
        $this->roles = Auth::user()->roles;
        $this->appService = $appService;
        
        $restrictionData = [];

        if(!$this->roles->isEmpty()){
            $restrictionData = json_decode($this->roles->first()->restriction, true);
        }
        
        $this->permissionGroupCompanies = $restrictionData['group_company'] ?? [];
        $this->permissionCompanies = $restrictionData['contribution_level_code'] ?? [];
        $this->permissionLocations = $restrictionData['work_area_code'] ?? [];

    }

    public function export(Request $request) 
    {
        $reportType = $request->export_report_type;
        $groupCompany = $request->export_group_company;
        $company = $request->export_company;
        $location = $request->export_location;

        $permissionGroupCompanies = $this->permissionGroupCompanies;
        $permissionCompanies = $this->permissionCompanies;
        $permissionLocations = $this->permissionLocations;

        $admin = 0;

        if($reportType==='Goal'){
            $goal = new GoalExport($groupCompany, $location, $company, $admin, $permissionLocations, $permissionCompanies, $permissionGroupCompanies);
            return Excel::download($goal, 'goals.xlsx');
        }
        if($reportType==='Employee'){
            $employee = new EmployeeExport($groupCompany, $location, $company, $permissionLocations, $permissionCompanies, $permissionGroupCompanies);
            return Excel::download($employee, 'employee.xlsx');
        }
        return;

    }

    public function exportAdmin(Request $request) 
    {
        $reportType = $request->export_report_type;
        $groupCompany = $request->export_group_company;
        $company = $request->export_company;
        $location = $request->export_location;

        $permissionGroupCompanies = $this->permissionGroupCompanies;
        $permissionCompanies = $this->permissionCompanies;
        $permissionLocations = $this->permissionLocations;
        
        $admin = 1;

        if($reportType==='Goal'){
            $goal = new GoalExport($groupCompany, $location, $company, $admin, $permissionLocations, $permissionCompanies, $permissionGroupCompanies);
            return Excel::download($goal, 'goals.xlsx');
        }
        if($reportType==='Employee'){
            $employee = new EmployeeExport($groupCompany, $location, $company, $permissionLocations, $permissionCompanies, $permissionGroupCompanies);
            return Excel::download($employee, 'employee.xlsx');
        }
        if($reportType==='EmployeePA'){
            $employee = new EmployeepaExport($groupCompany, $location, $company, $permissionLocations, $permissionCompanies, $permissionGroupCompanies);
            return Excel::download($employee, 'employeePA.xlsx');
        }
        return;

    }

    public function notInitiated(Request $request) 
    {
        $employee_id = $request->employee_id;

        $data = new NotInitiatedExport($employee_id, $this->appService);
        return Excel::download($data, 'employee_not_initiated_goals.xlsx');

    }

    public function initiated(Request $request) 
    {
        $employee_id = $request->employee_id;

        $data = new InitiatedExport($employee_id);
        return Excel::download($data, 'employee_initiated_goals.xlsx');

    }

    public function exportreportemp() 
    {
        return Excel::download(new EmployeeDetailExport, 'employees_detail.xlsx');
    }
}
