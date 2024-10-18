<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Models\AppraisalContributor;
use App\Models\ApprovalLayerAppraisal;
use App\Models\ApprovalRequest;
use App\Models\Calibration;
use App\Models\EmployeeAppraisal;
use Illuminate\Http\Request;
use App\Services\AppService;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use stdClass;

use function Pest\Laravel\json;

class AppraisalController extends Controller
{
    protected $category;
    protected $user;
    protected $appService;

    public function __construct(AppService $appService)
    {
        $this->appService = $appService;
        $this->user = Auth()->user()->employee_id;
        $this->category = 'Appraisal';
    }
    
    public function index(Request $request)
    {
        $period = 2024;

        $query = EmployeeAppraisal::with(['appraisal' => function($query) use ($period) {
                $query->where('period', $period);
            }, 'appraisalLayer.approver', 'appraisalContributor', 'calibration']);

        $datas = $query->get()->map(function ($employee) {
            $approvalStatus = [];

            foreach ($employee->appraisalLayer as $layer) {
                if ($layer->layer_type !== 'manager') {
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
                            ->where('contributor_type', '!=', 'manager')
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
                }
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
                $popoverContent[] = "L" . ($layerIndex + 1) . ": " . ($layer['approver_name'] .' ('.$layer['approver_id'].')' ?? 'N/A');
            }

            // Add peer layers
            foreach ($approvalStatus['peers'] ?? [] as $layerIndex => $layer) {
                $popoverContent[] = "P" . ($layerIndex + 1) . ": " . ($layer['approver_name'] .' ('.$layer['approver_id'].')' ?? 'N/A');
            }

            // Add subordinate layers
            foreach ($approvalStatus['subordinate'] ?? [] as $layerIndex => $layer) {
                $popoverContent[] = "S" . ($layerIndex + 1) . ": " . ($layer['approver_name'] .' ('.$layer['approver_id'].')' ?? 'N/A');
            }

            // Join content with line breaks
            $popoverText = implode("<br>", $popoverContent);

            $appraisal = $employee->appraisal && $employee->appraisal->first() 
                            ? $employee->appraisal->first()->id 
                            : null;

            return [
                'id' => $employee->employee_id,
                'name' => $employee->fullname,
                'approvalStatus' => $approvalStatus,
                'finalScore' => $appraisal 
                ? $this->calculateFinalScore($employee->employee_id, $appraisal) 
                : '-',
                'popoverContent' => $popoverText, // Add popover content here
            ];
        });

        $maxCalibrator = min($datas->map(function($employee) {
            return isset($employee['approvalStatus']['calibrator']) 
                ? count($employee['approvalStatus']['calibrator']) 
                : 0;
        })->max(), 10);
        
        $layerHeaders = array_map(function($num) {
            return 'L' . ($num + 1);
        }, range(0, $maxCalibrator - 1));

        $layerBody = array_map(function($num) {
            return ($num + 1);
        }, range(0, $maxCalibrator - 1));

        $parentLink = __('Reports');
        $link = __('Appraisal');

        return view('pages.appraisals.admin.app', compact('datas', 'layerHeaders', 'layerBody', 'link', 'parentLink'));
    }

