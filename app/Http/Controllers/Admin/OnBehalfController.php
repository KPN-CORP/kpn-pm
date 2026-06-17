<?php

namespace App\Http\Controllers\Admin;

use App\Exports\UserExport;
use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\ApprovalLayer;
use App\Models\ApprovalLayerAppraisal;
use App\Models\ApprovalRequest;
use App\Models\ApprovalSnapshots;
use App\Models\Calibration;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeAppraisal;
use App\Models\FormGroupAppraisal;
use App\Models\Goal;
use App\Models\KpiUnits;
use App\Models\Location;
use App\Models\MasterRating;
use App\Models\User;
use App\Services\AppService;
use App\Services\KPIAchievementService;
use App\Services\KPIService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use RealRashid\SweetAlert\Facades\Alert;
use stdClass;

class OnBehalfController extends Controller
{
    protected $groupCompanies;
    protected $companies;
    protected $locations;
    protected $permissionGroupCompanies;
    protected $permissionCompanies;
    protected $permissionLocations;
    protected $roles;
    protected $category;
    protected $appService;
    protected $user;
    protected $kpiService;
    protected $path;
    
    public function __construct(AppService $appService, KPIService $kpiService)
    {
        $this->kpiService = $kpiService;
        $this->category = 'Goals';
        $this->appService = $appService;
        $this->roles = Auth::user()->roles;
        $this->user = Auth::user();
        $this->path = base_path('resources/goal.json');
        
        $restrictionData = [];
        if(!is_null($this->roles)){
            $restrictionData = json_decode($this->roles->first()->restriction, true);
        }
        
        $this->permissionGroupCompanies = $restrictionData['group_company'] ?? [];
        $this->permissionCompanies = $restrictionData['contribution_level_code'] ?? [];
        $this->permissionLocations = $restrictionData['work_area_code'] ?? [];

        $groupCompanyCodes = $restrictionData['group_company'] ?? [];

        $this->groupCompanies = Employee::select('group_company')
            ->when(!empty($groupCompanyCodes), function ($query) use ($groupCompanyCodes) {
                return $query->whereIn('group_company', $groupCompanyCodes);
            })->orderBy('group_company')->distinct()->pluck('group_company');

        $workAreaCodes = $restrictionData['work_area_code'] ?? [];

        $this->locations = Employee::select('office_area', 'work_area_code', 'group_company')
            ->when(!empty($workAreaCodes) || !empty($groupCompanyCodes), function ($query) use ($workAreaCodes, $groupCompanyCodes) {
                return $query->where(function ($query) use ($workAreaCodes, $groupCompanyCodes) {
                    if (!empty($workAreaCodes)) {
                        $query->whereIn('work_area_code', $workAreaCodes);
                    }
                    if (!empty($groupCompanyCodes)) {
                        $query->orWhereIn('group_company', $groupCompanyCodes);
                    }
                });
            })
            ->orderBy('work_area_code')->distinct()->get();

        $companyCodes = $restrictionData['contribution_level_code'] ?? [];

        $this->companies = Company::select('contribution_level', 'contribution_level_code')
            ->when(!empty($companyCodes), function ($query) use ($companyCodes) {
                return $query->whereIn('contribution_level_code', $companyCodes);
            })
            ->orderBy('contribution_level_code')->get();
    }
    
    function index() {

        $parentLink = 'Admin';
        $link = 'On Behalf';

        $locations = $this->locations;
        $companies = $this->companies;
        $groupCompanies = $this->groupCompanies;

        return view('pages.onbehalfs.app', compact('link', 'parentLink', 'locations', 'companies', 'groupCompanies'));
       
    }

