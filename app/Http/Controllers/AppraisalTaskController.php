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
use App\Models\Goal;
use App\Services\AppService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
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

            $user = $this->user;
            $filterYear = $request->input('filterYear');
            
            $dataTeams = ApprovalLayerAppraisal::with(['approver', 'contributors' => function($query) use ($user) {
                $query->where('contributor_id', $user);
            }, 'approvalRequest' => function($query) {
                $query->where('category', 'Appraisal');
            }])
            ->where('approver_id', $user)
            ->where('layer_type', 'manager')
            ->get();


            $dataTeams->map(function($team) {
                if ($team->approvalRequest->first()) {
                    $team->approvalRequest->first()->formatted_created_at = $this->appService->formatDate($team->approvalRequest->first()->created_at);
                    $team->approvalRequest->first()->formatted_updated_at = $this->appService->formatDate($team->approvalRequest->first()->updated_at);
                }
                return $team;
            });

            $filteredDataTeams = $dataTeams->filter(function($item) {
                // Memeriksa apakah contributors kosong
                return $item->contributors->isEmpty();
            });

            $notifDataTeams = $filteredDataTeams->count();

            $data360 = ApprovalLayerAppraisal::with(['approver', 'contributors' => function($query) {
                $query->where('contributor_id', Auth::user()->employee_id);
            }, 'approvalRequest', 'appraisal'])
            ->where('approver_id', $user)
            ->where('layer_type', '!=', 'manager')
            ->where('layer_type', '!=', 'calibrator')
            ->get()
            ->filter(function($item) {
                // Hanya return item yang memiliki data appraisal
                return $item->appraisal !== null;
            });

            $data360->map(function($team360) {
                if ($team360->approvalRequest->first()) {
                    $team360->approvalRequest->first()->formatted_created_at = $this->appService->formatDate($team360->approvalRequest->first()->created_at);
                    $team360->approvalRequest->first()->formatted_updated_at = $this->appService->formatDate($team360->approvalRequest->first()->updated_at);
                }
                return $team360;
            });

            $filteredData360 = $data360->filter(function($item) {
                // Memeriksa apakah contributors kosong
                return $item->contributors->isEmpty();
            });

            $notifData360 = $filteredData360->count();
            
            $contributors = $data360->pluck('contributors');

            $parentLink = __('Appraisal');
            $link = __('Task Box');

            return view('pages.appraisals-task.app', compact('dataTeams', 'notifDataTeams', 'data360', 'notifData360', 'contributors', 'link', 'parentLink'));

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

    public function initiate(Request $request)
    {
        $user = $this->user;

        $step = $request->input('step', 1);

        $year = Carbon::now()->year;

        $approval = ApprovalLayerAppraisal::select('approver_id')->where('employee_id', $request->id)->where('layer', 1)->first();

        $goal = Goal::with(['employee'])->where('employee_id', $request->id)->whereYear('created_at', $year)->first();

        if ($goal) {
            $goalData = json_decode($goal->form_data, true);
        } else {
            Session::flash('error', "Goals for not found.");
            return redirect()->back();
        }

        // Read the content of the JSON files
        $formGroupContent = storage_path('../resources/testFormGroupTask.json');

        // Decode the JSON content
        $formGroupData = json_decode(File::get($formGroupContent), true);
        
        
        $formTypes = $formGroupData['data']['formName'] ?? [];
        $formDatas = $formGroupData['data']['formData'] ?? [];
        
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
        return view('pages.appraisals-task.initiate', compact('step', 'parentLink', 'link', 'filteredFormDatas', 'formGroupData', 'goal', 'approval', 'goalData', 'user'));
    }

    public function approval(Request $request)
    {
        $user = $this->user;

        $step = $request->input('step', 1);

        $year = Carbon::now()->year;

        $approval = ApprovalLayerAppraisal::select('approver_id')->where('employee_id', $request->id)->where('layer', 1)->first();

        $goal = Goal::with(['employee'])->where('employee_id', $request->id)->whereYear('created_at', $year)->first();

        if ($goal) {
            $goalData = json_decode($goal->form_data, true);
        } else {
            Session::flash('error', "Goals for not found.");
            return redirect()->back();
        }

        // Read the content of the JSON files
        $formGroupContent = storage_path('../resources/testFormGroupTask.json');

        // Decode the JSON content
        $formGroupData = json_decode(File::get($formGroupContent), true);
        
        
        $formTypes = $formGroupData['data']['formName'] ?? [];
        $formDatas = $formGroupData['data']['formData'] ?? [];

        
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

        $step = $request->input('step', 1);

        $year = Carbon::now()->year;

        
        $goals = Goal::with(['employee'])->where('employee_id', $request->id)->whereYear('created_at', $year)->first();
        
        $appraisal = Appraisal::with(['approvalRequest' => function($query) {
            $query->where('category', 'Appraisal');
        }])->where('employee_id', $request->id)->whereYear('created_at', $year)->first();

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

        $form360 = 'testFormGroup360.json';
        $formManager = 'testFormGroupReview.json';

        $formSource = $approval->layer_type == 'manager' ? $formManager : $form360 ;

        // Read the content of the JSON files
        $formGroupContent = storage_path('../resources/'. $formSource);

        // Decode the JSON content
        $formGroupData = json_decode(File::get($formGroupContent), true);
            
            
        $formTypes = $formGroupData['data']['formName'] ?? [];
        $formDatas = $formGroupData['data']['formData'] ?? [];
        
        
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
            'viewCategory' => 'initiate',
            'filteredFormData' => $filteredFormData,
        ];
        
        $parentLink = __('Appraisal');
        $link = 'Initiate Appraisal';

        // Pass the data to the view
        return view('pages.appraisals-task.review', compact('step', 'parentLink', 'link', 'filteredFormDatas', 'formGroupData', 'goal', 'goals', 'approval', 'goalData', 'user', 'achievement', 'appraisalId'));
    }

    public function detail(Request $request)
    {
        $user = $this->user;

        $step = $request->input('step', 1);

        $year = Carbon::now()->year;

        $contributors = AppraisalContributor::where('id', $request->id)->first();

        $goals = Goal::with(['employee'])->where('employee_id', $contributors->employee_id)->whereYear('created_at', $year)->first();
        
        $approval = ApprovalLayerAppraisal::where('employee_id', $contributors->employee_id)->where('approver_id', Auth::user()->employee_id )->first();

        $appraisalId = $contributors->appraisal_id;
        
        $data = json_decode($contributors->form_data, true);
        $achievement = array_filter($data['formData'], function ($form) {
            return $form['formName'] === 'KPI';
        });

        if ($goals) {
            $goalData = json_decode($goals->form_data, true);
        } else {
            Session::flash('error', "Goals for not found.");
            return redirect()->back();
        }

        if (!empty($achievement) && isset($achievement[0])) {
            foreach ($achievement[0] as $key => $formItem) {
                if (isset($goalData[$key])) {
                    $combinedData[$key] = array_merge($formItem, $goalData[$key]);
                } else {
                    $combinedData[$key] = $formItem;
                }
            }
        } else {
            // Handle the case where $achievement is empty
            // For example, set $combinedData to an empty array or handle it as per your needs
            $combinedData = [];
        }

        // Dump and die to inspect the result
        // dd($combinedData);

        $form360 = 'testFormGroup360.json';
        $formManager = 'testFormGroupReview.json';

        $formSource = $approval->layer_type == 'manager' ? $formManager : $form360 ;

        // Read the content of the JSON files
        $formGroupContent = storage_path('../resources/'. $formSource);

        // Decode the JSON content
        $formGroupData = json_decode(File::get($formGroupContent), true);

            
            
        $formTypes = $formGroupData['data']['formName'] ?? [];
        $formDatas = $formGroupData['data']['formData'] ?? [];
        
        
        $filteredFormData = array_filter($formDatas, function($form) use ($formTypes) {
            return in_array($form['name'], $formTypes);
        });        
        
        // Read the contents of the JSON file
        $formData = json_decode($contributors->form_data, true);
        
        $formCount = count($formData);
        
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
            'viewCategory' => 'detail',
            'filteredFormData' => $filteredFormData,
        ];

        
        $parentLink = __('Appraisal');
        $link = 'Initiate Appraisal';

        // Pass the data to the view
        return view('pages.appraisals-task.detail', compact('step', 'parentLink', 'link', 'filteredFormDatas', 'formGroupData', 'goal', 'goals', 'approval', 'goalData', 'user', 'achievement', 'appraisalId'));
    }

    public function storeInitiate(Request $request)
    {
        $submit_status = 'Submitted';
        $period = 2024;

        // Validate the request data
        $validatedData = $request->validate([
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

        $goals = Goal::with(['employee'])->where('employee_id', $validatedData['employee_id'])->whereYear('created_at', $period)->first();

        $goalData = json_decode($goals->form_data, true);

        // Create a new Appraisal instance and save the data
        $appraisal = new Appraisal;
        $appraisal->id = Str::uuid();
        $appraisal->employee_id = $validatedData['employee_id'];
        $appraisal->category = $this->category;
        $appraisal->form_data = json_encode($datas); // Store the form data as JSON
        $appraisal->form_status = $submit_status;
        $appraisal->period = $period;
        $appraisal->created_by = Auth::user()->id;
        
        $appraisal->save();

        $contributorData = ApprovalLayerAppraisal::select('approver_id', 'layer_type')->where('approver_id', Auth::user()->employee_id)->where('employee_id', $validatedData['employee_id'])->first();

        $firstCalibrator = ApprovalLayerAppraisal::where('layer', 1)->where('layer_type', 'calibrator')->where('employee_id', $validatedData['employee_id'])->value('approver_id');


        $formDatas = $this->appService->combineFormData($datas, $goalData, $contributorData->layer_type, $goals->employee);

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
        $approval->employee_id = $validatedData['employee_id'];
        $approval->current_approval_id = $validatedData['approver_id'];
        $approval->created_by = Auth::user()->id;
        // Set other attributes as needed
        $approval->save();

        $calibration = new Calibration();
        $calibration->appraisal_id = $validatedData['appraisal_id'];
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
        $period = 2024;

        // Validate the request data
        $validatedData = $request->validate([
            'employee_id' => 'required|string|size:11',
            'approver_id' => 'required|string|size:11',
            'appraisal_id' => 'required|string',
            'formGroupName' => 'required|string|min:5|max:100',
            'formData' => 'required|array',
        ]);

        $contributorData = ApprovalLayerAppraisal::select('approver_id', 'layer_type')->where('approver_id', Auth::user()->employee_id)->where('employee_id', $validatedData['employee_id'])->first();

        $goals = Goal::with(['employee'])->where('employee_id', $validatedData['employee_id'])->whereYear('created_at', $period)->first();

        $goalData = json_decode($goals->form_data, true);
        
        // Extract formGroupName
        $formGroupName = $validatedData['formGroupName'];
        $formData = $validatedData['formData'];

        // Create the array structure
        $datas = [
            'formGroupName' => $formGroupName,
            'formData' => $formData,
        ];
        
        $formDatas = $this->appService->combineFormData($datas, $goalData, $contributorData->layer_type, $goals->employee);

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

            if (!$nextApprover) {
                $approver = $validatedData['approver_id'];
                $statusRequest = 'Approved';
                $statusForm = 'Approved';
            }else{
                $approver = $nextApprover;
                $statusRequest = 'Pending';
                $statusForm = 'Submitted';
            }

            $model = Appraisal::find($validatedData['appraisal_id']);
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

            $calibration = new Calibration();
            $calibration->appraisal_id = $validatedData['appraisal_id'];
            $calibration->employee_id = $validatedData['employee_id'];
            $calibration->approver_id = $firstCalibrator;
            $calibration->period = $period;
            $calibration->created_by = Auth::user()->id;

            $calibration->save();

        }

        // Return a response, such as a redirect or a JSON response
        return redirect('appraisals-task')->with('success', 'Appraisal submitted successfully.');
    }

}
