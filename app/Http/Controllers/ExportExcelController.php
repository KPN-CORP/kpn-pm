<?php

namespace App\Http\Controllers;

use App\Exports\AchievementReportExport;
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
use Illuminate\Support\Facades\Log;

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
        $period = $request->export_period;

        $permissionGroupCompanies = $this->permissionGroupCompanies;
        $permissionCompanies = $this->permissionCompanies;
        $permissionLocations = $this->permissionLocations;

        $admin = 0;

        if($reportType==='Goal'){
            $goal = new GoalExport($period, $groupCompany, $location, $company, $admin, $permissionLocations, $permissionCompanies, $permissionGroupCompanies);
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
        $period = $request->export_period;

        $groupCompany = $request->export_group_company
            ? explode(',', $request->export_group_company)
            : [];

        $company = $request->export_company
            ? explode(',', $request->export_company)
            : [];

        $location = $request->export_location
            ? explode(',', $request->export_location)
            : [];

        if (!$reportType) {
            abort(400, 'Report type is required');
        }

        if (!$period) {
            abort(400, 'Period is required');
        }

        $permissionGroupCompanies = $this->permissionGroupCompanies;
        $permissionCompanies = $this->permissionCompanies;
        $permissionLocations = $this->permissionLocations;

        $admin = 1;

        return match ($reportType) {

            'Goal' => Excel::download(
                new GoalExport(
                    $period,
                    $groupCompany,
                    $location,
                    $company,
                    $admin,
                    $permissionLocations,
                    $permissionCompanies,
                    $permissionGroupCompanies
                ),
                'goals.xlsx'
            ),

            'Employee' => Excel::download(
                new EmployeeExport(
                    $groupCompany,
                    $location,
                    $company,
                    $permissionLocations,
                    $permissionCompanies,
                    $permissionGroupCompanies
                ),
                'employee.xlsx'
            ),

            'EmployeePA' => Excel::download(
                new EmployeepaExport(
                    $groupCompany,
                    $location,
                    $company,
                    $permissionLocations,
                    $permissionCompanies,
                    $permissionGroupCompanies
                ),
                'employeePA.xlsx'
            ),
            
            'Achievement' => $this->queueAchievementExport(
                $groupCompany,
                $location,
                $company,
                $period,
                $permissionLocations,
                $permissionCompanies,
                $permissionGroupCompanies
            ),

            default => abort(400, 'Invalid report type'),
        };
    }

    private function queueAchievementExport(
        $groupCompany,
        $location,
        $company,
        $period,
        $permissionLocations,
        $permissionCompanies,
        $permissionGroupCompanies
    ) {
        $fileName = 'exports/achievement_' . now()->format('Ymd_His') . '.xlsx';

        Log::info('🚀 Queue Export Triggered', [
        'file' => $fileName,
        'groupCompany' => $groupCompany,
        'location' => $location,
        'company' => $company,
    ]);

        Excel::queue(
            new AchievementReportExport(
                $groupCompany,
                $location,
                $company,
                $period,
                $permissionLocations,
                $permissionCompanies,
                $permissionGroupCompanies
            ),
            $fileName,
            'public'
        );

            Log::info('📥 Queue Job Dispatched');


        return response()->json([
            'status' => 'queued',
            'file' => $fileName
        ]);
    }

    public function notInitiated(Request $request) 
    {
        $employee_id = $request->employee_id;
        $period = $request->filterYear;

        $data = new NotInitiatedExport($employee_id, $period);
        return Excel::download($data, 'import_team_goals.xlsx');

    }

    public function initiated(Request $request) 
    {
        $employee_id = $request->employee_id;
        $period = $request->filterYear;

        $data = new InitiatedExport($employee_id, $period);
        return Excel::download($data, 'employee_initiated_goals.xlsx');

    }

    public function exportreportemp() 
    {
        return Excel::download(new EmployeeDetailExport, 'employees_detail.xlsx');
    }
}