    public function getOnBehalfContent(Request $request)
    {
        $category = $request->input('category');
        // $category = $id;

        $filterCategory = $request->input('filter_category');
        // $filterCategory = $category;

        $permissionLocations = $this->permissionLocations;
        $permissionCompanies = $this->permissionCompanies;
        $permissionGroupCompanies = $this->permissionGroupCompanies;

        $group_company = $request->input('group_company', []);
        $location = $request->input('location', []);
        $company = $request->input('company', []);

        $filters = compact('group_company', 'location', 'company');

        $parentLink = 'Admin';
        $link = 'On Behalf';

        $data = [];
        
        if ($filterCategory == 'Goals') {

            $period = $this->appService->goalPeriod();

            // Mengambil data pengajuan berdasarkan employee_id atau manager_id
            $datas = ApprovalRequest::with(['employee', 'goal', 'updatedBy', 'initiated', 'approval' => function ($query) {
                $query->with('approverName'); // Load nested relationship
            }])->where('category', $filterCategory)->where('period', $period)->whereHas('employee')->whereHas('manager');
            
            $criteria = [
                'work_area_code' => $permissionLocations,
                'group_company' => $permissionGroupCompanies,
                'contribution_level_code' => $permissionCompanies,
            ];

            $datas->where(function ($query) use ($criteria) {
                foreach ($criteria as $key => $value) {
                    if ($value !== null && !empty($value)) {
                        $query->orWhereHas('employee', function ($subquery) use ($key, $value) {
                            $subquery->whereIn($key, $value);
                        });
                    }
                }
            });
            
            // Apply filters based on request parameters
            if (!empty($group_company)) {
                $datas->whereHas('employee', function ($datas) use ($group_company) {
                    $datas->whereIn('group_company', $group_company);
                });
            }
            if (!empty($location)) {
                $datas->whereHas('employee', function ($datas) use ($location) {
                    $datas->whereIn('work_area_code', $location);
                });
            }
    
            if (!empty($company)) {
                $datas->whereHas('employee', function ($datas) use ($company) {
                    $datas->whereIn('contribution_level_code', $company);
                });
            }
            
            $datas = $datas->get();
            
            $datas->map(function($item) {

                // Format created_at
                $createdDate = Carbon::parse($item->created_at);

                    $item->formatted_created_at = $createdDate->format('d M Y');
    
                // Format updated_at
                $updatedDate = Carbon::parse($item->updated_at);

                    $item->formatted_updated_at = $updatedDate->format('d M Y');

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

                $access_menu = json_decode($item->employee->access_menu, true);
                $access = $access_menu['goals'] && $access_menu['doj'] ?? null;

                $item->access = $access;

                return $item;

                });
            
            foreach ($datas as $request) {
                // Memeriksa status form dan pembuatnya
                if ($request->goal->form_status != 'Draft' || $request->created_by == Auth::user()->id) {
                    // Mengambil nilai fullname dari relasi approverName
                    if ($request->approval->first()) {
                        $approverName = $request->approval->first();
                        $dataApprover = $approverName->approverName->fullname;
                    }else{
                        $dataApprover = '';
                    }
                    // Buat objek untuk menyimpan data request dan approver fullname
                    $dataItem = new stdClass();
                    $dataItem = $request;              
                    // Tambahkan objek $dataItem ke dalam array $data
                    $data[] = $dataItem;
                    
                }
            }
            Log::info('OnBehalf - Goals Data:', [
                'category' => $category,
                'filter_category' => $filterCategory,
                'count' => count($data),
                'data' => collect($data)->take(5), // hanya tampilkan 5 pertama untuk menghindari log berlebihan
            ]);
        }      

        if ($filterCategory == 'Appraisal') {

            $period = $this->appService->appraisalPeriod();

            $datas = ApprovalRequest::with([
                'employee',
                'appraisal',
                'updatedBy',
                'initiated',
                'calibration' => function ($query) {
                    $query->where('status', 'Pending');
                },
                'approval' => function ($query) {
                    $query->with('approverName');
                }
            ])
            ->where('category', $filterCategory)
            ->where('period', $period)
            ->whereHas('employee')
            ->whereHas('manager');

            // Apply permission-based filters
            $criteria = [
                'work_area_code' => $permissionLocations,
                'group_company' => $permissionGroupCompanies,
                'contribution_level_code' => $permissionCompanies,
            ];

            $datas->where(function ($query) use ($criteria) {
                foreach ($criteria as $key => $value) {
                    if (!empty($value)) {
                        $query->orWhereHas('employee', function ($subquery) use ($key, $value) {
                            $subquery->whereIn($key, $value);
                        });
                    }
                }
            });

            // Apply request filters
            if (!empty($group_company)) {
                $datas->whereHas('employee', function ($query) use ($group_company) {
                    $query->whereIn('group_company', $group_company);
                });
            }

            if (!empty($location)) {
                $datas->whereHas('employee', function ($query) use ($location) {
                    $query->whereIn('work_area_code', $location);
                });
            }

            if (!empty($company)) {
                $datas->whereHas('employee', function ($query) use ($company) {
                    $query->whereIn('contribution_level_code', $company);
                });
            }

            $datas = $datas->get();

            foreach ($datas as $request) {
                $appraisal = $request->appraisal;

                if (!$appraisal) continue; // Skip jika appraisal null

                // Cek form_status atau created_by
                if (($appraisal->goal->form_status ?? null) !== 'Draft' || $request->created_by == Auth::user()->id) {

                    $request->formatted_created_at = $this->appService->formatDate($appraisal->created_at);
                    $request->formatted_updated_at = $this->appService->formatDate($appraisal->updated_at);

                    if ($request->sendback_to == $request->employee->employee_id) {
                        $request->name = $request->employee->fullname . ' (' . $request->employee->employee_id . ')';
                        $request->approvalLayer = '';
                    } else {
                        $request->name = $request->manager->fullname . ' (' . $request->manager->employee_id . ')';
                        $request->approvalLayer = ApprovalLayerAppraisal::where('employee_id', $request->employee_id)
                            ->where('approver_id', $request->current_approval_id)
                            ->value('layer');
                    }

                    // Get final rating
                    $finalRating = null;
                    $formGroupId = $appraisal->form_group_id ?? null;

                    if ($formGroupId) {
                        $formGroup = FormGroupAppraisal::with('rating')->find($formGroupId);
                        if ($formGroup && $formGroup->rating) {
                            foreach ($formGroup->rating as $rating) {
                                if ((int)$rating->value === (int)$appraisal->rating) {
                                    $finalRating = $rating->parameter;
                                    break;
                                }
                            }
                        }
                    }

                    $dataApprover = $request->approval->first()->approverName->fullname ?? '';

                    $goalData = json_decode($request->appraisal->goal->form_data, true);

                    $form_data = Auth::user()->id == $request->appraisal->created_by ? $request->appraisal->approvalSnapshots->form_data : $request->appraisal->form_data;

                    $appraisalData = json_decode($form_data, true);

                    $employeeData = $request->employee;

                    $formData = $this->appService->combineFormData($appraisalData, $goalData, 'employee', $employeeData, $period);

                    // Simpan dalam objek stdClass
                    $dataItem = new \stdClass();
                    $dataItem->request = $request;
                    $dataItem->approver_name = $dataApprover;
                    $dataItem->name = $request->name;
                    $dataItem->approvalLayer = $request->approvalLayer;
                    $dataItem->finalRating = $finalRating;
                    $dataItem->formData = $formData;

                    $data[] = $dataItem;
                }
            }

            Log::info('OnBehalf - Appraisal Data:', [
                'category' => $category,
                'filter_category' => $filterCategory,
                'count' => count($data),
                'data' => collect($data)->take(5), // limit preview in log
            ]);
        }

        if ($filterCategory == 'Rating') {

            $period = $this->appService->appraisalPeriod();

            $datas = EmployeeAppraisal::whereHas('kpiUnit', function ($query) use ($period) {
                $query->where('periode', $period);
            });

            // Apply permission-based filters
            $criteria = [
                'work_area_code' => $permissionLocations,
                'group_company' => $permissionGroupCompanies,
                'contribution_level_code' => $permissionCompanies,
            ];

            $datas->where(function ($query) use ($criteria) {
                foreach ($criteria as $key => $value) {
                    if (!empty($value)) {
                        $query->whereIn($key, $value);
                    }
                }
            });

            // Apply request filters
            if (!empty($group_company)) {
                $datas->whereIn('group_company', $group_company);
            }

            if (!empty($location)) {
                $datas->whereIn('work_area_code', $location);
            }

            if (!empty($company)) {
                $datas->whereIn('contribution_level_code', $company);
            }

            $data = $datas->get();
            
            Log::info('OnBehalf - Rating Data:', [
                'category' => $category,
                'filter_category' => $filterCategory,
                'count' => count($data),
                'data' => collect($data)->take(5), // limit preview in log
            ]);
        }

        if ($filterCategory == 'Achievement') {
            
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
                        $values, $kpi['review_period'] ?? null
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

                    // ðŸ”¥ RE-ASSIGN BALIK
                    $item->achievement = $achievement;

                } else {

                    // ðŸ”¥ JANGAN SET KE RELATION LANGSUNG
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

            Log::info('OnBehalf - Appraisal Data:', [
                'category' => $category,
                'filter_category' => $filterCategory,
                'count' => count($data),
                'data' => collect($data)->take(5), // limit preview in log
            ]);
        }
    
        
        $locations = $this->locations;
        $companies = $this->companies;
        $groupCompanies = $this->groupCompanies;
        
        if ($filterCategory == 'Goals') {
            return view('pages.onbehalfs.goal', compact('data', 'link', 'parentLink', 'locations', 'companies', 'groupCompanies'));
        } elseif ($filterCategory == 'Appraisal') {
            return view('pages.onbehalfs.appraisal', compact('data', 'link', 'parentLink', 'locations', 'companies', 'groupCompanies'));
        } elseif ($filterCategory == 'Rating') {
            return view('pages.onbehalfs.calibrator', compact('data', 'link', 'parentLink', 'locations', 'companies', 'groupCompanies'));
        } elseif ($filterCategory == 'Achievement') {
            return view('pages.onbehalfs.achievement', compact('data', 'link', 'parentLink', 'locations', 'companies', 'groupCompanies'));
        } else {
            return view('pages.onbehalfs.empty');
        }
    }

