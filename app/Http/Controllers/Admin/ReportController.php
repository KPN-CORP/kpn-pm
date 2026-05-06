<?php

namespace App\Http\Controllers\Admin;

use App\Exports\GoalExport;
use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\ApprovalLayer;
use App\Models\ApprovalRequest;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeAppraisal;
use App\Models\Goal;
use App\Models\Location;
use App\Models\Report;
use App\Models\Schedule;
use App\Services\KPIAchievementService;
use App\Services\KPIService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    protected $groupCompanies;
    protected $companies;
    protected $locations;
    protected $permissionGroupCompanies;
    protected $permissionCompanies;
    protected $permissionLocations;
    protected $roles;
    protected $category;
    protected $path;
    protected $kpiService;
    
    public function __construct(KPIService $kpiService)
    {
        $this->category = 'Goals';
        $this->roles = Auth::user()->roles;
        $this->path = base_path('resources/goal.json');

        $this->kpiService = $kpiService;
        
        $restrictionData = [];
        if(!is_null($this->roles)){
            $restrictionData = json_decode($this->roles->first()->restriction, true);
        }
        
        $this->permissionGroupCompanies = $restrictionData['group_company'] ?? [];
        $this->permissionCompanies = $restrictionData['contribution_level_code'] ?? [];
        $this->permissionLocations = $restrictionData['work_area_code'] ?? [];

        $groupCompanyCodes = $restrictionData['group_company'] ?? [];

        $this->groupCompanies = Location::select('company_name')
            ->when(!empty($groupCompanyCodes), function ($query) use ($groupCompanyCodes) {
                return $query->whereIn('company_name', $groupCompanyCodes);
            })
            ->orderBy('company_name')->distinct()->pluck('company_name');

        $workAreaCodes = $restrictionData['work_area_code'] ?? [];

        $this->locations = Location::select('company_name', 'area', 'work_area')
            ->when(!empty($workAreaCodes) || !empty($groupCompanyCodes), function ($query) use ($workAreaCodes, $groupCompanyCodes) {
                return $query->where(function ($query) use ($workAreaCodes, $groupCompanyCodes) {
                    if (!empty($workAreaCodes)) {
                        $query->whereIn('work_area', $workAreaCodes);
                    }
                    if (!empty($groupCompanyCodes)) {
                        $query->orWhereIn('company_name', $groupCompanyCodes);
                    }
                });
            })
            ->orderBy('area')
            ->get();

        $companyCodes = $restrictionData['contribution_level_code'] ?? [];

        $this->companies = Company::select('contribution_level', 'contribution_level_code')
            ->when(!empty($companyCodes), function ($query) use ($companyCodes) {
                return $query->whereIn('contribution_level_code', $companyCodes);
            })
            ->orderBy('contribution_level_code')->get();
    }
    
    function index(Request $request) {
        $parentLink = 'Admin';
        $link = __('Report');

        $locations = Location::select('company_name', 'area', 'work_area')->orderBy('area')->get();
        $groupCompanies = Location::select('company_name')
        ->orderBy('company_name')
        ->distinct()
        ->pluck('company_name');
        $companies = Company::select('contribution_level', 'contribution_level_code')->orderBy('contribution_level_code')->get();

        $period = date('Y');

        $selectYear = Schedule::withTrashed()
        ->where('schedule_periode', '!=', $period)
        ->selectRaw('DISTINCT schedule_periode as period')
        ->orderBy('period', 'ASC')
        ->get();

        return view('reports-admin.app', compact('locations', 'companies', 'groupCompanies', 'link', 'parentLink', 'selectYear', 'period'));
    }

    public function changesGroupCompany(Request $request)
    {
        $selectedGroupCompany = $request->input('groupCompany');

        // Initialize query to fetch locations
        $locationsQuery = Location::query();

        // Check if a specific group company is selected
        if ($selectedGroupCompany) {
            // Filter locations by the selected group company
            $locationsQuery->where('company_name', $selectedGroupCompany);
        }

        // Fetch locations based on the modified query
        $locations = $locationsQuery->get();

        // Return JSON response with locations
        return response()->json([
            'locations' => $locations,
        ]);
    }
    
    // public function getReportContent($report_type)
    public function getReportContent(Request $request)
    {
        $user = Auth::user();
        $employeeId = $user->employee_id;
        $report_type = $request->report_type;
        $period = $request->input('filterYear');
        $group_company = $request->input('group_company', []);
        $location = $request->input('location', []);
        $company = $request->input('company', []);
        // $report_type = $report_type;
        // $period = 2026;
        // $group_company = [];
        // $location = [];
        // $company = [];
        $permissionLocations = $this->permissionLocations;
        $permissionCompanies = $this->permissionCompanies;
        $permissionGroupCompanies = $this->permissionGroupCompanies;

        $filters = compact('period', 'report_type', 'group_company', 'location', 'company');

        // Start building the query
        if ($report_type === 'Goal') {
            $query = ApprovalRequest::with(['employee', 'manager', 'goal', 'initiated'])->where('category', $this->category)->whereHas('employee')->whereHas('manager')->whereHas('initiated');

            $criteria = [
                'work_area_code' => $permissionLocations,
                'group_company' => $permissionGroupCompanies,
                'contribution_level_code' => $permissionCompanies,
            ];

            $query->where(function ($query) use ($criteria) {
                foreach ($criteria as $key => $value) {
                    if ($value !== null && !empty($value)) {
                        $query->orWhereHas('employee', function ($subquery) use ($key, $value) {
                            $subquery->whereIn($key, $value);
                        });
                    }
                }
            });

            if (!empty($group_company)) {
                $query->whereHas('employee', function ($query) use ($group_company) {
                    $query->whereIn('group_company', $group_company)->orderBy('fullname');
                });
            }
            if (!empty($location)) {
                $query->whereHas('employee', function ($query) use ($location) {
                    $query->whereIn('work_area_code', $location);
                });
            }
            if (!empty($company)) {
                $query->whereHas('employee', function ($query) use ($company) {
                    $query->whereIn('contribution_level_code', $company);
                });
            }
            if (!empty($period)) {
                $query->where('period', $period);
            } else {
                $query->where('period', date('Y'));
            }

            // Apply employee filters
            $data = $query->get();

            $data->map(function($item) {
                // Format created_at
                $createdDate = Carbon::parse($item->created_at);

                    $item->formatted_created_at = $createdDate->format('d M Y g:ia');
    
                // Format updated_at
                $updatedDate = Carbon::parse($item->updated_at);

                    $item->formatted_updated_at = $updatedDate->format('d M Y g:ia');

                // Determine name and approval layer
                if ($item->sendback_to == $item->employee->employee_id) {
                    $item->name = $item->employee->fullname . ' (' . $item->employee->employee_id . ')';
                    $item->approvalLayer = '';
                } else {
                    $item->name = $item->manager->fullname . ' (' . $item->manager->employee_id . ')';
                    $item->approvalLayer = ApprovalLayer::where('employee_id', $item->employee_id)
                                                        ->where('approver_id', $item->current_approval_id)
                                                        ->value('layer');
                }

                return $item;
            });

            $route = 'reports-admin.goal';
        } elseif ($report_type === 'Employee') {
            $query = Employee::query()->orderBy('fullname')->whereNull('deleted_at'); // Start with Employee model

            if (!empty($group_company)) {
                    $query->whereIn('group_company', $group_company)->orderBy('fullname');
            }
            if (!empty($location)) {
                    $query->whereIn('work_area_code', $location);
            }
            if (!empty($company)) {
                    $query->whereIn('contribution_level_code', $company);
            }

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

            $data = $query->get();
            foreach ($data as $employee) {
                $employee->access_menu = json_decode($employee->access_menu, true);
            }
            $route = 'reports-admin.employee';
        } elseif ($report_type === 'EmployeePA') {
            $query = EmployeeAppraisal::query()->orderBy('fullname'); // Start with Employee model

            if (!empty($group_company)) {
                    $query->whereIn('group_company', $group_company)->orderBy('fullname');
            }
            if (!empty($location)) {
                    $query->whereIn('work_area_code', $location);
            }
            if (!empty($company)) {
                    $query->whereIn('contribution_level_code', $company);
            }

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

            $designations = Designation::select('designation_name', 'job_code')
            ->orderBy('parent_company_id', 'asc')
            ->orderBy('designation_name', 'asc')
            ->orderBy('job_code', 'asc')
            ->groupBy('job_code', 'designation_name', 'parent_company_id')
            ->get();
            $departments = Department::select('department_name')
            ->orderBy('department_name', 'asc')
            ->groupBy('department_name')
            ->get();
            $companies = Company::orderBy('contribution_level_code', 'asc')->get();
            $locations = Location::orderBy('area', 'asc')->get();

            $jobLevel = EmployeeAppraisal::select('job_level')->distinct()->orderBy('job_level', 'asc')->get();

            $data = $query->get();
            foreach ($data as $employee) {
                $employee->access_menu = json_decode($employee->access_menu, true);
            }
            $route = 'reports-admin.employeepa';

            $link = __('Report');

            return view($route, compact('data', 'link', 'filters', 'designations','departments','companies','locations', 'jobLevel'));
        } elseif ($report_type === 'Achievement') {
            
            $query = Goal::with([
                'employee',
                'achievement.approver'
            ])->whereHas('achievement.approver');

            $query->whereHas('employee', function ($q) use ($group_company, $location, $company) {
                if (!empty($group_company)) {
                    $q->whereIn('group_company', $group_company);
                }

                if (!empty($location)) {
                    $q->whereIn('work_area_code', $location);
                }

                if (!empty($company)) {
                    $q->whereIn('contribution_level_code', $company);
                }
            });

            $query->where('period', $period ?? date('Y'));

            $data = $query->get();

            $approvalLayers = ApprovalLayer::whereIn(
                'employee_id',
                $data->pluck('employee_id')->filter()->unique()
            )
            ->where('layer', 1)
            ->get()
            ->keyBy(function ($item) {
                return $item->employee_id . '-' . $item->approver_id;
            });

            $data->map(function($item) use ($approvalLayers) {

                $formData = json_decode($item->form_data, true) ?? [];

                // ambil achievement KPI (service kamu)
                $achievementData = KPIAchievementService::getByGoal($item->id) ?? [];
                $isEmptyAchievement = empty($achievementData);

                foreach ($formData as &$kpi) {

                    $kpiId = $kpi['kpi_id'] ?? null;

                    // inject monthly achievement
                    $kpi['ach'] = $kpiId && isset($achievementData[$kpiId]['ach'])
                        ? $achievementData[$kpiId]['ach']
                        : array_fill(1, 12, null);

                    $kpi['attachment'] = $kpiId && isset($achievementData[$kpiId]['attachment'])
                        ? $achievementData[$kpiId]['attachment']
                        : array_fill(1, 12, null);

                    $kpi['approval_status'] = $kpiId && isset($achievementData[$kpiId]['approval_status'])
                        ? $achievementData[$kpiId]['approval_status']
                        : array_fill(1, 12, null);

                    // ========================
                    // HITUNG ACTUAL & ACHIEVEMENT
                    // ========================
                    $values = collect($kpi['ach'])
                        ->filter(fn($v) => $v !== null && $v !== '')
                        ->values()
                        ->toArray();

                    $actual = $this->kpiService->aggregate(
                        $kpi['calculation_method'] ?? 'last',
                        $values
                    );

                    $achievementValue = $isEmptyAchievement
                        ? 0
                        : $this->kpiService->achievement(
                            $actual,
                            (float)($kpi['target'] ?? 0),
                            $kpi['type'] ?? 'Higher Better'
                        );
                    
                    $options = [];

                    if (File::exists($this->path)) {
                        $options = json_decode(File::get($this->path), true) ?? [];
                    }

                    $reviewPeriodMap = collect($options['Review Period'] ?? [])
                        ->flatten(1)
                        ->pluck('label', 'value')
                        ->toArray();

                    $calculationMethodMap = collect($options['Calculation Method'] ?? [])
                        ->flatten(1)
                        ->pluck('label', 'value')
                        ->toArray();

                    $kpi['actual'] = round($actual, 2);
                    $kpi['achievement'] = round($achievementValue, 2);

                    $kpi['review_period_label'] = $reviewPeriodMap[$kpi['review_period'] ?? ''] ?? '-';
                    $kpi['calculation_method_label'] = $calculationMethodMap[$kpi['calculation_method'] ?? ''] ?? '-';
                }

                // inject ke item
                $item->formData = $formData;

                $achievement = $item->achievement;

                if ($achievement) {

                    $achievement->formatted_created_at = Carbon::parse($achievement->created_at)->format('d M Y g:ia');
                    $achievement->formatted_updated_at = Carbon::parse($achievement->updated_at)->format('d M Y g:ia');

                    $approver = optional($achievement->approver);

                    $achievement->name = $approver->fullname
                        ? $approver->fullname . ' (' . $approver->employee_id . ')'
                        : '-';

                    $key = $item->employee_id . '-' . $achievement->current_approver_employee_id;

                    $layerData = $approvalLayers->get($key);

                    $achievement->approvalLayer = $layerData->layer ?? null;

                    // 🔥 RE-ASSIGN BALIK
                    $item->achievement = $achievement;

                } else {

                    // 🔥 JANGAN SET KE RELATION LANGSUNG
                    $item->setRelation('achievement', (object)[
                        'name' => '-',
                        'approvalLayer' => null,
                        'formatted_created_at' => '-',
                        'formatted_updated_at' => '-',
                        'approval_status' => '-',
                    ]);
                }

                return $item;
            });

            $route = 'reports-admin.achievement';
        }else {
            $data = collect(); // Empty collection for unknown report types
            $route = 'reports-admin.empty';
        }

        $link = __('Report');

        return view($route, compact('data', 'link', 'filters'));
    }


    public function generateReportExcel(Request $request)
    {
        // Logika untuk generate report
        
        $reportType = $request->export_report_type;
        $groupCompany = $request->export_group_company;
        $company = $request->export_company;
        $location = $request->export_location;
        $period = $request->export_period;
        $permissionLocations = $this->permissionLocations;
        $permissionCompanies = $this->permissionCompanies;
        $permissionGroupCompanies = $this->permissionGroupCompanies;
        $admin = 1;

        $directory = 'report/excel'; // Direktori tempat file akan disimpan
        $date = now()->format('dmY');
        $reportName = 'Nama Report';
        $fileName = $reportType.'_'.$date.'.xlsx'; // Nama file yang akan disimpan

        if($reportType==='Goal'){
            $export = new GoalExport($period, $groupCompany, $location, $company, $admin, $permissionLocations, $permissionCompanies, $permissionGroupCompanies);
            $fileContent = Excel::download($export, $fileName)->getFile();
        }
        return false;

        // Mengecek dan membuat direktori jika belum ada
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory, 0755, true); // Buat direktori dengan izin 0755 (opsional)
        }

        // Menyimpan file ke dalam direktori yang sudah ada
        Storage::disk('public')->put($directory . '/' . $fileName, $fileContent);

        // Simpan informasi report ke dalam database
        $filePath = $directory . '/' . $fileName;
        $report = new Report();
        $report->name = $reportName;
        $report->file_path = $filePath;
        $report->save();

        return redirect()->back()->with('success', 'Report berhasil di-generate dan disimpan.');
    }

}
