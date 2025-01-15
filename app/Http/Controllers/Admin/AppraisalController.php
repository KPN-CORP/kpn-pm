<?php

namespace App\Http\Controllers\Admin;

use App\Events\FileReadyNotification;
use App\Exports\AppraisalDetailExport;
use App\Http\Controllers\Controller;
use App\Jobs\ExportAppraisalDetails;
use App\Models\Appraisal;
use App\Models\AppraisalContributor;
use App\Models\ApprovalLayerAppraisal;
use App\Models\ApprovalRequest;
use App\Models\ApprovalSnapshots;
use App\Models\Calibration;
use App\Models\EmployeeAppraisal;
use App\Models\FormGroupAppraisal;
use App\Models\MasterRating;
use App\Models\MasterWeightage;
use Illuminate\Http\Request;
use App\Services\AppService;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use stdClass;

use function Pest\Laravel\json;

class AppraisalController extends Controller
{
    protected $category;
    protected $user;
    protected $appService;
    protected $roles;

    public function __construct(AppService $appService)
    {
        $this->appService = $appService;
        $this->user = Auth()->user()->employee_id;
        $this->category = 'Appraisal';
        $this->roles = Auth()->user()->roles;
    }

    public function index(Request $request)
    {
        $period = $this->appService->appraisalPeriod();

        $restrictionData = [];
        if (!is_null($this->roles)) {
            $restrictionData = json_decode($this->roles->first()->restriction, true);
        }

        $permissionGroupCompanies = $restrictionData['group_company'] ?? [];
        $permissionCompanies = $restrictionData['contribution_level_code'] ?? [];
        $permissionLocations = $restrictionData['work_area_code'] ?? [];

        $criteria = [
            'work_area_code' => $permissionLocations,
            'group_company' => $permissionGroupCompanies,
            'contribution_level_code' => $permissionCompanies,
        ];

        $query = EmployeeAppraisal::with([
            'appraisal' => function ($query) use ($period) {
                $query->where('period', $period);
            },
            'appraisalLayer.approver',
            'appraisalContributor',
            'calibration',
            'appraisal.formGroupAppraisal'
        ]);
        // }, 'appraisalLayer.approver', 'appraisalContributor', 'calibration', 'appraisal.formGroupAppraisal'])->where('employee_id', '01119060003');

        $query->where(function ($query) use ($criteria) {
            foreach ($criteria as $key => $value) {
                if ($value !== null && !empty($value)) {
                    $query->whereIn($key, $value);
                }
            }
        });

        $data = $query->get();

        $datas = $data->map(function ($employee) {
            $approvalStatus = [];

            foreach ($employee->appraisalLayer as $layer) {
                // if ($layer->layer_type !== 'manager') {
                if (!isset($approvalStatus[$layer->layer_type])) {
                    $approvalStatus[$layer->layer_type] = [];
                }

                // Check availability depending on the layer_type (AppraisalContributor for peers/subordinates, Calibration for calibrators)
                if ($layer->layer_type === 'calibrator') {
                    // Check using Calibration model for calibrators
                    $isAvailable = Calibration::where('approver_id', $layer->approver_id)
                        ->where('employee_id', $employee->employee_id)
                        ->where('status', 'Approved')
                        ->exists();
                } else {
                    // Check using AppraisalContributor model for peers and subordinates
                    $isAvailable = AppraisalContributor::where('contributor_id', $layer->approver_id)
                        // ->where('contributor_type', '!=', 'manager')
                        ->where('employee_id', $employee->employee_id)
                        ->exists();
                }

                // Append approver_id, layer, and status data to the corresponding array
                $approvalStatus[$layer->layer_type][] = [
                    'approver_id' => $layer->approver_id,
                    'layer' => $layer->layer,
                    'status' => $isAvailable ? true : false,
                    'approver_name' => $layer->approver->fullname,
                    'approver_id' => $layer->approver->employee_id,
                ];
                // }
            }

            // Sort each layer_type's array by 'layer'
            foreach ($approvalStatus as $type => $layers) {
                usort($layers, function ($a, $b) {
                    return $a['layer'] <=> $b['layer'];
                });
                $approvalStatus[$type] = $layers;
            }

            // Prepare popover content
            $popoverContent = [];

            // Add calibrator layers
            foreach ($approvalStatus['calibrator'] ?? [] as $layerIndex => $layer) {
                $popoverContent[] = "C" . ($layerIndex + 1) . ": " . ($layer['approver_name'] . ' (' . $layer['approver_id'] . ')' ?? 'N/A');
            }

            // Add peer layers
            foreach ($approvalStatus['peers'] ?? [] as $layerIndex => $layer) {
                $popoverContent[] = "P" . ($layerIndex + 1) . ": " . ($layer['approver_name'] . ' (' . $layer['approver_id'] . ')' ?? 'N/A');
            }

            // Add subordinate layers
            foreach ($approvalStatus['subordinate'] ?? [] as $layerIndex => $layer) {
                $popoverContent[] = "S" . ($layerIndex + 1) . ": " . ($layer['approver_name'] . ' (' . $layer['approver_id'] . ')' ?? 'N/A');
            }

            foreach ($approvalStatus['manager'] ?? [] as $layerIndex => $layer) {
                $popoverContent[] = "M: " . ($layer['approver_name'] . ' (' . $layer['approver_id'] . ')' ?? 'N/A');
            }

            // Join content with line breaks
            $popoverText = implode("<br>", $popoverContent);

            if ($employee->appraisal->first()) {
                # code...
                $masterRating = MasterRating::select('id_rating_group', 'parameter', 'value', 'min_range', 'max_range')
                    ->where('id_rating_group', $employee->appraisal->first()->formGroupAppraisal->id_rating_group)
                    ->get();
                $convertRating = [];

                foreach ($masterRating as $rating) {
                    $convertRating[$rating->value] = $rating->parameter;
                }
                $appraisal = $employee->appraisal->first()->rating
                    ? $convertRating[$employee->appraisal->first()->rating]
                    : '-';
            } else {
                $appraisal = '-';
            }

            $accessMenu = json_decode($employee->access_menu, true);

            return [
                'id' => $employee->employee_id,
                'name' => $employee->fullname,
                'groupCompany' => $employee->group_company,
                'accessPA' => isset($accessMenu['accesspa']) ? $accessMenu['accesspa'] : 0,
                'appraisalStatus' => $employee->appraisal->first(),
                'approvalStatus' => $approvalStatus,
                'finalScore' => $appraisal,
                'popoverContent' => $popoverText, // Add popover content here
            ];
        });

        $maxCalibrator = min($datas->map(function ($employee) {
            return isset($employee['approvalStatus']['calibrator'])
                ? count($employee['approvalStatus']['calibrator'])
                : 0;
        })->max(), 10);

        if ($maxCalibrator > 0) {
            $layerHeaders = array_map(function ($num) {
                return 'C' . ($num + 1);
            }, range(0, $maxCalibrator - 1));

            $layerBody = array_map(function ($num) {
                return ($num + 1);
            }, range(0, $maxCalibrator - 1));
        } else {
            // Handle the case when maxCalibrator is 0
            $layerHeaders = [];  // Or set to a default message like ['No calibrators available']
            $layerBody = [];     // Or set to a default message if needed
        }

        $parentLink = __('Reports');
        $link = __('Appraisal');

        return view('pages.appraisals.admin.app', compact('datas', 'layerHeaders', 'layerBody', 'link', 'parentLink'));
    }

