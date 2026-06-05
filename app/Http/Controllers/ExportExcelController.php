<?php

namespace App\Http\Controllers;

use App\Exports\AchievementExport;
use App\Exports\AchievementReportExport;
use App\Exports\EmployeeDetailExport;
use App\Exports\EmployeeExport;
use App\Exports\GoalExport;
use App\Exports\InitiatedExport;
use App\Exports\NotInitiatedExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EmployeepaExport;
use App\Models\Goal;
use App\Models\KPIAchievement;
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

    public function achievement(Request $request)
    {
        try {

            $request->validate(
                [
                    'employee_id' => 'required|array|min:1',
                    'employee_id.*' => 'required|string',
                    'filterYear' => 'required'
                ],
                [
                    'employee_id.required' =>
                        'No Employees have approved Goals data.',

                    'employee_id.array' =>
                        'Data is invalid.',

                    'employee_id.min' =>
                        'No employee selected.',

                    'employee_id.*.required' =>
                        'No Employees have approved Goals data.',

                    'filterYear.required' =>
                        'Please select the Period.'
                ]
            );

            $employeeId = $request->employee_id;
            $period = $request->filterYear;

            $goals = Goal::query()
                ->with('employee')
                ->where('period', $period)
                ->where('form_status', 'Approved')
                ->whereIn('employee_id', $employeeId)
                ->get();

            if ($goals->isEmpty()) {

                return back()->with(
                    'error',
                    [
                        'message' =>
                            'No approved goals found'
                    ]
                );
            }

            $data = collect();

            foreach ($goals as $goal) {

                $formData = json_decode(
                    $goal->form_data,
                    true
                );

                if (
                    !$formData ||
                    !is_array($formData)
                ) {

                    throw new \Exception(
                        sprintf(
                            'Invalid goal data for employee %s',
                            $goal->employee_id
                        )
                    );
                }

                foreach ($formData as $kpi) {

                    $kpiId =
                        $kpi['kpi_id']
                        ?? null;

                    if (!$kpiId) {
                        continue;
                    }

                    $achievements =
                        KPIAchievement::query()
                        ->where(
                            'goal_id',
                            $goal->id
                        )
                        ->where(
                            'kpi_id',
                            $kpiId
                        )
                        ->get()
                        ->keyBy('month');

                    $data->push(
                        (object)[

                            'employee_id' =>
                                $goal->employee_id,

                            'employee' =>
                                $goal->employee,

                            'kpi' =>
                                $kpi['kpi']
                                ?? null,

                            'description' =>
                                $kpi['description']
                                ?? null,

                            'target' =>
                                $kpi['target']
                                ?? null,

                            'uom' =>
                                $kpi['uom']
                                ?? null,

                            'custom_uom' =>
                                $kpi['custom_uom']
                                ?? null,

                            'weightage' =>
                                $kpi['weightage']
                                ?? null,

                            'type' =>
                                $kpi['type']
                                ?? null,

                            'review_period' =>
                                $kpi['review_period']
                                ?? null,

                            'calculation_method' =>
                                $kpi['calculation_method']
                                ?? null,

                            'jan' =>
                                $achievements[1]
                                    ->value
                                ?? null,

                            'feb' =>
                                $achievements[2]
                                    ->value
                                ?? null,

                            'mar' =>
                                $achievements[3]
                                    ->value
                                ?? null,

                            'apr' =>
                                $achievements[4]
                                    ->value
                                ?? null,

                            'may' =>
                                $achievements[5]
                                    ->value
                                ?? null,

                            'jun' =>
                                $achievements[6]
                                    ->value
                                ?? null,

                            'jul' =>
                                $achievements[7]
                                    ->value
                                ?? null,

                            'aug' =>
                                $achievements[8]
                                    ->value
                                ?? null,

                            'sep' =>
                                $achievements[9]
                                    ->value
                                ?? null,

                            'oct' =>
                                $achievements[10]
                                    ->value
                                ?? null,

                            'nov' =>
                                $achievements[11]
                                    ->value
                                ?? null,

                            'dec' =>
                                $achievements[12]
                                    ->value
                                ?? null,
                        ]
                    );
                }
            }

            if ($data->isEmpty()) {

                return back()->with(
                    'error',
                    [
                        'message' =>
                            'No achievement data found'
                    ]
                );
            }

            return Excel::download(
                new AchievementExport(
                    $data
                ),
                'team_achievement.xlsx'
            );

        } catch (\Throwable $e) {

            Log::error(
                'Achievement Export Error',
                [
                    'message' =>
                        $e->getMessage(),

                    'line' =>
                        $e->getLine(),

                    'file' =>
                        $e->getFile(),
                ]
            );

            return back()->with(
                'error',
                [
                    'message' =>
                        $e->getMessage()
                ]
            );
        }
    }

    public function myAchievement(Request $request)
    {
        $employeeId = $request->employee_id; // current approver employee id
        $period = $request->filterYear;

        $goals = Goal::query()
            ->with('employee')
            ->where('employee_id', $employeeId)
            ->where('period', $period)
            ->where('form_status', 'Approved')
            ->get();

        $data = collect();

        foreach ($goals as $goal) {

            $formData = is_array($goal->form_data) ? $goal->form_data : json_decode($goal->form_data, true);

            if (!$formData) continue;

            foreach ($formData as $kpi) {

                $kpiId = $kpi['kpi_id'] ?? null;

                if (!$kpiId) continue;

                $achievements = KPIAchievement::query()
                    ->where('goal_id', $goal->id)
                    ->where('kpi_id', $kpiId)
                    ->get()
                    ->keyBy('month');

                // if ($achievements->isEmpty()) {
                //     continue;
                // }

                $data->push((object)[

                    'employee_id' => $goal->employee_id,

                    'employee' => $goal->employee,

                    'kpi' => $kpi['kpi'] ?? null,

                    'description' => $kpi['description'] ?? null,

                    'target' => $kpi['target'] ?? null,

                    'uom' => $kpi['uom'] ?? null,

                    'custom_uom' => $kpi['custom_uom'] ?? null,

                    'weightage' => $kpi['weightage'] ?? null,

                    'type' => $kpi['type'] ?? null,

                    'review_period' => $kpi['review_period'] ?? null,

                    'calculation_method' => $kpi['calculation_method'] ?? null,

                    'jan' => $achievements[1]->value ?? null,
                    'feb' => $achievements[2]->value ?? null,
                    'mar' => $achievements[3]->value ?? null,
                    'apr' => $achievements[4]->value ?? null,
                    'may' => $achievements[5]->value ?? null,
                    'jun' => $achievements[6]->value ?? null,
                    'jul' => $achievements[7]->value ?? null,
                    'aug' => $achievements[8]->value ?? null,
                    'sep' => $achievements[9]->value ?? null,
                    'oct' => $achievements[10]->value ?? null,
                    'nov' => $achievements[11]->value ?? null,
                    'dec' => $achievements[12]->value ?? null,

                ]);
            }
        }

        return Excel::download(
            new AchievementExport($data),
            'my_achievement.xlsx'
        );
    }

    public function exportreportemp() 
    {
        return Excel::download(new EmployeeDetailExport, 'employees_detail.xlsx');
    }
}
