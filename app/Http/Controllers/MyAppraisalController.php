<?php

namespace App\Http\Controllers;

use App\Exports\UserExport;
use App\Models\Appraisal;
use App\Models\AppraisalContributor;
use App\Models\ApprovalLayerAppraisal;
use App\Models\ApprovalRequest;
use App\Models\ApprovalSnapshots;
use App\Models\EmployeeAppraisal;
use App\Models\FormAppraisal;
use App\Models\FormGroupAppraisal;
use App\Models\Goal;
use App\Models\MasterWeightage;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use RealRashid\SweetAlert\Facades\Alert;
use stdClass;
use App\Services\AppService;
use Illuminate\Support\Facades\Http;

class MyAppraisalController extends Controller
{
    
    protected $category;
    protected $user;
    protected $appService;

    public function __construct(AppService $appService)
    {
        $this->user = Auth::user()->employee_id;
        $this->appService = $appService;
        $this->category = 'Appraisal';
    }

    function formatDate($date)
    {
        // Parse the date using Carbon
        $carbonDate = Carbon::parse($date);

        // Check if the date is today
        if ($carbonDate->isToday()) {
            return 'Today ' . $carbonDate->format('ga');
        } else {
            return $carbonDate->format('d M ga');
        }
    }

    public function index(Request $request) {
        
        $user = $this->user;
        $period = $this->appService->appraisalPeriod();
        $filterYear = $request->input('filterYear');
        $accessMenu = [];

        $employee = EmployeeAppraisal::where('employee_id', $user)->first();
        if ($employee) {
            $accessMenu = json_decode($employee->access_menu, true);
        }

        try {

            // Retrieve approval requests
            $datasQuery = ApprovalRequest::with([
                'employee', 'appraisal.goal', 'appraisal.approvalSnapshots' => function ($query) {
                    $query->where('created_by', Auth::user()->id);
                }, 'updatedBy', 'adjustedBy', 'initiated', 'manager', 'contributor',
                'approval' => function ($query) {
                    $query->with('approverName');
                }
            ])
            ->whereHas('approvalLayerAppraisal', function ($query) use ($user) {
                $query->where('employee_id', $user)->orWhere('approver_id', $user);
            })
            ->where('employee_id', $user)->where('category', $this->category)->where('period', $period);

            if (!empty($filterYear)) {
                $datasQuery->where('period', $filterYear);
            }

            $datas = $datasQuery->get();

            $formattedData = $datas->map(function($item) {
                $item->formatted_created_at = $this->appService->formatDate($item->created_at);

                $item->formatted_updated_at = $this->appService->formatDate($item->updated_at);

                if ($item->sendback_to == $item->employee->employee_id) {
                    $item->name = $item->employee->fullname . ' (' . $item->employee->employee_id . ')';
                    $item->approvalLayer = '';
                } else {
                    $item->name = $item->manager->fullname . ' (' . $item->manager->employee_id . ')';
                    $item->approvalLayer = ApprovalLayerAppraisal::where('employee_id', $item->employee_id)
                                                        ->where('approver_id', $item->current_approval_id)
                                                        ->value('layer');
                }
                return $item;
            });
            
            $adjustByManager = $datas->first()->updatedBy ? 
                ApprovalLayerAppraisal::where('approver_id', $datas->first()->updatedBy->employee_id)
                    ->where('employee_id', $datas->first()->employee_id)
                    ->first() : null;

            $data = [];
            foreach ($formattedData as $request) {
                if ($request->appraisal->goal->form_status != 'Draft' || $request->created_by == Auth::user()->id) {

                    $formGroup = FormGroupAppraisal::with('rating')->find($request->appraisal->form_group_id);

                    $finalRating = null;

                    foreach ($formGroup->rating as $rating) {
                        if ((int)$rating->value === (int)$request->appraisal->rating) {
                            $finalRating = $rating->parameter; // Get the matching parameter
                            break; // Stop looping once a match is found
                        }
                    }

                    $dataApprover = $request->approval->first() ? $request->approval->first()->approverName->fullname : '';

                    $dataItem = new stdClass();
                    $dataItem->request = $request;
                    $dataItem->approver_name = $dataApprover;
                    $dataItem->name = $request->name;
                    $dataItem->approvalLayer = $request->approvalLayer;
                    $dataItem->finalRating = $finalRating;
                    $data[] = $dataItem;
                }
            }

            $goalData = $datas->isNotEmpty() ? json_decode($datas->first()->appraisal->goal->form_data, true) : [];
            
            $form_data = Auth::user()->id == $datas->first()->appraisal->created_by ? $datas->first()->appraisal->approvalSnapshots->form_data : $datas->first()->appraisal->form_data;

            $appraisalData = $datas->isNotEmpty() ? json_decode($form_data, true) : [];

            $groupedContributors = $datas->first()->contributor->groupBy('contributor_type');

            $mergedResults = [];

            // Kumpulan data berdasarkan contributor_type
            $contributorManagerContent = [];
            $combinedPeersData = [];
            $combinedSubData = [];

            // Gabungkan formData untuk setiap contributor_type
            foreach ($groupedContributors as $type => $contributors) {
                // Siapkan array untuk menampung formData dari kontributor dalam grup
                $formDataSets = [];

                foreach ($contributors as $contributor) {
                    // Decode form_data JSON dari setiap kontributor
                    $formData = json_decode($contributor->form_data, true);

                    // Kumpulkan formData untuk setiap kontributor
                    $formDataSets[] = $formData;
                }

                // Gabungkan semua formData menggunakan fungsi mergeFormData
                $mergedFormData = $this->mergeFormData($formDataSets);

                // Simpan hasil gabungan sesuai dengan contributor_type
                if ($type === 'manager') {
                    $contributorManagerContent = $mergedFormData;
                } elseif ($type === 'peers') {
                    $combinedPeersData = $mergedFormData;
                } elseif ($type === 'subordinate') {
                    $combinedSubData = $mergedFormData;
                }
            }

            $employeeData = $datas->first()->employee;

            // Setelah data digabungkan, gunakan combineFormData untuk setiap jenis kontributor
            $formGroupData = $this->appService->formGroupAppraisal($user, 'Appraisal Form');
            
            $cultureData = $this->getDataByName($formGroupData['data']['form_appraisals'], 'Culture') ?? [];
            $leadershipData = $this->getDataByName($formGroupData['data']['form_appraisals'], 'Leadership') ?? [];
            
            $formData = $this->appService->combineFormData($appraisalData, $goalData, 'employee', $employeeData, $datas->first()->period);
            
            if (isset($formData['totalKpiScore'])) {
                $appraisalData['kpiScore'] = round($formData['totalKpiScore'], 2);
                $appraisalData['cultureScore'] = round($formData['totalCultureScore'], 2);
                $appraisalData['leadershipScore'] = round($formData['totalLeadershipScore'], 2);
            }
            
            foreach ($formData['formData'] as &$form) {
                if ($form['formName'] === 'Leadership' || $form['formName'] === 'Leadership 1' || $form['formName'] === 'Leadership 2') {
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

            $parentLink = __('Appraisal');
            $link = __('My Appraisal');

            $selectYear = ApprovalRequest::where('id', $datas->first()->id)->select('period')->get();

            return view('pages.appraisals.my-appraisal', compact('data', 'link', 'parentLink', 'formData', 'uomOption', 'typeOption', 'accessMenu', 'selectYear', 'adjustByManager', 'appraisalData'));

        } catch (Exception $e) {
            Log::error('Error in index method: ' . $e->getMessage());

            // Return empty data to be consumed in the Blade template
            return view('pages.appraisals.my-appraisal', [
                'data' => [],
                'link' => 'My Appraisal',
                'parentLink' => 'Appraisal',
                'formData' => ['formData' => []],
                'uomOption' => [],
                'typeOption' => [],
                'goals' => null,
                'selectYear' => [],
                'adjustByManager' => null,
                'appraisalData' => [],
                'accessMenu' => $accessMenu
            ]);
        }
    }

    public function create(Request $request)
    {
        $step = $request->input('step', 1);

        $period = $this->appService->appraisalPeriod();

        $goalChecked = Goal::where('employee_id', $request->id)->where('period', $period)->exists();

        $goal = Goal::where('employee_id', $request->id)->where('period', $period)->first();

        $appraisal = Appraisal::where('employee_id', $request->id)->where('period', $period)->first();

        $accessMenu = [];

        $employee = EmployeeAppraisal::where('employee_id', $request->id)->first();
        if ($employee) {
            $accessMenu = json_decode($employee->access_menu, true);
        }

        // check goals
        if ($goalChecked) {
            $goalData = json_decode($goal->form_data, true);
        } else {
            Session::flash('error', "Your Goal for $period are not found.");
            return redirect()->route('appraisals');
        }

        if (!$accessMenu['createpa'] && !$accessMenu['accesspa']) {
            Session::flash('error', "You are not eligible to create Appraisal $period.");
            return redirect()->route('appraisals');
        }

        // check appraisals
        if ($appraisal) {
            Session::flash('error', "Appraisal $period already initiated.");
            return redirect()->route('appraisals');
        }
        
        $approval = ApprovalLayerAppraisal::select('approver_id')->where('employee_id', $request->id)->where('layer_type', 'manager')->where('layer', 1)->first();
  
        if (!$approval) {
            Session::flash('error', "No Reviewer assigned, please contact admin to assign reviewer");
            return redirect()->back();
        }
        
        $calibrator = ApprovalLayerAppraisal::where('layer', 1)->where('layer_type', 'calibrator')->where('employee_id', $request->id)->value('approver_id');

        if (!$calibrator) {
            Session::flash('error', "No Layer assigned, please contact admin to assign layer");
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

        $parentLink = __('Appraisal');
        $link = 'Initiate Appraisal';

        // Pass the data to the view
        return view('pages/appraisals/create', compact('step', 'parentLink', 'link', 'filteredFormData', 'formGroupData', 'goalData', 'goal', 'approval', 'ratings'));
    }

    public function store(Request $request)
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

        // Extract formGroupName
        $formGroupName = $validatedData['formGroupName'];
        $formData = $validatedData['formData'];

        $goals = Goal::with(['employee'])->where('employee_id', $validatedData['employee_id'])->where('period', $period)->first();

        // Create the array structure
        $datas = [
            'formGroupName' => $formGroupName,
            'formData' => $formData,
        ];

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
        // Set other attributes as needed
        $approval->save();

        // Return a response, such as a redirect or a JSON response
        return redirect('appraisals')->with('success', 'Appraisal submitted successfully.');
    }

    private function getDataByName($data, $name) {
        foreach ($data as $item) {
            if ($item['name'] === $name) {
                return $item['data'];
            }
        }
        return null;
    }

    private function mergeFormData(array $formDataSets)
    {
        $mergedData = [];

        foreach ($formDataSets as $formData) {
            foreach ($formData['formData'] as $form) {

                $formName = $form['formName'];

                // Check if formName already exists in the merged data
                $existingFormIndex = collect($mergedData)->search(function ($item) use ($formName) {
                    return $item['formName'] === $formName;
                });

                if ($existingFormIndex !== false) {
                    // Merge scores for the existing form
                    foreach ($form as $key => $scores) {
                        if (is_numeric($key)) {
                            $mergedData[$existingFormIndex][$key] = array_merge($mergedData[$existingFormIndex][$key] ?? [], $scores);
                        }
                    }
                } else {
                    // Add the form to the merged data
                    $mergedData[] = $form;
                }
            }
        }

        return [
            'formGroupName' => 'Appraisal Form',
            'formData' => $mergedData
        ];

    }

    function show($id) {
        $data = Goal::find($id);
        
        return view('pages.goals.modal', compact('data')); //modal body hilang ketika modal show bentrok dengan view goal
    }
    

    function edit(Request $request) {

        $step = $request->input('step', 1);

        $period = $this->appService->appraisalPeriod();

        $appraisal = Appraisal::with(['approvalRequest'])->where('id', $request->id)->where('period', $period)->first();

        $parentLink = __('Appraisal');
        $link = __('Edit');

        if(!$appraisal){
            return redirect()->route('appraisals');
        }else{
            $goal = Goal::where('employee_id', $appraisal->employee_id)->where('period', $period)->first();

            $goalData = json_decode($goal->form_data, true);

            $approvalRequest = ApprovalRequest::where('form_id', $appraisal->id)->first();

            // Read the content of the JSON files
            $formGroupContent = $this->appService->formGroupAppraisal($appraisal->employee_id, 'Appraisal Form');
            
            if (!$formGroupContent) {
                $formGroupData = ['data' => ['formData' => []]];
            } else {
                $formGroupData = $formGroupContent;
            }

            
            $formTypes = $formGroupData['data']['form_names'] ?? [];
            $formDatas = $formGroupData['data']['form_appraisals'] ?? [];
            
            
            $filteredFormData = array_filter($formDatas, function($form) use ($formTypes) {
                return in_array($form['name'], $formTypes);
            });
            
            $ratings = $formGroupData['data']['rating'];
            
            $approval = ApprovalLayerAppraisal::select('approver_id')->where('employee_id', $appraisal->employee_id)->where('layer', 1)->first();
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

            // Function to merge scores
            function mergeScores($formData, $filteredFormData) {
                foreach ($formData['formData'] as $formData) {
                    $formName = $formData['formName'];
                    foreach ($filteredFormData as &$section) {
                        if ($section['name'] === $formName) {
                            foreach ($formData as $key => $value) {
                                if (is_numeric($key)) {
                                    if (isset($value['score'])) {
                                        foreach ($value['score'] as $scoreIndex => $scoreValue) {
                                            if (isset($section['data'][$key]['score'][$scoreIndex])) {
                                                $section['data'][$key]['score'][$scoreIndex] += $scoreValue;
                                            } else {
                                                $section['data'][$key]['score'][$scoreIndex] = $scoreValue;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                return $filteredFormData;
            }

            // Merge the scores
            $filteredFormData = mergeScores($formData, $filteredFormData);

            $viewCategory = 'Edit';

            return view('pages.appraisals.edit', compact('step', 'goal', 'appraisal', 'goalData', 'formCount', 'filteredFormData', 'link', 'data', 'approvalRequest', 'parentLink', 'approval', 'formGroupData', 'ratings', 'viewCategory'));
        }

    }

    function update(Request $request) {
        $period = $this->appService->appraisalPeriod();
        // Validate the request data
        $validatedData = $request->validate([
            'id' => 'required|uuid',
            'employee_id' => 'required|string|size:11',
            'formGroupName' => 'required|string|min:5|max:100',
            'formData' => 'required|array',
        ]);
    
        // Extract formGroupName
        $formGroupName = $validatedData['formGroupName'];
        $formData = $validatedData['formData'];
    
        // Iterate through the formData to add 'score' after 'achievement' for each KPI
        foreach ($formData as &$form) {
            if ($form['formName'] === 'KPI') {
                foreach ($form as $key => &$value) {
                    if (is_array($value) && isset($value['achievement'])) {
                        // Add the 'score' key after 'achievement'
                        $value; // Replace with the actual score value
                    }
                }
            }
            if ($form['formName'] === 'Culture') {
                foreach ($form as $key => &$value) {
                    if (is_numeric($key)) {
                        $scores = [];
                        foreach ($value as $score) {
                            // Convert each score into an array with 'score' as a key
                            $scores[] = ['score' => $score['score']];
                        }
                        $value = $scores; // Assign the array of score objects back to the form
                    }
                }
            }
            if ($form['formName'] === 'Leadership') {
                foreach ($form as $key => &$value) {
                    if (is_numeric($key)) {
                        $scores = [];
                        foreach ($value as $score) {
                            // Convert each score into an array with 'score' as a key
                            $scores[] = ['score' => $score['score']];
                        }
                        $value = $scores; // Assign the array of score objects back to the form
                    }
                }
            }
        }
    
        // Create the array structure
        $datas = [
            'formGroupName' => $formGroupName,
            'formData' => $formData,
        ];
    
        // Create a new Appraisal instance and save the data
        $appraisal = Appraisal::where('id', $validatedData['id'])->first();
        $appraisal->form_data = json_encode($datas); // Store the form data as JSON
        $appraisal->updated_by = Auth::user()->id;
        
        $appraisal->save();
        
        $snapshot = ApprovalSnapshots::where('form_id', $appraisal->id)->where('employee_id', $appraisal->employee_id)->first();
        $snapshot->form_data = json_encode($datas);
        $snapshot->updated_by = Auth::user()->id;
        
        $snapshot->save();
    
        // Return a response, such as a redirect or a JSON response
        return redirect('appraisals')->with('success', 'Appraisal updated successfully.');
    }
    

}