    public function detail(Request $request)
    {
        $period = $this->appService->appraisalPeriod();
        $data = EmployeeAppraisal::with([
            'appraisalLayer' => function ($query) {
                $query->where('layer_type', '!=', 'calibrator');
            },
            'appraisal' => function ($query) use ($period) {
                $query->where('period', $period);
            }
        ])->where('employee_id', $request->id)->get();

        try {

            $data->map(function ($item) {

                $appraisal_id = $item->appraisal->first()->id;

                $item->appraisalLayer->map(function ($subItem) use ($appraisal_id) {

                    $contributor = AppraisalContributor::select('id', 'appraisal_id', 'contributor_type', 'contributor_id')->where('contributor_type', $subItem->layer_type)->where('contributor_id', $subItem->approver_id)->where('appraisal_id', $appraisal_id)->first();

                    $subItem->contributor = $contributor;
                    return $subItem;
                });

                $item->join_date = $this->appService->formatDate($item->date_of_joining);

                return $item;
            });

            $datas = $data->first();

            $form_id = $datas->appraisal->first()->id;

            // Convert array to collection and group by layer_type
            $groupedData = collect($datas->appraisalLayer)->groupBy('layer_type')->map(function ($items, $layerType) {
                // Further group each layer_type by 'layer'
                return $items->groupBy('layer')->mapWithKeys(function ($layerGroup, $layer) use ($layerType) {
                    // Handle layer type name and layer-based key
                    if ($layerType === 'manager') {
                        return ['Manager' => $layerGroup];
                    } elseif ($layerType === 'peers') {
                        return ['P' . $layer => $layerGroup];
                    } elseif ($layerType === 'subordinate') {
                        return ['S' . $layer => $layerGroup];
                    }
                });
            });

            $parentLink = __('Reports');
            $link = __('Appraisal');

            $formGroup = FormGroupAppraisal::with(['rating'])->find($datas->appraisal->first()->form_group_id);

            $ratings = [];

            foreach ($formGroup->rating as $rating) {
                $ratings[$rating->value] = $rating->parameter;
            }

            $final_rating = '-';

            if ($datas->appraisal->first()->rating) {
                $final_rating = $ratings[$datas->appraisal->first()->rating];
            }

            return view('pages.appraisals.admin.detail', compact('datas', 'groupedData', 'parentLink', 'link', 'final_rating'));

        } catch (Exception $e) {
            Log::error('Error in index method: ' . $e->getMessage());

            return redirect()->route('admin.appraisal');
        }
    }