    // function create($id) {

    //     // Mengambil data pengajuan berdasarkan employee_id atau manager_id
    //     $datas = ApprovalRequest::with(['employee', 'goal', 'manager', 'approval' => function ($query) {
    //         $query->with('approverName'); // Load nested relationship
    //     }])->where('form_id', $id)->get();

    //     $data = [];
        
    //     foreach ($datas as $request) {
    //         // Memeriksa status form dan pembuatnya
    //         if ($request->goal->form_status != 'Draft' || $request->created_by == Auth::user()->id) {
    //             // Mengambil nilai fullname dari relasi approverName
    //             if ($request->approval->first()) {
    //                 $approverName = $request->approval->first();
    //                 $dataApprover = $approverName->approverName->fullname;
    //             }else{
    //                 $dataApprover = '';
    //             }
        
    //             // Buat objek untuk menyimpan data request dan approver fullname
    //             $dataItem = new stdClass();

    //             $dataItem->request = $request;
    //             $dataItem->approver_name = $dataApprover;
              

    //             // Tambahkan objek $dataItem ke dalam array $data
    //             $data[] = $dataItem;
                
    //         }
    //     }
        
    //     $formData = [];
    //     if($datas->isNotEmpty()){
    //         $formData = json_decode($datas->first()->goal->form_data, true);
    //     }

    //     $path = base_path('resources/goal.json');

    //     // Check if the JSON file exists
    //     if (!File::exists($path)) {
    //         // Handle the situation where the JSON file doesn't exist
    //         abort(500, 'JSON file does not exist.');
    //     }

    //     // Read the contents of the JSON file
    //     $options = json_decode(File::get($path), true);

    //     $uomOption = $options['UoM'];
    //     $typeOption = $options['Type'];
    //     $reviewPeriodOption = $options['Review Period'] ?? [];
    //     $calculationMethodOption = $options['Calculation Method'] ?? [];

    //     // Mapping value â†’ label
    //     $reviewPeriodMap = [];
    //     foreach ($reviewPeriodOption as $group) {
    //         foreach ($group as $opt) {
    //             $reviewPeriodMap[$opt['value']] = $opt['label'];
    //         }
    //     }

    //     $calculationMethodMap = [];
    //     foreach ($calculationMethodOption as $group) {
    //         foreach ($group as $opt) {
    //             $calculationMethodMap[$opt['value']] = $opt['label'];
    //         }
    //     }

    //     // Field label
    //     $fieldLabelMap = [
    //         'review_period' => __('Review Period'),
    //         'calculation_method' => __('Calculation Method'),
    //         'kpi' => 'KPI',
    //         'target' => 'Target',
    //         'uom' => 'UoM',
    //         'weightage' => 'Weightage',
    //         'type' => 'Type',
    //     ];

    //     // Snapshot
    //     $snapshots = ApprovalSnapshots::where('form_id', $id)
    //         ->orderBy('created_at', 'desc')
    //         ->get()
    //         ->map(fn($item) => [
    //             'created_at' => $item->created_at,
    //             'data' => json_decode($item->form_data, true),
    //         ])
    //         ->values();

