<?php

namespace App\Http\Controllers;

use App\Models\Appraisal;
use App\Models\AppraisalContributor;
use App\Models\Approval;
use App\Models\ApprovalLayerAppraisal;
use App\Models\ApprovalRequest;
use App\Models\ApprovalSnapshots;
use App\Models\Calibration;
use App\Models\Employee;
use App\Models\EmployeeAppraisal;
use App\Models\Goal;
use App\Models\KpiUnits;
use App\Services\AppService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use PhpParser\Node\Expr\Empty_;
use stdClass;

use function PHPUnit\Framework\isEmpty;

class AppraisalTaskController extends Controller
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

    public function index(Request $request) {
        try {

            $employee = EmployeeAppraisal::with(['schedule'])->first();

            $user = $this->user;
            $period = $this->appService->appraisalPeriod();
            $filterYear = $request->input('filterYear');
            
            $dataTeams = ApprovalLayerAppraisal::with(['approver', 'contributors' => function($query) use ($user, $period) {
                $query->where('contributor_id', $user)->where('period', $period);
            }])
            ->where('approver_id', $user)
            ->where('layer_type', 'manager')
            ->get();

            $filteredDataTeams = $dataTeams->filter(function($item) {
                // Memeriksa apakah contributors kosong
                return $item->contributors->isEmpty();
            });

            $notifDataTeams = $filteredDataTeams->count();

            $data360 = ApprovalLayerAppraisal::with(['approver', 'contributors' => function($query) use ($period) {
                $query->where('contributor_id', Auth::user()->employee_id)->where('period', $period);
            }, 'appraisal'])
            ->where('approver_id', $user)
            ->where('layer_type', '!=', 'manager')
            ->where('layer_type', '!=', 'calibrator')
            ->get()
            ->filter(function($item) {
                // Hanya return item yang memiliki data appraisal
                return $item->appraisal !== null;
            });

            $filteredData360 = $data360->filter(function($item) {
                // Memeriksa apakah contributors kosong
                return $item->contributors->isEmpty();
            });

            $notifData360 = $filteredData360->count();
            
            $contributors = $data360->pluck('contributors');

            $parentLink = __('Appraisal');
            $link = __('Task Box');

            return view('pages.appraisals-task.app', compact('notifDataTeams', 'notifData360', 'contributors', 'link', 'parentLink'));

        } catch (Exception $e) {
            Log::error('Error in index method: ' . $e->getMessage());

            // Return empty data to be consumed in the Blade template
            return view('pages.appraisals-task.app', [
                'data' => [],
                'link' => 'My Appraisal',
                'parentLink' => 'Appraisal',
                'formData' => ['formData' => []],
                'uomOption' => [],
                'typeOption' => [],
                'goals' => null,
                'selectYear' => [],
                'adjustByManager' => null,
                'appraisalData' => []
            ]);
        }
    }

    public function getTeamData(Request $request)
    {
        $user = $this->user;
        $period = $this->appService->appraisalPeriod();
        $filterYear = $request->input('filterYear');

        $datas = ApprovalLayerAppraisal::with(['employee', 'approver', 'contributors' => function($query) use ($user, $period) {
            $query->where('contributor_id', $user)->where('period', $period);
        }, 'goal' => function($query) use ($period) {
            $query->where('period', $period);
        }, 'approvalRequest' => function($query) use ($period, $user) {
            $query->where('category', 'Appraisal')->where('period', $period)->where('current_approval_id', $user);
        }])
        ->where('approver_id', $user)
        ->where('layer_type', 'manager')
        ->get();

        $datas->map(function($item) use ($period) {

            // Check if goal and contributors exist and if form_data is not null
            $goalData = $item && $item->goal->isNotEmpty() && $item->goal->first()->form_data 
                ? json_decode($item->goal->first()->form_data, true) 
                : [];
        
            $appraisalData = $item && $item->contributors->isNotEmpty() && $item->contributors->first()->form_data 
                ? json_decode($item->contributors->first()->form_data, true) 
                : [];

            if (!Empty($appraisalData)) {
                $period = $item->contributors->first()->period;
            }
        
            // Get employee data
            $employeeData = $item->first()->employee ?? null;
        
            // Combine form data
            $formData = $this->appService->combineFormData($appraisalData, $goalData, 'manager', $employeeData, $period);
        
            // Assign form scores to the item
            $item->total_score = round($formData['totalScore'], 2) ?? [];
            $item->kpi_score = round($formData['kpiScore'], 2) ?? [];
            $item->culture_score = round($formData['cultureScore'], 2) ?? [];
            $item->leadership_score = round($formData['leadershipScore'], 2) ?? [];
        
            return $item;
        });

        // Prepare data for DataTables
        $data = [];


        foreach ($datas as $index => $team) {
            $data[] = [
                'index' => $index + 1,
                'employee' => [
                    'fullname' => $team->employee->fullname,
                    'employee_id' => $team->employee->employee_id,
                    'designation' => $team->employee->designation_name,
                    'office_area' => $team->employee->office_area,
                    'group_company' => $team->employee->group_company,
                ],
                'kpi' => [
                    'kpi_status' => $team->contributors->isNotEmpty(),
                    'total_score' => $team->total_score, // KPI Score
                    'kpi_score' => $team->kpi_score, // KPI Score
                    'culture_score' => $team->culture_score, // culture Score
                    'leadership_score' => $team->leadership_score, // leadership Score
                ],
                'approval_date' => $team->approvalRequest->first() ? $this->appService->formatDate($team->approvalRequest->first()->created_at) : '-',
                'action' => view('components.action-buttons', ['team' => $team])->render(), // Render action buttons
            ];
        }

        // Return the response as JSON
        return response()->json($data);
    }

    public function get360Data(Request $request)
    {
        $user = $this->user;
        $period = $this->appService->appraisalPeriod();
        $filterYear = $request->input('filterYear');

        $datas = ApprovalLayerAppraisal::with(['employee', 'approver', 'contributors' => function($query) use ($user, $period) {
            $query->where('contributor_id', $user)->where('period', $period);
        }, 'goal' => function($query) use ($period) {
            $query->where('period', $period);
        }, 'approvalRequest' => function($query) use ($period) {
            $query->where('category', 'Appraisal')->where('period', $period);
        }])
        ->where('approver_id', $user)
        ->whereIn('layer_type', ['peers', 'subordinate']);
        
        $datas = $datas->has('approvalRequest')->get();

        $datas->map(function($item) use ($period){
            // Check if goal and contributors exist and if form_data is not null
            $goalData = $item && $item->goal->isNotEmpty() && $item->goal->first()->form_data 
                ? json_decode($item->goal->first()->form_data, true) 
                : [];
        
            $appraisalData = $item && $item->contributors->isNotEmpty() && $item->contributors->first()->form_data 
                ? json_decode($item->contributors->first()->form_data, true) 
                : [];

            if (!Empty($appraisalData)) {
                $period = $item->contributors->first()->period;
            }
        
            // Get employee data
            $employeeData = $item->first()->employee ?? null;
        
            // Combine form data
            $formData = $this->appService->combineFormData($appraisalData, $goalData, 'manager', $employeeData, $period);
        
            // Assign form scores to the item
            $item->kpi_score = round($formData['kpiScore'], 2) ?? [];
            $item->culture_score = round($formData['cultureScore'], 2) ?? [];
            $item->leadership_score = round($formData['leadershipScore'], 2) ?? [];
        
            return $item;
        });

        // Prepare data for DataTables
        $data = [];


        foreach ($datas as $index => $team) {
            $data[] = [
                'index' => $index + 1,
                'employee' => [
                    'fullname' => $team->employee->fullname,
                    'employee_id' => $team->employee->employee_id,
                    'designation' => $team->employee->designation_name,
                    'office_area' => $team->employee->office_area,
                    'group_company' => $team->employee->group_company,
                    'category' => $team->layer_type,
                ],
                'kpi' => [
                    'kpi_status' => $team->contributors->isNotEmpty(),
                    'kpi_score' => $team->kpi_score, // KPI Score
                    'culture_score' => $team->culture_score, // culture Score
                    'leadership_score' => $team->leadership_score, // leadership Score
                ],
                'approval_date' => $team->approvalRequest->first() ? $this->appService->formatDate($team->approvalRequest->first()->created_at) : '-',
                'action' => view('components.action-buttons', ['team' => $team])->render(), // Render action buttons
            ];
        }

        // Return the response as JSON
        return response()->json($data);
    }

    public function initiate(Request $request)
    {
        $user = $this->user;

        $step = $request->input('step', 1);

        $period = $this->appService->appraisalPeriod();

        $approval = ApprovalLayerAppraisal::select('approver_id')->where('employee_id', $request->id)->where('layer', 1)->first();

        $goal = Goal::with(['employee'])->where('employee_id', $request->id)->where('period', $period)->first();

        $calibrator = ApprovalLayerAppraisal::where('layer', 1)->where('layer_type', 'calibrator')->where('employee_id', $request->id)->value('approver_id');

        if (!$calibrator) {
            Session::flash('error', "No Layer assigned, please contact admin to assign layer");
            return redirect()->back();
        }
        
        if ($goal) {
            $goalData = json_decode($goal->form_data, true);
        } else {
            Session::flash('error', "Goals for not found.");
            return redirect()->back();
        }

        // Get form group appraisal
        $formGroupData = $this->appService->formGroupAppraisal($request->id, 'Appraisal Form');                
        
        $formTypes = $formGroupData['data']['form_names'] ?? [];
        $formDatas = $formGroupData['data']['form_appraisals'] ?? [];
                
        $filteredFormData = array_filter($formDatas, function($form) use ($formTypes) {
            return in_array($form['name'], $formTypes);
        });

        $ratings = $formGroupData['data']['rating'];

        $filteredFormDatas = [
            'viewCategory' => 'initiate',
            'filteredFormData' => $filteredFormData,
        ];
        
        $parentLink = __('Appraisal');
        $link = 'Initiate Appraisal';

        // Pass the data to the view
        return view('pages.appraisals-task.initiate', compact('step', 'parentLink', 'link', 'filteredFormDatas', 'formGroupData', 'goal', 'approval', 'goalData', 'user', 'ratings'));
    }

    public function approval(Request $request)
    {
        $user = $this->user;
        $period = $this->appService->appraisalPeriod();

        $step = $request->input('step', 1);

        $approval = ApprovalLayerAppraisal::select('approver_id')->where('employee_id', $request->id)->where('layer', 1)->first();

        $goal = Goal::with(['employee'])->where('employee_id', $request->id)->where('period', $period)->first();

        if ($goal) {
            $goalData = json_decode($goal->form_data, true);
        } else {
            Session::flash('error', "Goals for not found.");
            return redirect()->back();
        }

        // Read the content of the JSON files
        $formGroupData = $this->appService->formGroupAppraisal($request->id, 'Appraisal Form Task');
        
        $formTypes = $formGroupData['data']['form_names'] ?? [];
        $formDatas = $formGroupData['data']['form_appraisals'] ?? [];

        
        $filteredFormData = array_filter($formDatas, function($form) use ($formTypes) {
            return in_array($form['name'], $formTypes);
        });

        $filteredFormDatas = [
            'viewCategory' => 'initiate',
            'filteredFormData' => $filteredFormData,
        ];
                
        $parentLink = __('Appraisal');
        $link = 'Initiate Appraisal';

        // Pass the data to the view
        return view('pages.appraisals-task.approval', compact('step', 'parentLink', 'link', 'filteredFormDatas', 'formGroupData', 'goal', 'approval', 'goalData', 'user'));
    }

    public function review(Request $request)
    {
        $user = $this->user;
        $period = $this->appService->appraisalPeriod();

        $step = $request->input('step', 1);
        
        $goals = Goal::with(['employee'])->where('employee_id', $request->id)->where('period', $period)->first();
        
        $appraisal = Appraisal::with(['employee', 'approvalRequest' => function($query) use ($period) {
            $query->where('category', 'Appraisal')->where('period', $period);
        }])->where('employee_id', $request->id)->where('period', $period)->first();

        $approval = ApprovalLayerAppraisal::where('employee_id', $appraisal->employee_id)->where('approver_id', Auth::user()->employee_id )->first();

        $appraisalId = $appraisal->id;
        
        $data = json_decode($appraisal['form_data'], true);

        $achievement = array_filter($data['formData'], function ($form) {
            return $form['formName'] === 'KPI';
        });

        if ($goals) {
            $goalData = json_decode($goals->form_data, true);
        } else {
            Session::flash('error', "Goals for not found.");
            return redirect()->back();
        }

        foreach ($achievement[0] as $key => $formItem) {
            if (isset($goalData[$key])) {
                $combinedData[$key] = array_merge($formItem, $goalData[$key]);
            } else {
                $combinedData[$key] = $formItem;
            }
        }

        $form_name = $approval->layer_type == 'manager' ? 'Appraisal Form Review' : 'Appraisal Form 360' ;

        $formGroupData = $this->appService->formGroupAppraisal($request->id, $form_name);   
            
        $formTypes = $formGroupData['data']['form_names'] ?? [];
        $formDatas = $formGroupData['data']['form_appraisals'] ?? [];
        
        
        $filteredFormData = array_filter($formDatas, function($form) use ($formTypes) {
            return in_array($form['name'], $formTypes);
        });
        
        // Read the contents of the JSON file
        $formData = json_decode($appraisal->form_data, true);
        
        $formCount = count($formData);
        
        $data = json_decode($appraisal->form_data, true);

        
        $selfReviewData = [];
        foreach ($formData['formData'] as $item) {
            if ($item['formName'] === 'KPI') {
                $selfReviewData = array_slice($item, 1);
                break;
            }
        }

        
        // Add the achievements to the goalData
        foreach ($goalData as $index => &$goal) {
            if (isset($selfReviewData[$index])) {
                $goal['actual'] = $selfReviewData[$index]['achievement'];
            } else {
                $goal['actual'] = [];
            }
        }

        
        foreach ($formData['formData'] as &$form) {                
            if ($form['formName'] === 'Culture') {
                foreach ($form as $key => &$value) {
                    if (is_numeric($key)) {
                        $scores = [];
                        foreach ($value as $score) {
                            $scores[] = $score['score'];
                        }
                        $value = ['score' => $scores];
                    }
                }
            }
            if ($form['formName'] === 'Leadership') {
                foreach ($form as $key => &$value) {
                    if (is_numeric($key)) {
                        $scores = [];
                        foreach ($value as $score) {
                            $scores[] = $score['score'];
                        }
                        $value = ['score' => $scores];
                    }
                }
            }
        }

        // Merge the scores
        $filteredFormData = $this->appService->mergeScores($formData, $filteredFormData);

        $filteredFormDatas = [
            'viewCategory' => 'Review',
            'filteredFormData' => $filteredFormData,
        ];

        $ratings = $formGroupData['data']['rating'];
        
        $parentLink = __('Appraisal');
        $link = 'Review Appraisal';

        // Pass the data to the view
        return view('pages.appraisals-task.review', compact('step', 'parentLink', 'link', 'filteredFormDatas', 'formGroupData', 'goal', 'goals', 'approval', 'goalData', 'user', 'achievement', 'appraisalId', 'ratings', 'appraisal'));
    }

    public function detail(Request $request)
    {
        try {
            $user = Auth::user()->employee_id;
            $filterYear = $request->input('filterYear');
            $contributorId = $request->id;

            $datasQuery = AppraisalContributor::with(['employee'])->where('id', $contributorId);

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

            if (!Empty($appraisalData)) {
                $period = $datas->first()->period;
            } else {
                $period = $this->appService->appraisalPeriod();
            }
            

            $employeeData = $datas->first()->employee;

            // Setelah data digabungkan, gunakan combineFormData untuk setiap jenis kontributor

            $formGroupData = $this->appService->formGroupAppraisal($employeeData->employee_id, 'Appraisal Form');
            
            if (!$formGroupData) {
                $appraisalForm = ['data' => ['formData' => []]];
            } else {
                $appraisalForm = $formGroupData;
            }
            
            $cultureData = $this->getDataByName($appraisalForm['data']['form_appraisals'], 'Culture') ?? [];
            $leadershipData = $this->getDataByName($appraisalForm['data']['form_appraisals'], 'Leadership') ?? [];

            $formData = $this->appService->combineFormData($appraisalData, $goalData, 'employee', $employeeData, $period);

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

            $parentLink = __('Appraisal');
            $link = __('Details');

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

            return view('pages.appraisals-task.detail', compact('datas', 'link', 'parentLink', 'formData', 'uomOption', 'typeOption', 'goals', 'selectYear', 'appraisalData'));

        } catch (Exception $e) {
            Log::error('Error in index method: ' . $e->getMessage());

            // Return empty data to be consumed in the Blade template
            return view('pages.appraisals-task.detail', [
                'data' => [],
                'link' => 'My Appraisal',
                'parentLink' => 'Appraisal',
                'formData' => ['formData' => []],
                'uomOption' => [],
                'typeOption' => [],
                'goals' => null,
                'selectYear' => [],
                'appraisalData' => []
            ]);
        }
    }

    private function getDataByName($data, $name) {
        foreach ($data as $item) {
            if ($item['name'] === $name) {
                return $item['data'];
            }
        }
        return null;
    }

    public function storeInitiate(Request $request)
    {
        $submit_status = 'Submitted';
        $period = $this->appService->appraisalPeriod();

        // Validate the request data
        $validatedData = $request->validate([
            'form_group_id' => 'required|string',
            'employee_id' => 'required|string|size:11',
            'approver_id' => 'required|string|size:11',
            'formGroupName' => 'required|string|min:5|max:100',
            'formData' => 'required|array',
        ]);

        $contributorData = ApprovalLayerAppraisal::select('approver_id', 'layer_type')->where('employee_id', $validatedData['employee_id'])->get();

        // Extract formGroupName
        $formGroupName = $validatedData['formGroupName'];
        $formData = $validatedData['formData'];

        // Create the array structure
        $datas = [
            'formGroupName' => $formGroupName,
            'formData' => $formData,
        ];

        $goals = Goal::with(['employee'])->where('employee_id', $validatedData['employee_id'])->where('period', $period)->first();

        $goalData = json_decode($goals->form_data, true);

        // Create a new Appraisal instance and save the data
        $appraisal = new Appraisal;
        $appraisal->id = Str::uuid();
        $appraisal->goals_id = $goals->id;
        $appraisal->employee_id = $validatedData['employee_id'];
        $appraisal->form_group_id = $validatedData['form_group_id'];
        $appraisal->category = $this->category;
        $appraisal->form_data = json_encode($datas); // Store the form data as JSON
        $appraisal->form_status = $submit_status;
        $appraisal->period = $period;
        $appraisal->created_by = Auth::user()->id;
        
        $appraisal->save();

        $contributorData = ApprovalLayerAppraisal::select('approver_id', 'layer_type')->where('approver_id', Auth::user()->employee_id)->where('employee_id', $validatedData['employee_id'])->first();

        $firstCalibrator = ApprovalLayerAppraisal::where('layer', 1)->where('layer_type', 'calibrator')->where('employee_id', $validatedData['employee_id'])->value('approver_id');

        $kpiUnit = KpiUnits::with(['masterCalibration'])->where('employee_id', $firstCalibrator)->first();
        
        $calibrationGroupID = $kpiUnit->masterCalibration->id_calibration_group;

        $formDatas = $this->appService->combineFormData($datas, $goalData, $contributorData->layer_type, $goals->employee, $period);

        AppraisalContributor::create([
            'appraisal_id' => $appraisal->id,
            'employee_id' => $validatedData['employee_id'],
            'contributor_id' => $contributorData->approver_id,
            'contributor_type' => $contributorData->layer_type,
            // Add additional data here
            'form_data' => json_encode($datas),
            'rating' => $formDatas['contributorRating'],
            'status' => 'Approved',
            'period' => $period,
            'created_by' => Auth::user()->id
        ]);
        
        $snapshot =  new ApprovalSnapshots;
        $snapshot->id = Str::uuid();
        $snapshot->form_id = $appraisal->id;
        $snapshot->form_data = json_encode($datas);
        $snapshot->employee_id = $validatedData['employee_id'];
        $snapshot->created_by = Auth::user()->id;
        
        $snapshot->save();

        $approval = new ApprovalRequest();
        $approval->form_id = $appraisal->id;
        $approval->category = $this->category;
        $approval->period = $period;
        $approval->employee_id = $validatedData['employee_id'];
        $approval->current_approval_id = $validatedData['approver_id'];
        $approval->created_by = Auth::user()->id;
        $approval->status = 'Approved';
        // Set other attributes as needed
        $approval->save();

        $calibration = new Calibration();
        $calibration->id_calibration_group = $calibrationGroupID;
        $calibration->appraisal_id = $appraisal->id;
        $calibration->employee_id = $validatedData['employee_id'];
        $calibration->approver_id = $firstCalibrator;
        $calibration->period = $period;
        $calibration->created_by = Auth::user()->id;

        $calibration->save();


        // Return a response, such as a redirect or a JSON response
        return redirect('appraisals-task')->with('success', 'Appraisal submitted successfully.');
    }

    public function storeReview(Request $request)
    {
        $period = $this->appService->appraisalPeriod();

        // Validate the request data
        $validatedData = $request->validate([
            'employee_id' => 'required|string|size:11',
            'approver_id' => 'required|string|size:11',
            'appraisal_id' => 'required|string',
            'formGroupName' => 'required|string|min:5|max:100',
            'formData' => 'required|array',
        ]);

        $contributorData = ApprovalLayerAppraisal::select('approver_id', 'layer_type')->where('approver_id', Auth::user()->employee_id)->where('employee_id', $validatedData['employee_id'])->first();

        $goals = Goal::with(['employee'])->where('employee_id', $validatedData['employee_id'])->where('period', $period)->first();

        $goalData = json_decode($goals->form_data, true);
        
        // Extract formGroupName
        $formGroupName = $validatedData['formGroupName'];
        $formData = $validatedData['formData'];

        // Create the array structure
        $datas = [
            'formGroupName' => $formGroupName,
            'formData' => $formData,
        ];
        
        $formDatas = $this->appService->combineFormData($datas, $goalData, $contributorData->layer_type, $goals->employee, $period);

        AppraisalContributor::create([
            'appraisal_id' => $validatedData['appraisal_id'],
            'employee_id' => $validatedData['employee_id'],
            'contributor_id' => $contributorData->approver_id,
            'contributor_type' => $contributorData->layer_type,
            // Add additional data here
            'form_data' => json_encode($datas),
            'rating' => $formDatas['contributorRating'],
            'status' => 'Approved',
            'period' => $period,
            'created_by' => Auth::user()->id
        ]);
        
        $snapshot =  new ApprovalSnapshots();
        $snapshot->id = Str::uuid();
        $snapshot->form_id = $validatedData['appraisal_id'];
        $snapshot->form_data = json_encode($datas);
        $snapshot->employee_id = $validatedData['employee_id'];
        $snapshot->created_by = Auth::user()->id;
        
        $snapshot->save();

        if($contributorData->layer_type == 'manager'){

            $nextLayer = ApprovalLayerAppraisal::where('approver_id', $validatedData['approver_id'])->where('layer_type', 'manager')
                                    ->where('employee_id', $validatedData['employee_id'])->max('layer');

            // Cari approver_id pada layer selanjutnya
            $nextApprover = ApprovalLayerAppraisal::where('layer', $nextLayer + 1)->where('layer_type', 'manager')->where('employee_id', $validatedData['employee_id'])->value('approver_id');

            $firstCalibrator = ApprovalLayerAppraisal::where('layer', 1)->where('layer_type', 'calibrator')->where('employee_id', $validatedData['employee_id'])->value('approver_id');

            $kpiUnit = KpiUnits::with(['masterCalibration'])->where('employee_id', $firstCalibrator)->first();

            $calibrationGroupID = $kpiUnit->masterCalibration->id_calibration_group;

            if (!$nextApprover) {
                $approver = $validatedData['approver_id'];
                $statusRequest = 'Approved';
                $statusForm = 'Approved';
            }else{
                $approver = $nextApprover;
                $statusRequest = 'Pending';
                $statusForm = 'Submitted';
            }

            $calibration = new Calibration();
            $calibration->appraisal_id = $validatedData['appraisal_id'];
            $calibration->id_calibration_group = $calibrationGroupID;
            $calibration->employee_id = $validatedData['employee_id'];
            $calibration->approver_id = $firstCalibrator;
            $calibration->period = $period;
            $calibration->created_by = Auth::user()->id;

            if ($calibration->save()) {

                $model = Appraisal::find($validatedData['appraisal_id']);
                $model->form_data = json_encode($datas);
                $model->form_status = $statusForm;
    
                $model->save();
                
                $approvalRequest = ApprovalRequest::where('form_id', $validatedData['appraisal_id'])->first();
                $approvalRequest->current_approval_id = $approver;
                $approvalRequest->status = $statusRequest;
                $approvalRequest->updated_by = Auth::user()->id;
    
                $approvalRequest->save();
    
    
                $approval = new Approval;
                $approval->request_id = $approvalRequest->id;
                $approval->approver_id = Auth::user()->employee_id;
                $approval->created_by = Auth::user()->id;
                $approval->status = 'Approved';
    
                $approval->save();
            }

        }

        // Return a response, such as a redirect or a JSON response
        return redirect('appraisals-task')->with('success', 'Appraisal submitted successfully.');
    }

}