    public function getDetailData(Request $request)
    {
        try {
            $user = Auth::user()->employee_id;
            $period = $this->appService->appraisalPeriod();
            $contributorId = $request->id;

            $parts = explode('_', $contributorId);

            // Access the separated parts
            $id = $parts[0];
            $formId = $parts[1];

            if ($id == 'summary') {
                $datasQuery = AppraisalContributor::with(['employee'])->where('appraisal_id', $formId);
                $datas = $datasQuery->get();

                $checkSnapshot = ApprovalSnapshots::where('form_id', $formId)->where('created_by', $datas->first()->employee->id)
                    ->orderBy('created_at', 'desc');

                // Check if `datas->first()->employee->id` exists
                if ($checkSnapshot) {
                    $query = $checkSnapshot;
                } else {
                    $query = ApprovalSnapshots::where('form_id', $formId)
                        ->orderBy('created_at', 'asc');
                }

                $employeeForm = $query->first();

                $data = [];
                $appraisalDataCollection = [];
                $goalDataCollection = [];

                $formGroupContent = $this->appService->formGroupAppraisal($datas->first()->employee_id, 'Appraisal Form');

                if (!$formGroupContent) {
                    $appraisalForm = ['data' => ['formData' => []]];
                } else {
                    $appraisalForm = $formGroupContent;
                }

                $cultureData = $this->getDataByName($appraisalForm['data']['form_appraisals'], 'Culture') ?? [];
                $leadershipData = $this->getDataByName($appraisalForm['data']['form_appraisals'], 'Leadership') ?? [];


                if ($employeeForm) {

                    // Create data item object
                    $dataItem = new stdClass();
                    $dataItem->request = $employeeForm;
                    $dataItem->name = $employeeForm->name;
                    $dataItem->goal = $employeeForm->goal;
                    $data[] = $dataItem;


                    // Get appraisal form data for each record
                    $appraisalData = [];


                    if ($employeeForm->form_data) {
                        $appraisalData = json_decode($employeeForm->form_data, true);
                        $contributorType = $employeeForm->contributor_type;
                        $appraisalData['contributor_type'] = 'employee';
                    }

                    // Get goal form data for each record
                    $goalData = [];
                    if ($employeeForm->goal && $employeeForm->goal->form_data) {
                        $goalData = json_decode($employeeForm->goal->form_data, true);
                        $goalDataCollection[] = $goalData;
                    }

                    // Combine the appraisal and goal data for each contributor
                    $employeeData = $employeeForm->employee; // Get employee data

                    $formData[] = $appraisalData;

                }

                foreach ($datas as $request) {

                    // Create data item object
                    $dataItem = new stdClass();
                    $dataItem->request = $request;
                    $dataItem->name = $request->name;
                    $dataItem->goal = $request->goal;
                    $data[] = $dataItem;

                    // Get appraisal form data for each record
                    $appraisalData = [];

                    if ($request->form_data) {
                        $appraisalData = json_decode($request->form_data, true);
                        $contributorType = $request->contributor_type;
                        $appraisalData['contributor_type'] = $contributorType;
                    }

                    // Get goal form data for each record
                    $goalData = [];
                    if ($request->goal && $request->goal->form_data) {
                        $goalData = json_decode($request->goal->form_data, true);
                        $goalDataCollection[] = $goalData;
                    }

                    // Combine the appraisal and goal data for each contributor
                    $employeeData = $request->employee; // Get employee data

                    $formData[] = $appraisalData;

                }

                $jobLevel = $employeeData->job_level;

                $weightageData = MasterWeightage::where('group_company', 'LIKE', '%' . $employeeData->group_company . '%')->where('period', $request->period)->first();

                $weightageContent = json_decode($weightageData->form_data, true);

                $result = $this->appraisalSummary($weightageContent, $formData, $employeeData->employee_id);

                // $formData = $this->appService->combineFormData($result['summary'], $goalData, $result['summary']['contributor_type'], $employeeData, $request->period);


                $formData = $this->appService->combineSummaryFormData($result, $goalData, $employeeData, $request->period);

                if (isset($formData['totalKpiScore'])) {
                    $formData['kpiScore'] = round($formData['kpiScore'], 2);
                    $formData['cultureScore'] = round($formData['cultureScore'], 2);
                    $formData['leadershipScore'] = round($formData['leadershipScore'], 2);
                }

                foreach ($formData['formData'] as &$form) {
                    if ($form['formName'] === 'Leadership') {
                        foreach ($leadershipData as $index => $leadershipItem) {
                            foreach ($leadershipItem['items'] as $itemIndex => $item) {
                                if (isset($form[$index][$itemIndex])) {
                                    $form[$index][$itemIndex] = [
                                        'formItem' => $item,
                                        'score' => $form[$index][$itemIndex]['score']
                                    ];
                                }
                            }
                            $form[$index]['title'] = $leadershipItem['title'];
                        }
                    }

                    if ($form['formName'] === 'Culture') {
                        foreach ($cultureData as $index => $cultureItem) {
                            foreach ($cultureItem['items'] as $itemIndex => $item) {
                                if (isset($form[$index][$itemIndex])) {
                                    $form[$index][$itemIndex] = [
                                        'formItem' => $item,
                                        'score' => $form[$index][$itemIndex]['score']
                                    ];
                                }
                            }
                            $form[$index]['title'] = $cultureItem['title'];
                        }
                    }

                }

                $appraisalData = $formData;

            } else {

                $datasQuery = AppraisalContributor::with(['employee'])->where('id', $id);

                $datas = $datasQuery->get();

                $formattedData = $datas->map(function ($item) {
                    $item->formatted_created_at = $this->appService->formatDate($item->created_at);

                    $item->formatted_updated_at = $this->appService->formatDate($item->updated_at);

                    return $item;
                });

                $data = [];
                foreach ($formattedData as $request) {
                    $dataItem = new stdClass();
                    $dataItem->request = $request;
                    $dataItem->name = $request->name;
                    $dataItem->goal = $request->goal;
                    $data[] = $dataItem;
                }

                $goalData = $datas->isNotEmpty() ? json_decode($datas->first()->goal->form_data, true) : [];
                $appraisalData = $datas->isNotEmpty() ? json_decode($datas->first()->form_data, true) : [];

                $employeeData = $datas->first()->employee;

                // Setelah data digabungkan, gunakan combineFormData untuk setiap jenis kontributor

                $formGroupContent = $this->appService->formGroupAppraisal($datas->first()->employee_id, 'Appraisal Form');

                if (!$formGroupContent) {
                    $appraisalForm = ['data' => ['formData' => []]];
                } else {
                    $appraisalForm = $formGroupContent;
                }

                $cultureData = $this->getDataByName($appraisalForm['data']['form_appraisals'], 'Culture') ?? [];
                $leadershipData = $this->getDataByName($appraisalForm['data']['form_appraisals'], 'Leadership') ?? [];

                $jobLevel = $employeeData->job_level;

                $weightageData = MasterWeightage::where('group_company', 'LIKE', '%' . $employeeData->group_company . '%')->where('period', $request->period)->first();

                $weightageContent = json_decode($weightageData->form_data, true);

                $formData = $this->appService->combineFormData($appraisalData, $goalData, $datas->first()->contributor_type, $employeeData, $datas->first()->period);

                if (isset($formData['totalKpiScore'])) {
                    $appraisalData['kpiScore'] = round($formData['kpiScore'], 2);
                    $appraisalData['cultureScore'] = round($formData['cultureScore'], 2);
                    $appraisalData['leadershipScore'] = round($formData['leadershipScore'], 2);
                }

                foreach ($formData['formData'] as &$form) {
                    if ($form['formName'] === 'Leadership') {
                        foreach ($leadershipData as $index => $leadershipItem) {
                            foreach ($leadershipItem['items'] as $itemIndex => $item) {
                                if (isset($form[$index][$itemIndex])) {
                                    $form[$index][$itemIndex] = [
                                        'formItem' => $item,
                                        'score' => $form[$index][$itemIndex]['score']
                                    ];
                                }
                            }
                            $form[$index]['title'] = $leadershipItem['title'];
                        }
                    }
                    if ($form['formName'] === 'Culture') {
                        foreach ($cultureData as $index => $cultureItem) {
                            foreach ($cultureItem['items'] as $itemIndex => $item) {
                                if (isset($form[$index][$itemIndex])) {
                                    $form[$index][$itemIndex] = [
                                        'formItem' => $item,
                                        'score' => $form[$index][$itemIndex]['score']
                                    ];
                                }
                            }
                            $form[$index]['title'] = $cultureItem['title'];
                        }
                    }
                }

                $path = base_path('resources/goal.json');
                if (!File::exists($path)) {
                    $options = ['UoM' => [], 'Type' => []];
                } else {
                    $options = json_decode(File::get($path), true);
                }

                $uomOption = $options['UoM'] ?? [];
                $typeOption = $options['Type'] ?? [];

                $employee = EmployeeAppraisal::where('employee_id', $user)->first();
                if (!$employee) {
                    $access_menu = ['goals' => null];
                } else {
                    $access_menu = json_decode($employee->access_menu, true);
                }
                $goals = $access_menu['goals'] ?? null;

                $selectYear = ApprovalRequest::where('employee_id', $user)->where('category', $this->category)->select('created_at')->get();
                $selectYear->transform(function ($req) {
                    $req->year = Carbon::parse($req->created_at)->format('Y');
                    return $req;
                });

                $appraisalData = $formData;

            }

            return view('components.appraisal-card', compact('datas', 'formData', 'appraisalData'));

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }

    }