    //     // Group history per KPI
    //     $goalHistories = [];

    //     for ($i = 0; $i < count($snapshots); $i++) {
    //         $current = $snapshots[$i];
    //         $previous = $snapshots[$i + 1] ?? null;

    //         if (!$previous) continue;

    //         foreach ($current['data'] as $kpiIndex => $curr) {
    //             $prev = $previous['data'][$kpiIndex] ?? null;
    //             if (!$prev) continue;

    //             $changes = [];

    //             foreach ($curr as $field => $value) {

    //                 $oldVal = $prev[$field] ?? null;
    //                 $newVal = $value;

    //                 // Mapping value â†’ label
    //                 if ($field === 'review_period') {
    //                     $oldVal = $reviewPeriodMap[$oldVal] ?? $oldVal;
    //                     $newVal = $reviewPeriodMap[$newVal] ?? $newVal;
    //                 }

    //                 if ($field === 'calculation_method') {
    //                     $oldVal = $calculationMethodMap[$oldVal] ?? $oldVal;
    //                     $newVal = $calculationMethodMap[$newVal] ?? $newVal;
    //                 }

    //                 if ($oldVal != $newVal) {
    //                     $label = $fieldLabelMap[$field] ?? ucwords(str_replace('_', ' ', $field));

    //                     $changes[$label] = [
    //                         'old' => $oldVal,
    //                         'new' => $newVal
    //                     ];
    //                 }
    //             }

    //             if (!empty($changes)) {
    //                 $goalHistories[$kpiIndex][] = [
    //                     'date' => \Carbon\Carbon::parse($current['created_at'])->format('d M Y H:i'),
    //                     'changes' => $changes
    //                 ];
    //             }
    //         }
    //     }
    //     $parentLink = 'On Behalf';
    //     $link = 'Approval';

    //     return view('pages.onbehalfs.approval', compact('data', 'link', 'parentLink', 'formData', 'uomOption', 'typeOption', 'goalHistories', 'reviewPeriodOption', 'calculationMethodOption'));

    // }
    