    public function detail(Request $request)
    {
        $period = 2024;

        try {

            $data = EmployeeAppraisal::with(['appraisalLayer' => function ($query) {
                $query->where('layer_type', '!=', 'calibrator');
            }, 'appraisal' => function ($query) use ($period) {
                $query->where('period', $period);
            }])->where('employee_id', $request->id)->get();

            $data->map(function($item) {

                $appraisal_id = $item->appraisal->first()->id;

                $item->appraisalLayer->map(function($subItem) use ($appraisal_id) {
                    
                    $contributor = AppraisalContributor::select('id','appraisal_id','contributor_type','contributor_id')->where('contributor_type', $subItem->layer_type)->where('contributor_id', $subItem->approver_id)->where('appraisal_id', $appraisal_id)->first();
                    
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

            return view('pages.appraisals.admin.detail', compact('datas', 'groupedData', 'parentLink', 'link'));

        } catch (Exception $e) {
            Log::error('Error in index method: ' . $e->getMessage());

            // Return empty data to be consumed in the Blade template
            return view('pages.appraisals.admin.app', [
                'datas' => []
            ]);
        }
    }

    public function getDetailData(Request $request)
    {
            $user = Auth::user()->employee_id;
            $period = 2024;
            $contributorId = $request->id;

            $parts = explode('_', $contributorId);
            
            // Access the separated parts
            $id = $parts[0];
            $formId = $parts[1];

            if ($id == 'summary') {
                $datasQuery = AppraisalContributor::with(['employee'])->where('appraisal_id', $formId);
                $datas = $datasQuery->get();

                $data = [];
                $appraisalDataCollection = [];
                $goalDataCollection = [];

                $formGroupContent = storage_path('../resources/testFormGroup.json');
                if (!File::exists($formGroupContent)) {
                    $appraisalForm = ['data' => ['formData' => []]];
                } else {
                    $appraisalForm = json_decode(File::get($formGroupContent), true);
                }

                $cultureData = $this->getDataByName($appraisalForm['data']['formData'], 'Culture') ?? [];
                $leadershipData = $this->getDataByName($appraisalForm['data']['formData'], 'Leadership') ?? [];

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
                        $appraisalDataCollection[] = $appraisalData;
                    }

                    // Get goal form data for each record
                    $goalData = [];
                    if ($request->goal && $request->goal->form_data) {
                        $goalData = json_decode($request->goal->form_data, true);
                        $goalDataCollection[] = $goalData;
                    }

                    
                    // Combine the appraisal and goal data for each contributor
                    $employeeData = $request->employee; // Get employee data
                    
                    // return response()->json($appraisalData);

                    // $formData[] = $this->appService->combineFormData($appraisalData, $goalData, 'employee', $employeeData);
                    // return response()->json($formData);
                    $formData[] = $appraisalData;

                }

                
                // return response()->json($formData);
                $summaryScores = $this->summarizeScoresPerItem($formData);

                $formData = $this->appService->combineFormData($summaryScores, $goalData, 'employee', $employeeData);

                $appraisalData = $summaryScores;

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

            }else{

                $datasQuery = AppraisalContributor::with(['employee'])->where('id', $id);
    
                $datas = $datasQuery->get();
                
                $formattedData = $datas->map(function($item) {
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
                
                $formGroupContent = storage_path('../resources/testFormGroup.json');
                if (!File::exists($formGroupContent)) {
                    $appraisalForm = ['data' => ['formData' => []]];
                } else {
                    $appraisalForm = json_decode(File::get($formGroupContent), true);
                }
                
                $cultureData = $this->getDataByName($appraisalForm['data']['formData'], 'Culture') ?? [];
                $leadershipData = $this->getDataByName($appraisalForm['data']['formData'], 'Leadership') ?? [];
    
                $formData = $this->appService->combineFormData($appraisalData, $goalData, 'employee', $employeeData);
    
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
    
                $path = storage_path('../resources/goal.json');
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

            }

            return view('components.appraisal-card', compact('datas', 'formData', 'appraisalData'));

    }

    private function getDataByName($data, $name) {
        foreach ($data as $item) {
            if ($item['name'] === $name) {
                return $item['data'];
            }
        }
        return null;
    }

    private function summarizeScoresPerItem($data) 
    {
        try {
            $averageFormData = [
                'KPI' => [],
                'Culture' => [],
                'Leadership' => []
            ];

            // Process each form in the data
            foreach ($data as $form) {

                // Process form data
                foreach ($form['formData'] as $formItem) {
                    $formName = $formItem['formName'];

                    // Format KPI achievements
                    if ($formName === 'KPI') {
                        $kpiFormatted = [];
                        foreach ($formItem as $key => $kpiData) {
                            if (is_numeric($key) && isset($kpiData['achievement'])) {
                                $kpiFormatted[$key] = [
                                    'achievement' => $kpiData['achievement']
                                ];
                            }
                        }
                        // Add formName for KPI
                        $kpiFormatted['formName'] = 'KPI';
                        $averageFormData['KPI'] = $kpiFormatted; // No KPI key, just append to the array
                        continue;
                    }


                    // Process Culture and Leadership scores
                    if (in_array($formName, ['Culture', 'Leadership'])) {
                        foreach ($formItem as $key => $value) {
                            if (is_numeric($key)) {
                                if (!isset($averageFormData[$formName][$key])) {
                                    $averageFormData[$formName][$key] = [
                                        'totalScore' => [],
                                        'count' => 0
                                    ];
                                }

                                foreach ($value as $scoreIndex => $scoreData) {
                                    if (!isset($averageFormData[$formName][$key]['totalScore'][$scoreIndex])) {
                                        $averageFormData[$formName][$key]['totalScore'][$scoreIndex] = 0;
                                    }

                                    // Sum the scores for averaging later
                                    $averageFormData[$formName][$key]['totalScore'][$scoreIndex] += floatval($scoreData['score']);
                                }
                                // Count occurrences for averaging
                                $averageFormData[$formName][$key]['count']++;
                            }
                        }
                    }
                }
            }

            // Calculate averages for Culture and Leadership
            foreach (['Culture', 'Leadership'] as $formName) {
                foreach ($averageFormData[$formName] as $key => $scoreData) {
                    foreach ($scoreData['totalScore'] as $scoreIndex => $totalScore) {
                        // Calculate average and store
                        $averageFormData[$formName][$key][$scoreIndex] = [
                            'score' => round($totalScore / $scoreData['count'], 2)
                        ];
                    }
                    // Remove the temporary 'totalScore' and 'count'
                    unset($averageFormData[$formName][$key]['totalScore']);
                    unset($averageFormData[$formName][$key]['count']);
                }
            }

            // Structure the final result by flattening
            $flattenedFormData = [];
            foreach ($averageFormData as $formName => $data) {
                $data['formName'] = $formName;
                $flattenedFormData[] = $data;
            }

            // Return success response with formGroupName and formData
            return [
                'status' => 'success',
                'formGroupName' => 'Appraisal Form',
                'formData' => $flattenedFormData
            ];

        } catch (\Exception $e) {
            // Handle any errors
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ];
        }
    }


    public function getPeerData($peerId)
    {
        // Sample data - replace with your actual data logic
        $peerData = [
            'P1' => [
                'name' => 'Peer Group 1',
                'members' => ['John', 'Jane', 'Mike'],
                'status' => 'Active'
            ],
            'P2' => [
                'name' => 'Peer Group 2',
                'members' => ['Sarah', 'Tom', 'Lisa'],
                'status' => 'Active'
            ],
            'P3' => [
                'name' => 'Peer Group 3',
                'members' => ['Alex', 'Emma', 'Ryan'],
                'status' => 'Inactive'
            ]
        ];

        return response()->json($peerData[$peerId] ?? []);
    }

    private function calculateFinalScore($employeeId, $appraisalId)
    {
        // Retrieve the score, checking if a rating exists for the given employee and appraisal
        $score = Appraisal::select('id', 'employee_id', 'rating')
            ->where('employee_id', $employeeId)
            ->where('id', $appraisalId)
            ->whereNotNull('rating')
            ->first();

        // Return the rating if it exists, otherwise return '-'
        return $score ? $score->rating : '-';
    }

}