    private function getDataByName($data, $name)
    {
        foreach ($data as $item) {
            if ($item['name'] === $name) {
                return $item['data'];
            }
        }
        return null;
    }

    function appraisalSummary($weightages, $formData, $employeeID)
    {

        $calculatedFormData = [];

        $checkLayer = ApprovalLayerAppraisal::where('employee_id', $employeeID)
            ->where('layer_type', '!=', 'calibrator')
            ->selectRaw('layer_type, COUNT(*) as count')
            ->groupBy('layer_type')
            ->get();

        $layerCounts = $checkLayer->pluck('count', 'layer_type')->toArray();

        $managerCount = $layerCounts['manager'] ?? 0;
        $peersCount = $layerCounts['peers'] ?? 0;
        $subordinateCount = $layerCounts['subordinate'] ?? 0;

        $calculatedFormData = []; // Initialize result array

        // Loop through $formData first to structure results by formGroupName and contributor_type
        foreach ($formData as $data) {

            $contributorType = $data['contributor_type'];
            $formGroupName = $data['formGroupName'];
            $formDataWithCalculatedScores = []; // Array to store calculated scores for the group

            foreach ($data['formData'] as $form) {
                $formName = $form['formName'];
                $calculatedForm = ["formName" => $formName];

                if ($formName === "KPI") {
                    // Directly copy KPI achievements
                    foreach ($form as $key => $achievement) {
                        if (is_numeric($key)) {
                            $calculatedForm[$key] = $achievement;
                        }
                    }
                } else {
                    // Process other forms
                    foreach ($weightages as $item) {
                        foreach ($item['competencies'] as $competency) {
                            if ($competency['competency'] == $formName) {
                                // Handle weightage360
                                $weightage360 = 0;

                                if (isset($competency['weightage360'])) {
                                    // Extract weightages for each type
                                    $weightageValues = collect($competency['weightage360'])->flatMap(function ($weightage) {
                                        return $weightage;
                                    });

                                    $weightage360 = $weightageValues[$contributorType] ?? 0;

                                    if ($contributorType == 'manager') {
                                        if ($subordinateCount > 0) {
                                            $weightage360 += $weightageValues['employee'] ?? 0;
                                        }

                                        // Adjust weightages
                                        if ($subordinateCount == 0) {
                                            $weightage360 += $weightageValues['subordinate'] ?? 0;
                                        }
                                        if ($peersCount == 0) {
                                            $weightage360 += $weightageValues['peers'] ?? 0;
                                        }
                                        if ($subordinateCount == 0 && $peersCount == 0) {
                                            $weightage360 += ($weightageValues['subordinate'] ?? 0) + ($weightageValues['peers'] ?? 0);
                                        }
                                    }
                                }

                                // Calculate weighted scores
                                foreach ($form as $key => $scores) {
                                    if (is_numeric($key)) {
                                        $calculatedForm[$key] = [];
                                        foreach ($scores as $scoreData) {
                                            $score = $scoreData['score'];
                                            $weightedScore = $score * ($weightage360 / 100);
                                            $calculatedForm[$key][] = ["score" => $weightedScore];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                $formDataWithCalculatedScores[] = $calculatedForm;
            }

            $calculatedFormData[] = [
                "formGroupName" => $formGroupName,
                "formData" => $formDataWithCalculatedScores,
                "contributor_type" => $contributorType
            ];

        }


        // Second part: Calculate summary averages
        $averages = [];

        // Iterate through each contributor's data
        foreach ($calculatedFormData as $contributorData) {

            $contributorType = $contributorData['contributor_type'];

            foreach ($contributorData['formData'] as $form) {
                $formName = $form['formName'];

                if ($formName === 'KPI') {
                    // Store KPI values (only from manager)
                    if ($contributorType === 'manager') {
                        foreach ($form as $key => $value) {
                            if (is_numeric($key)) {
                                // Initialize if not already set
                                if (!isset($summedScores[$formName][$key])) {
                                    $summedScores[$formName][$key] = ["achievement" => $value['achievement']];
                                }
                            }
                        }
                    }
                } else {

                    // Process forms like Culture and Leadership
                    foreach ($form as $key => $values) {

                        if (is_numeric($key)) {
                            // Initialize if not already set
                            if (!isset($summedScores[$formName][$key])) {
                                $summedScores[$formName][$key] = [];
                            }

                            // if ($peersCount == 0 || $subordinateCount == 0) {
                            //     // Sum scores directly without weightage
                            //     $totalScore = 0;
                            //     $scoreCount = count($values);

                            //     foreach ($values as $index => $scoreData) {
                            //         $totalScore += $scoreData['score'];
                            //     }

                            //     // Calculate the average score
                            //     $averageScore = $scoreCount > 0 ? $totalScore / $scoreCount : 0;

                            //     // Store the average score at this index
                            //     $summedScores[$formName][$key][] = ["score" => $averageScore];
                            // } else {
                            // Apply weightage if peers or subordinate count is non-zero
                            foreach ($values as $index => $scoreData) {
                                // Ensure the array exists for this index
                                if (!isset($summedScores[$formName][$key][$index])) {
                                    $summedScores[$formName][$key][$index] = ["score" => 0];
                                }
                                // Accumulate the score
                                $summedScores[$formName][$key][$index]['score'] += $scoreData['score'];
                            }
                            // }

                        }
                    }
                }
            }
        }

        // Format the summary response
        $summary = [
            "formGroupName" => "Appraisal Form",
            "formData" => [],
            "contributor_type" => "summary"
        ];

        // Add KPI first if exists
        if (isset($summedScores['KPI'])) {
            $kpiForm = [
                "formName" => "KPI"
            ];
            foreach ($summedScores['KPI'] as $key => $value) {
                $kpiForm[$key] = $value; // Include KPI data in the summary
            }
            $summary['formData'][] = $kpiForm; // Add KPI to the summary
        }

        // Add Culture and Leadership
        foreach (['Culture', 'Leadership'] as $formName) {
            if (isset($summedScores[$formName])) {
                $form = [
                    "formName" => $formName
                ];
                foreach ($summedScores[$formName] as $key => $scores) {
                    $form[$key] = $scores; // Include Culture or Leadership data in the summary
                }
                $summary['formData'][] = $form; // Add Culture or Leadership to the summary
            }
        }

        // Return both calculated data and summary
        return [
            'calculated_data' => $calculatedFormData,
            'summary' => $summary
        ];
    }

    public function exportAppraisalDetail(Request $request)
    {
        $data = $request->input('data'); // Retrieve the data sent by DataTable
        $headers = $request->input('headers'); // Dynamic headers from the request
        $batchSize = $request->input('batchSize', 500);
        $userID = Auth()->user()->id;

        $isZip = count($data) > $batchSize;

        $job = ExportAppraisalDetails::dispatch($this->appService, $data, $headers, $userID, $batchSize);

        // Log::info('Dispatched job:', ['job' => $job]);

        return response()->json([
            'message' => 'Export is being processed in the background.',
            'isZip' => $isZip
            // 'message' => 'Your file is being processed, you will be notified when it is ready for download.',
        ]);

        // $job = dispatch(new ExportAppraisalDetails($this->appService, $data, $headers));

        // // Return a response indicating the export is processing, and include a task ID if needed
        // return response()->json([
        //     'message' => 'Export is being processed in the background.',
        //     'job_id' => $job->getJobId() // Pass job ID to check the status later
        // ]);

    }

    public function checkFileAvailability(Request $request)
    {
        $fileName = $request->input('file'); // Get the file name from the request

        // Define the path where files are stored
        $filePath = 'exports/' . $fileName;

        // Check if the file exists
        if (Storage::disk('public')->exists($filePath)) {
            return response()->json(['exists' => true, 'filePath' => $filePath]);
        } else {
            return response()->json(['exists' => false, 'message' => 'File not found.']);
        }
    }

    /**
     * Download a file from the 'exports' directory.
     *
     * @param  string  $fileName
     * @return \Illuminate\Http\Response
     */
    public function downloadFile($fileName)
    {
        $filePath = 'exports/' . $fileName;

        // Check if file exists and download
        if (Storage::disk('public')->exists($filePath)) {
            return Storage::disk('public')->download($filePath);
        } else {
            return response()->json(['message' => 'File not found.'], 404);
        }
    }
    public function deleteFile($fileName)
    {
        $filePath = 'exports/' . $fileName;

        // Check if file exists and download
        if (Storage::disk('public')->exists($filePath)) {
            return Storage::disk('public')->delete($filePath);
        } else {
            return response()->json(['message' => 'File not found.'], 404);
        }
    }

}