    public function create($id) {

        // Mengambil data pengajuan
        $datas = \App\Models\ApprovalRequest::with(['employee', 'goal', 'manager', 'approval' => function ($query) {
            $query->with('approverName');
        }])->where('form_id', $id)->get();

        $data = [];
        
        foreach ($datas as $request) {
            if ($request->goal->form_status != 'Draft' || $request->created_by == \Illuminate\Support\Facades\Auth::user()->id) {
                if ($request->approval->first()) {
                    $approverName = $request->approval->first();
                    $dataApprover = $approverName->approverName->fullname;
                } else {
                    $dataApprover = '';
                }
        
                $dataItem = new \stdClass();
                $dataItem->request = $request;
                $dataItem->approver_name = $dataApprover;
              
                $data[] = $dataItem;
            }
        }
        
        $formData = [];
        if($datas->isNotEmpty()){
            $formData = json_decode($datas->first()->goal->form_data, true);
        }

        $path = base_path('resources/goal.json');
        if (!\Illuminate\Support\Facades\File::exists($path)) {
            abort(500, 'JSON file does not exist.');
        }

        $options = json_decode(\Illuminate\Support\Facades\File::get($path), true);

        $uomOption = $options['UoM'] ?? [];
        $typeOption = $options['Type'] ?? [];
        $reviewPeriodOption = $options['Review Period'] ?? [];
        $calculationMethodOption = $options['Calculation Method'] ?? [];

        $reviewPeriodMap = [];
        foreach ($reviewPeriodOption as $group) {
            foreach ($group as $opt) {
                $reviewPeriodMap[$opt['value']] = $opt['label'];
            }
        }

        $calculationMethodMap = [];
        foreach ($calculationMethodOption as $group) {
            foreach ($group as $opt) {
                $calculationMethodMap[$opt['value']] = $opt['label'];
            }
        }

        $fieldLabelMap = [
            'review_period' => 'Review Period',
            'calculation_method' => 'Calculation Method',
            'calc_method' => 'Calculation Method',
            'kpi' => 'KPI',
            'description' => 'Description',
            'target' => 'Target',
            'uom' => 'UoM',
            'weightage' => 'Weightage',
            'type' => 'Type',
        ];

        // --- KUNCI PERBAIKAN SNAPSHOT ADMIN ON BEHALF ---
        // Ambil ID Manager yang sedang diwakilkan
        $manager_id = $datas->isNotEmpty() ? $datas->first()->current_approval_id : null;

        $snapshots = \App\Models\ApprovalSnapshots::where('form_id', $id)
            ->where('employee_id', $manager_id) // Filter berdasarkan ID Manager
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'created_at' => $item->created_at,
                    'data' => json_decode($item->form_data, true) ?? [],
                ];
            })
            ->values()
            ->toArray();

        // Jika ini pengajuan baru, $beforeSnapshot akan kosong dan View akan men-trigger status "NEW"
        $beforeSnapshot = $snapshots[0]['data'] ?? [];
        // ------------------------------------------------

        $parentLink = 'On Behalf';
        $link = 'Approval';

        return view('pages.onbehalfs.approval', compact(
            'data', 
            'link', 
            'parentLink', 
            'formData', 
            'uomOption', 
            'typeOption', 
            'reviewPeriodOption', 
            'calculationMethodOption', 
            'reviewPeriodMap',
            'calculationMethodMap',
            'beforeSnapshot'
        ));
    }
    
    public function store(Request $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            // Determine next approver and status
            $currentLayer = ApprovalLayer::where('approver_id', $request->current_approver_id)
                ->where('employee_id', $request->employee_id)
                ->value('layer');

            $nextLayer = $currentLayer ? $currentLayer + 1 : 1;
            $nextApprover = ApprovalLayer::where('employee_id', $request->employee_id)
                ->where('layer', $nextLayer)
                ->value('approver_id');

            $statusRequest = $nextApprover ? 'Pending' : 'Approved';
            $statusForm = $nextApprover ? 'Submitted' : 'Approved';

            // Validate form submission
            if ($request->submit_type === 'submit_form') {
                $rules = [
                    'kpi.*' => 'required|string',
                    'target.*' => 'required|string',
                    'uom.*' => 'required|string',
                    'weightage.*' => 'required|integer|min:5|max:100',
                    'type.*' => 'required|string',
                ];

                $validator = Validator::make($request->all(), $rules, [
                    'weightage.*.integer' => 'Weightage harus berupa angka.',
                    'weightage.*.min' => 'Weightage minimal :min%.',
                    'weightage.*.max' => 'Weightage maksimal :max%.',
                ]);

                if ($validator->fails()) {
                    return back()->withErrors($validator)->withInput();
                }
            }

            // Prepare KPI data
            $kpiData = [];
            foreach ($request->input('kpi', []) as $index => $kpi) {
                $kpiData[$index] = [
                    'kpi' => $kpi,
                    'description' => $request->description[$index] ?? '',
                    'target' => $request->target[$index],
                    'uom' => $request->uom[$index],
                    'weightage' => $request->weightage[$index],
                    'type' => $request->type[$index],
                    'custom_uom' => $request->custom_uom[$index] ?? null,
                    'review_period' => $request->review_period[$index] ?? null,
                    'calculation_method' => $request->calculation_method[$index] ?? null,
                    'kpi_id' => $request->kpi_id[$index],
                ];
            }

            $jsonData = json_encode($kpiData);

            // Get the current approver's ID from the request
            $approverId = $request->current_approver_id;

            // Use firstOrNew to handle both cases (existing/new)
            $snapshot = ApprovalSnapshots::firstOrNew([
                'form_id' => $request->id,
                'employee_id' => $approverId,
            ]);

            // For new records, set required fields
            if (!$snapshot->exists) {
                $snapshot->id = Str::uuid(); // Remove if using model UUID generation
                $snapshot->form_id = $request->id;
                $snapshot->employee_id = $approverId;
                $snapshot->created_by = Auth::user()->id;
            }

            // Update common fields for both cases
            $snapshot->form_data = $jsonData;
            $snapshot->updated_by = Auth::user()->id;

            // Save the record
            $snapshot->save();

            if (!$snapshot->save()) {
                throw new Exception('Gagal menyimpan snapshot persetujuan');
            }

            // Update goal status
            $goal = Goal::findOrFail($request->id);
            $goal->form_data = $jsonData;
            $goal->form_status = $statusForm;

            if (!$goal->save()) {
                throw new Exception('Gagal memperbarui status goal');
            }

            // Update approval request
            $approvalRequest = ApprovalRequest::where('form_id', $request->id)
                ->firstOrFail();
                
            $approvalRequest->current_approval_id = $nextApprover ?? $request->current_approver_id;
            $approvalRequest->status = $statusRequest;
            $approvalRequest->updated_by = Auth::id();
            $approvalRequest->messages = $request->messages;
            $approvalRequest->sendback_messages = null;
            $approvalRequest->sendback_to = null;

            if (!$approvalRequest->save()) {
                throw new Exception('Gagal memperbarui permintaan persetujuan');
            }

            // Update/create approval record
            $approval = Approval::firstOrNew([
                'request_id' => $approvalRequest->id,
                'approver_id' => $request->current_approver_id,
            ]);

            $approval->messages = $request->messages;
            $approval->status = 'Approved';
            $approval->created_by = Auth::id();

            if (!$approval->save()) {
                throw new Exception('Gagal menyimpan catatan persetujuan');
            }

            DB::commit();
            return redirect()
                    ->route('onbehalf')
                    ->with('success', 'Achievements approved successfully');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Approval process failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user' => Auth::id(),
                'goal_id' => $request->id ?? 'N/A',
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    public function unitOfMeasurement()
    {
        $uom = file_get_contents(base_path('resources/goal.json'));

        return response()->json(json_decode($uom, true));
    }

    public function sendback(Request $request, ApprovalRequest $approval)
    {
        $sendbackTo = $request->input('sendback_to');

        if ($sendbackTo === 'creator') {
            // Kirim kembali ke pembuat form (creator)
            $creator = $approval->user; // Pembuat form
            $previousApprovers = $creator->creatorApproverLayer->flatMap(function ($layer) {
                return $layer->previousApprovers;
            });
        } elseif ($sendbackTo === 'previous_approver') {
            // Kirim kembali ke atasan sebelumnya
            $previousApprovers = $approval->user->previousApprovers;
        }

        // Lakukan sesuatu dengan daftar previous_approvers, seperti menampilkannya di view
        return view('approval.sendback', compact('previousApprovers'));
    }

    public function getGoalContent(Request $request)
    {
        // Get the authenticated user's employee_id
        $user = Auth::user();

        $permissionLocations = $this->permissionLocations;
        $permissionCompanies = $this->permissionCompanies;
        $permissionGroupCompanies = $this->permissionGroupCompanies;

        $group_company = $request->input('group_company', []);
        $location = $request->input('location', []);
        $company = $request->input('company', []);

        $filters = compact('group_company', 'location', 'company');

        // Start building the query
        $query = ApprovalRequest::with(['employee', 'manager', 'goal', 'initiated'])->where('category', $this->category);

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
                $query->whereIn('group_company', $group_company);
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

        $path = base_path('resources/goal.json');

        // Check if the JSON file exists
        if (!File::exists($path)) {
            // Handle the situation where the JSON file doesn't exist
            abort(500, 'JSON file does not exist.');
        }

        // Read the contents of the JSON file
        $options = json_decode(File::get($path), true);

        $uomOption = $options['UoM'];
        $typeOption = $options['Type'];

        // Fetch the data based on the constructed query
        $data = $query->get();
        // Determine the report type and return the appropriate view
            return view('pages.onbehalfs.goal', compact('data', 'uomOption', 'typeOption'));
        
    }

    public function goalsRevoke(Request $request)
    {
        $goalId = $request->input('id');

        // Find the approval request record
        $approvalRequest = ApprovalRequest::where('form_id', $goalId)->first();
        $goals = Goal::where('id', $goalId)->first();
        $firstApprover = ApprovalLayer::where('employee_id', $approvalRequest->employee_id)->orderBy('layer', 'asc')
        ->value('approver_id');
        
        if (!$approvalRequest || !$goals) {
            return response()->json(['success' => false, 'message' => 'Goals not found.']);
        }

        try {
            // Process the revoke logic here
            $approvalRequest->sendback_to = $approvalRequest->employee_id;
            $approvalRequest->current_approval_id = $firstApprover;
            $approvalRequest->status = 'Sendback';
            $approvalRequest->save();

            $goals->form_status = 'Submitted';
            $goals->save();

            if ($goals) {
                Approval::where('request_id', $approvalRequest->id)->delete();
            }

            return response()->json(['success' => true, 'message' => 'Goal revoked successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to revoke goal.']);
        }
    }

    public function rating($id) {
        $period = $this->appService->appraisalPeriod();
        $category = 'Appraisal';
        try {
            Log::info('Starting the rating on behalfs method.', ['user' => $this->user]);

            $amountOfTime = 100;
            ini_set('max_execution_time', $amountOfTime);
            $user = $id;
            $period = $this->appService->appraisalPeriod();

            // Get the KPI unit and calibration percentage
            $kpiUnit = KpiUnits::with(['masterCalibration' => function($query) use ($period) {
                $query->where('period', $period);
            }])->where('employee_id', $id)->where('status_aktif', 'T')->where('periode', $period)->first();

            if (!$kpiUnit) {
                Log::warning('KPI Unit not set for the user.', ['user' => $id]);
                Session::flash('error', "Your KPI Unit not been set");
                Session::flash('errorTitle', "Cannot Initiate Rating");
            }

            Log::info('Fetching KPI unit and calibration percentage.', ['user' => $id, 'period' => $period, 'kpiUnit' => $kpiUnit]);

            $calibration = $kpiUnit->masterCalibration->percentage;
            $masterRating = MasterRating::select('id_rating_group', 'parameter', 'value', 'min_range', 'max_range')
                ->where('id_rating_group', $kpiUnit->masterCalibration->id_rating_group)
                ->get();

            Log::info('Fetched master ratings.', ['masterRatingCount' => $masterRating->count()]);

            // Query for all ApprovalLayerAppraisal data
            $allData = ApprovalLayerAppraisal::with(['employee'])
                ->where('approver_id', $id)
                ->whereHas('employee', function ($query) {
                    // Ensure the employee's access_menu has accesspa = 1
                    $query->where(function($q) {
                        $q->whereRaw('json_valid(access_menu)')
                        ->whereJsonContains('access_menu', ['createpa' => 1]);
                    });
                })
                ->where('layer_type', 'calibrator')
                ->get();

            Log::info('Fetched all ApprovalLayerAppraisal data.', ['allDataCount' => $allData->count()]);

            // Query for ApprovalLayerAppraisal data with approval requests
            $dataWithRequests = ApprovalLayerAppraisal::join('approval_requests', 'approval_requests.employee_id', '=', 'approval_layer_appraisals.employee_id')
                ->where('approval_layer_appraisals.approver_id', $id)
                ->where('approval_layer_appraisals.layer_type', 'calibrator')
                ->where('approval_requests.category', $category)
                ->where('approval_requests.period', $period) // Apply $period to the relation
                ->whereNull('approval_requests.deleted_at')
                ->select('approval_layer_appraisals.*')
                ->get()
                ->keyBy('id');  // This will create a collection indexed by the 'id'

            Log::info('Fetched ApprovalLayerAppraisal data with requests.', ['dataWithRequestsCount' => $dataWithRequests->count()]);

            // Group the data based on job levels
            $datas = $allData->groupBy(function ($data) {
                $jobLevel = $data->employee->job_level;
                if (in_array($jobLevel, ['2A', '2B', '2C', '2D', '3A', '3B'])) {
                    return 'Level23';
                } elseif (in_array($jobLevel, ['4A', '4B', '5A', '5B'])) {
                    return 'Level45';
                } elseif (in_array($jobLevel, ['6A', '6B', '7A', '7B'])) {
                    return 'Level67';
                } elseif (in_array($jobLevel, ['8A', '8B', '9A', '9B'])) {
                    return 'Level89';
                }
                return 'Other Levels';
            })->map(function ($group) use ($dataWithRequests, $id, $period, $category) {
                Log::info('Processing group.', ['groupSize' => $group->count()]);

                // Fetch `withRequests` based on the user's criteria
                $withRequests = ApprovalLayerAppraisal::join('approval_requests', 'approval_requests.employee_id', '=', 'approval_layer_appraisals.employee_id')
                    ->where('approval_layer_appraisals.approver_id', $id)
                    ->where('approval_layer_appraisals.layer_type', 'calibrator')
                    ->where('approval_requests.category', $category)
                    ->where('approval_requests.period', $period) // Apply $period to the relation
                    ->whereNull('approval_requests.deleted_at')
                    ->whereIn('approval_layer_appraisals.id', $group->pluck('id'))
                    ->select('approval_layer_appraisals.*', 'approval_requests.*')
                    ->get()
                    ->groupBy('id')
                    ->map(function ($subgroup) {
                        $appraisal = $subgroup->first();
                        $appraisal->approval_requests = $subgroup->first();
                        return $appraisal;
                    });

                Log::info('Processed withRequests.', ['withRequestsCount' => $withRequests->count()]);

                // Filter out items without requests
                $withoutRequests = $group->filter(function ($item) use ($dataWithRequests) {
                    return !$dataWithRequests->has($item->id);
                });

                Log::info('Processed withoutRequests.', ['withoutRequestsCount' => $withoutRequests->count()]);

                return [
                    'with_requests' => $withRequests->values(),
                    'without_requests' => $withoutRequests->values(),
                ];
            })->sortKeys();

            Log::info('Grouped and processed data.', ['groupedDataCount' => $datas->count()]);

            // Process rating data
            $ratingDatas = $datas->map(function ($group) use ($id, $period, $user) {
                Log::info('Processing rating data for group.', ['groupSize' => $group['with_requests']->count() + $group['without_requests']->count()]);

                // Preload all calibration data in bulk
                $calibration = Calibration::with(['approver'])->where('period', $period)
                ->whereIn('employee_id', $group['with_requests']->pluck('employee_id'))
                ->whereIn('appraisal_id', $group['with_requests']->pluck('approvalRequest')->flatten()->pluck('form_id'))
                ->whereIn('status', ['Pending', 'Approved'])
                ->orderBy('id', 'desc')
                ->get()
                ->groupBy(['employee_id', 'appraisal_id']); // Group by employee_id and appraisal_id for easy access

                // Preload suggested ratings and rating values in bulk
                $suggestedRatings = [];
                $ratingValues = [];
                foreach ($group['with_requests'] as $data) {
                $employeeId = $data->employee->employee_id;
                $formId = $formId = $data->approvalRequest->where('category', 'Appraisal')->where('period', $period)->first()->form_id;

                // Cache suggested ratings
                if (!isset($suggestedRatings[$employeeId][$formId])) {
                    $suggestedRatings[$employeeId][$formId] = $this->appService->suggestedRating($employeeId, $formId);
                }

                // Cache rating values
                if (!isset($ratingValues[$employeeId])) {
                    $ratingValues[$employeeId] = $this->appService->ratingValue($employeeId, $user, $period);
                }
                }

                // Process withRequests using preloaded data
                $withRequests = $group['with_requests']->map(function ($data) use ($id, $calibration, $suggestedRatings, $ratingValues, $period) {
                    Log::info('Processing withRequests item.', ['itemId' => $data->id]);

                    $employeeId = $data->employee->employee_id;
                    $formId = $data->approvalRequest->where('category', 'Appraisal')->where('period', $period)->first()->form_id;

                    // Fetch calibration data for the current employee and appraisal
                    $calibrationData = $calibration[$employeeId][$formId] ?? collect();

                    // Find previous rating
                    $previousRating = $calibrationData->whereNotNull('rating')
                        ->where('approver_id', '!=', $id)
                        ->first();

                    // Calculate suggested rating
                    $suggestedRating = $suggestedRatings[$employeeId][$formId];
                    $data->suggested_rating = $calibrationData->where('approver_id', $id)->first()
                        ? $this->appService->convertRating(
                            $suggestedRating,
                            $calibrationData->where('approver_id', $id)->first()->id_calibration_group
                        )
                        : null;

                    // Set previous rating details
                    $data->previous_rating = $previousRating
                        ? $this->appService->convertRating($previousRating->rating, $calibrationData->first()->id_calibration_group)
                        : null;
                    $data->previous_rating_name = $previousRating
                        ? $previousRating->approver->fullname . ' (' . $previousRating->approver->employee_id . ')'
                        : null;

                    // Set rating value
                    $data->rating_value = $ratingValues[$employeeId];


                    // Check if the user is a calibrator
                    $isCalibrator = $calibrationData->where('approver_id', $id)
                        ->where('status', 'Pending')
                        ->isNotEmpty();
                    $data->is_calibrator = $isCalibrator;

                    // Check if rating is allowed
                    $data->rating_allowed = $this->appService->ratingAllowedCheck($employeeId);

                    // Count incomplete ratings
                    $data->rating_incomplete = $calibrationData->whereNull('rating')->whereNull('deleted_at')->count();
                    $data->calibrationData = $calibrationData;

                    // Set rating status and approved date
                    $userCalibration = $calibrationData->first();
                    if ($userCalibration) {
                        $data->rating_status = $calibrationData->where('approver_id', $id)->first() ? $calibrationData->where('approver_id', $id)->first()->status : null;
                        $data->rating_approved_date = Carbon::parse($userCalibration->updated_at)->format('d M Y');
                    }

                    $data->onCalibratorPending = $calibrationData->where('approver_id', $id)->where('status', 'Pending')->count();

                    // Assign Pending and Approved Calibrators
                    $pendingCalibrator = $calibrationData->where('status', 'Pending')->first();
                    $approvedCalibrator = $calibrationData->where('status', 'Approved')->first();

                    $data->current_calibrator = $pendingCalibrator && $pendingCalibrator->approver
                        ? $pendingCalibrator->approver->fullname . ' (' . $pendingCalibrator->approver->employee_id . ')'
                        : false;
                    $data->approver_name = $approvedCalibrator && $approvedCalibrator->approver
                        ? $approvedCalibrator->approver->fullname . ' (' . $approvedCalibrator->approver->employee_id . ')'
                        : ($data->status == 'Pending' ? $data->approval_requests->approver->fullname : false);

                    return $data;
                });

                Log::info('Processed withRequests.', ['processedCount' => $withRequests->count()]);

                // Process `without_requests`
                $withoutRequests = $group['without_requests']->map(function ($data) use ($id, $calibration) {
                    Log::info('Processing withoutRequests item.', ['itemId' => $data->id]);

                    $data->suggested_rating = null;

                    $isCalibrator = Calibration::where('approver_id', $id)
                        ->where('employee_id', $data->employee->employee_id)
                        ->where('status', 'Pending')
                        ->exists();
                    $data->is_calibrator = $isCalibrator;

                    $data->rating_allowed = $this->appService->ratingAllowedCheck($data->employee->employee_id);

                    return $data;
                });

                Log::info('Processed withoutRequests.', ['processedCount' => $withoutRequests->count()]);

                $combinedResults = $withRequests->merge($withoutRequests);

                Log::info('Combined results.', ['combinedCount' => $combinedResults->count()]);

                return $combinedResults;
            });

            Log::info('Processed all rating data.', ['ratingDatasCount' => $ratingDatas->count()]);

            // Get calibration results
            $calibrations = $datas->map(function ($group) use ($calibration, $id) {
                Log::info('Processing calibration results for group.', ['groupSize' => $group['with_requests']->count() + $group['without_requests']->count()]);

                // $onCalibratorPending = $group['with_requests']->where('approver_id', $id)->where('status', 'Pending')->count();
                $calibratorPendingCount = $group['with_requests']->where('onCalibratorPending', '>', 0)->count();

                $countWithRequests = $group['with_requests']->count();
                $countWithoutRequests = $group['without_requests']->count();
                $count = $countWithRequests + $countWithoutRequests;
                // $count = 12; // Test number

                $ratingResults = [];
                $percentageResults = [];
                $calibration = json_decode($calibration, true);

                // Step 1: Calculate initial rating results and percentage results
                foreach ($calibration as $key => $weight) {
                    $ratingResults[$key] = round($count * $weight);
                    $percentageResults[$key] = round(100 * $weight);
                }

                // Step 2: Check if the sum of $ratingResults matches $count
                $totalRatingResults = array_sum($ratingResults);
                $difference = abs($count - $totalRatingResults);

                if ($difference !== 0) {
                    if ($totalRatingResults < $count) {
                        // Normalize the calibration weights to redistribute the difference
                        $totalWeight = array_sum($calibration);
                        $normalizedWeights = array_map(fn($w) => $w / $totalWeight, $calibration);

                        // Redistribute the difference proportionally based on normalized weights
                        foreach ($normalizedWeights as $key => $normalizedWeight) {
                            $adjustment = floor($difference * $normalizedWeight);
                            $ratingResults[$key] += $adjustment;
                        }

                        // Recalculate the total after redistribution to ensure it matches $count
                        $newTotal = array_sum($ratingResults);
                        if ($newTotal !== $count) {
                            // If there's still a small mismatch due to rounding, adjust the largest value
                            $maxWeightKey = array_keys($calibration, max($calibration))[0];
                            $ratingResults[$maxWeightKey] += ($count - $newTotal);
                        }
                    } elseif ($totalRatingResults > $count) {
                        // Allocate the $difference to the lowest $percentageResults that have $ratingResults value >= 1
                        while ($difference > 0) {
                            $lowestKey = collect($percentageResults)
                                ->filter(fn($percentage, $key) => $ratingResults[$key] >= 1)
                                ->sort()
                                ->keys()
                                ->first();

                            if ($lowestKey !== null) {
                                $ratingResults[$lowestKey] -= 1;
                                $difference -= 1;
                            } else {
                                break; // Exit if no valid key is found
                            }
                        }
                    }
                }

                // Step 3: Process suggested ratings and combine results
                $suggestedRatingCounts = $group['with_requests']->pluck('suggested_rating')->countBy();
                $totalSuggestedRatings = $suggestedRatingCounts->sum();

                $combinedResults = [];
                foreach ($calibration as $key => $weight) {
                    $ratingCount = $suggestedRatingCounts->get($key, 0);
                    $ratingPercentage = $totalSuggestedRatings > 0
                        ? round(($ratingCount / $totalSuggestedRatings) * 100, 2)
                        : 0;

                    $combinedResults[$key] = [
                        'percentage' => $percentageResults[$key] . '%',
                        'rating_count' => $ratingResults[$key],
                        'suggested_rating_count' => $ratingCount,
                        'suggested_rating_percentage' => $ratingPercentage . '%',
                    ];
                }

                Log::info('Processed calibration results.', ['combinedResults' => $combinedResults]);

                return [
                    'count' => $count,
                    'calibratorPendingCount' => $calibratorPendingCount,
                    'combined' => $combinedResults,
                ];
            });

            Log::info('Processed all calibration results.', ['calibrationsCount' => $calibrations->count()]);

            // Determine the active level as the first non-empty level
            $activeLevel = null;
            foreach ($calibrations as $level => $data) {
                if (!empty($data)) {
                    $activeLevel = $level;
                    break;
                }
            }

            Log::info('Determined active level.', ['activeLevel' => $activeLevel]);

            $parentLink = 'Calibration';
            $link = 'Rating';
            $id_calibration_group = $kpiUnit->masterCalibration->id_calibration_group;

            Log::info('Returning view with data.', ['activeLevel' => $activeLevel, 'id_calibration_group' => $id_calibration_group]);

            // dd($ratingDatas);

            return view('pages.rating.app', compact('ratingDatas', 'calibrations', 'masterRating', 'link', 'parentLink', 'activeLevel', 'id_calibration_group'));
        } catch (Exception $e) {
            Log::error('Error in index method: ' . $e->getMessage());
            return redirect()->route('onbehalf');
        }
    }

}
