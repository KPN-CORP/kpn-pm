<?php

namespace App\Http\Controllers;

use App\Exports\UserExport;
use App\Models\Approval;
use App\Models\ApprovalLayer;
use App\Models\ApprovalRequest;
use App\Models\ApprovalSnapshots;
use App\Models\Employee;
use App\Models\Goal;
use App\Models\User;
use App\Services\AppService;
use Carbon\Carbon;
use Exception;
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

class MyGoalController extends Controller
{
    protected $category;
    protected $user;
    protected $appService;

    public function __construct(AppService $appService)
    {
        $this->category = 'Goals';
        $this->appService = $appService;
        $this->user = Auth::user()->employee_id;
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

    function index(Request $request) {
        $user = $this->user;

        $period = $this->appService->goalPeriod();
    
        // Retrieve the selected year from the request
        $filterYear = $request->input('filterYear');
        
        // Retrieve approval requests
        $datasQuery = ApprovalRequest::with([
            'employee', 'goal', 'updatedBy', 'adjustedBy', 'initiated', 'manager', 
            'approval' => function ($query) {
                $query->with('approverName'); // Load nested relationship
            }
        ])
        ->whereHas('approvalLayer', function ($query) use ($user) {
            $query->where('employee_id', $user)->orWhere('approver_id', $user);
        })
        ->where('employee_id', $user)->where('category', $this->category)->orderBy('created_at', 'DESC');
    
        // Apply additional filtering based on the selected year
        if (!empty($filterYear)) {
            $datasQuery->where('period', $filterYear);
        }
        
        $datas = $datasQuery->get();
    
        $formattedData = $datas->map(function($item) {
            // Format created_at
            $createdDate = Carbon::parse($item->created_at);
            if ($createdDate->isToday()) {
                $item->formatted_created_at = 'Today ' . $createdDate->format('g:i A');
            } else {
                $item->formatted_created_at = $createdDate->format('d M Y');
            }
    
            // Format updated_at
            $updatedDate = Carbon::parse($item->updated_at);
            if ($updatedDate->isToday()) {
                $item->formatted_updated_at = 'Today ' . $updatedDate->format('g:i A');
            } else {
                $item->formatted_updated_at = $updatedDate->format('d M Y');
            }
    
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
    
        if (!empty($datas->first()->updatedBy)) {
            $adjustByManager = ApprovalLayer::where('approver_id', $datas->first()->updatedBy->employee_id)
                                            ->where('employee_id', $datas->first()->employee_id)
                                            ->first();
        } else {
            $adjustByManager = null;
        }
        
        $data = [];
        
        foreach ($formattedData as $request) {
            // Check form status and creator
            // if ($request->appraisal->goal->form_status != 'Draft' || $request->created_by == Auth::user()->id) {
                // Get fullname from approverName relation
                $dataApprover = '';
                if ($request->approval->first()) {
                    $approverName = $request->approval->first();
                    $dataApprover = $approverName->approverName->fullname;
                }
    
                // Create an object to store request data and approver fullname
                $dataItem = new stdClass();
                $dataItem->request = $request;
                $dataItem->approver_name = $dataApprover;
                $dataItem->name = $request->name;  // Add the name
                $dataItem->approvalLayer = $request->approvalLayer;  // Add the approval layer
    
                // Add the data item to the array
                $data[] = $dataItem;
            // }
        }
    
        $path = base_path('resources/goal.json');
    
        // Check if the JSON file exists
        if (!File::exists($path)) {
            abort(500, 'JSON file does not exist.');
        }
    
        // Read the contents of the JSON file
        $options = json_decode(File::get($path), true);
    
        $uomOption = $options['UoM'];
        $typeOption = $options['Type'];
    
        $parentLink = __('Goal');
        $link = __('My Goal');
    
        $employee = Employee::where('employee_id', $user)->first();
        $access_menu = json_decode($employee->access_menu, true);
        $access = $access_menu['goals'] && $access_menu['doj'] ?? null;
    
        $selectYear = ApprovalRequest::where('employee_id', $user)->where('category', $this->category)->select('created_at')->get();
        $selectYear->transform(function ($req) {
            $req->year = Carbon::parse($req->created_at)->format('Y');
            return $req;
        });
    
        return view('pages.goals.my-goal', compact('data', 'link', 'parentLink', 'uomOption', 'typeOption', 'access', 'selectYear', 'adjustByManager', 'period'));
    }

    function show($id) {
        $data = Goal::find($id);
        
        return view('pages.goals.modal', compact('data')); //modal body hilang ketika modal show bentrok dengan view goal
    }
    
    function create($id) {

        $period = $this->appService->goalPeriod();

        $employee = Employee::where('employee_id', $id)->first();
        $access_menu = json_decode($employee->access_menu, true);
        $goal_access = $access_menu['goals'] ?? null;
        $doj_access = $access_menu['doj'] ?? null;

        if (!$goal_access || !$doj_access) {
            // User ID doesn't match the condition, show error message
            if ($this->user != $id) {
                Session::flash('error', "This employee not granted access to initiate Goals for $period.");
                return redirect('team-goals');
            } else {
                Session::flash('error', "You are not granted access to initiate Goals for $period.");
            }
            return redirect('goals');
        }

        $goal = Goal::where('employee_id', $id)->where('period', $period)->get();
        if ($goal->isNotEmpty()) {
            // User ID doesn't match the condition, show error message
            Session::flash('error', "You already initiated Goals for $period.");

            if ($this->user != $id) {
                return redirect('team-goals');
            }
            return redirect('goals');
        }

        $datas = ApprovalLayer::with(['employee'])->where('employee_id', $id)->where('layer', 1)->get();  
        if (!$datas->first()) {
            Session::flash('error', "Theres no direct manager assigned in your position!");

            if ($this->user != $id) {
                return redirect('team-goals');
            }
            return redirect('goals');
        }

        $path = base_path('resources/goal.json');

        // Check if the JSON file exists
        if (!File::exists($path)) {
            // Handle the situation where the JSON file doesn't exist
            abort(500, 'JSON file does not exist.');
        }

        // Read the contents of the JSON file
        $uomOptions = json_decode(File::get($path), true);

        $uomOption = $uomOptions['UoM'];
        
        $parentLink = __('Goal');
        $link = 'Create';

        return view('pages.goals.form', compact('datas', 'link', 'parentLink', 'uomOption', 'period'));

    }

    function edit($id) {

        $goals = Goal::with(['approvalRequest'])->where('id', $id)->get();
        $goal =  $goals->first();

        $approvalRequest = ApprovalRequest::with(['employee' => function($q) {
            $q->select('id', 'fullname', 'employee_id', 'designation_name', 'job_level', 'group_company', 'unit');
        }])->where('form_id', $goal->id)->first();

        $parentLink = __('Goal');
        $link = __('Edit');

        $path = base_path('resources/goal.json');

        // Check if the JSON file exists
        if (!File::exists($path)) {
            // Handle the situation where the JSON file doesn't exist
            abort(500, 'JSON file does not exist.');
        }

        if(!$goal){
            return redirect()->route('goals');
        }else{
            // Read the contents of the JSON file
            $formData = json_decode($goal->form_data, true);

            $formCount = count($formData);

            $options = json_decode(File::get($path), true);
            $uomOption = $options['UoM'];
            $typeOption = $options['Type'];

            $selectedUoM = [];
            $selectedType = [];
            $weightage = [];
            $totalWeightages = 0;
            
            foreach ($formData as $index => $row) {
                $selectedUoM[$index] = $row['uom'] ?? '';
                $selectedType[$index] = $row['type'] ?? '';
                $weightage[$index] = $row['weightage'] ?? '';
                $totalWeightages += (float)$weightage[$index];
            }


            $data = json_decode($goal->form_data, true);
            
            return view('pages.goals.edit', compact('goal', 'formCount', 'link', 'data', 'uomOption', 'selectedUoM', 'typeOption', 'selectedType', 'approvalRequest', 'totalWeightages', 'parentLink'));
        }

    }

    public function store(Request $request)
    {
        $user = $this->user;
        $period = $this->appService->goalPeriod();
        $employee = Employee::where('employee_id', $request->employee_id)->first();
        $access_menu = json_decode($employee->access_menu, true);
        $goal_access = $access_menu['goals'] ?? null;
        $doj_access = $access_menu['doj'] ?? null;

        if (!$goal_access || !$doj_access) {
            // User ID doesn't match the condition, show error message
            if ($this->user != $request->employee_id) {
                Session::flash('error', "This employee not granted access to initiate Goals.");
                return redirect('team-goals');
            } else {
                Session::flash('error', "You are not granted access to initiate Goals.");
            }
            return redirect('goals');
        }

        // Check approval layer existence early
        $layer = ApprovalLayer::select('approver_id')
            ->where('employee_id', $request->employee_id)
            ->where('layer', 1)
            ->first();

        if (!$layer) {
            return redirect('goals')->withErrors(['error' => 'Approval layer configuration missing for this employee']);
        }

        // Handle draft vs submitted logic
        $submit_status = $request->submit_type === 'save_draft' ? 'Draft' : 'Submitted';

        // Validation for submitted forms
        if ($submit_status === 'Submitted') {
            $rules = [
                'kpi.*' => 'required|string',
                'target.*' => 'required|string',
                'uom.*' => 'required|string',
                'weightage.*' => 'required|numeric|min:5|max:100',
                'type.*' => 'required|string',
            ];

            $validator = Validator::make($request->all(), $rules, [
                'weightage.*.numeric' => 'Weightage must be a whole (8) or decimal (8.5) number',
                'weightage.*.min' => 'Weightage must be at least :min%',
                'weightage.*.max' => 'Weightage cannot exceed :max%',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
        }

        // Check for existing goals
        $existingGoal = Goal::where('employee_id', $request->employee_id)
            ->where('category', $request->category)
            ->where('period', $period)
            ->exists();

        if ($existingGoal) {
            return redirect('goals')->withErrors(['error' => "You already have goals initiated for {$period}"]);
        }

        // Start transaction
        DB::beginTransaction();
        try {

            $nextLayer = ApprovalLayer::where('approver_id', $layer->approver_id)
                                    ->where('employee_id', $request->employee_id)->max('layer');

            // Cari approver_id pada layer selanjutnya
            $nextApprover = ApprovalLayer::where('layer', $nextLayer + 1)->where('employee_id', $request->employee_id)->value('approver_id');

            if($request->employee_id == $user) {
                $approver = $layer->approver_id;
                $statusRequest = 'Pending';
                $statusForm = $submit_status;
            } else if (!$nextApprover) {
                $approver = $layer->approver_id;
                $statusRequest = 'Approved';
                $statusForm = 'Approved';
            }else{
                $approver = $nextApprover;
                $statusRequest = 'Pending';
                $statusForm = $submit_status;
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
                ];
            }

            // Save main goal record
            $goal = new Goal();
            $goal->id = Str::uuid();
            $goal->employee_id = $request->employee_id;
            $goal->category = $request->category;
            $goal->form_data = json_encode($kpiData);
            $goal->form_status = $statusForm;
            $goal->period = $period;

            if (!$goal->save()) {
                throw new Exception("Failed to save goal data");
            }

            // Save approval snapshot
            $snapshot = new ApprovalSnapshots();
            $snapshot->id = Str::uuid();
            $snapshot->form_id = $goal->id;
            $snapshot->form_data = $goal->form_data;
            $snapshot->employee_id = $request->employee_id;
            $snapshot->created_by = Auth::id();

            if (!$snapshot->save()) {
                throw new Exception("Failed to save approval snapshot");
            }

            // Create approval request
            $approvalRequest = new ApprovalRequest();
            $approvalRequest->form_id = $goal->id;
            $approvalRequest->category = $this->category;
            $approvalRequest->employee_id = $request->employee_id;
            $approvalRequest->current_approval_id = $approver; /// Approver pertama
            $approvalRequest->period = $period;
            $approvalRequest->status = $statusRequest;
            $approvalRequest->created_by = Auth::id();

            if (!$approvalRequest->save()) {
                throw new Exception("Failed to create approval request");
            }

            // Create initial approval record
            $approval = new Approval();
            $approval->request_id = $approvalRequest->id;
            $approval->approver_id = Auth::user()->employee_id;
            $approval->created_by = Auth::id();
            $approval->status = 'Approved';

            if (!$approval->save()) {
                throw new Exception("Failed to record approval");
            }

            DB::commit();

            return $user != $request->employee_id 
                ? redirect('team-goals')->with('success', 'Goal saved successfully')
                : redirect('goals')->with('success', 'Goal saved successfully');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Goal store failed: ' . $e->getMessage(), [
                'user' => Auth::id(),
                'employee_id' => $request->employee_id,
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to save goal: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request)
    {
        $user = $this->user;
        $period = $this->appService->goalPeriod();

        // Validate submission type
        $submit_status = $request->submit_type === 'save_draft' ? 'Draft' : 'Submitted';

        // Validation for submitted forms
        if ($submit_status === 'Submitted') {
            $rules = [
                'kpi.*' => 'required|string',
                'target.*' => 'required|numeric',
                'uom.*' => 'required|string',
                'weightage.*' => 'required|numeric|min:5|max:100',
                'type.*' => 'required|string',
            ];

            $validator = Validator::make($request->all(), $rules, [
                'weightage.*.numeric' => 'Weightage must be a whole (8) or decimal (8.5) number',
                'weightage.*.min' => 'Weightage must be at least :min%',
                'weightage.*.max' => 'Weightage cannot exceed :max%',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
        }

        $approver = null;	
        $statusRequest = 'Pending';

        if($request->employee_id != $user){
            $nextLayer = ApprovalLayer::where('approver_id', $user)
                                        ->where('employee_id', $request->employee_id)->max('layer');
    
            // Cari approver_id pada layer selanjutnya
            $nextApprover = ApprovalLayer::where('layer', $nextLayer + 1)->where('employee_id', $request->employee_id)->value('approver_id');
    
            if (!$nextApprover) {
                $approver = $user;
                $statusRequest = 'Approved';
                $submit_status = 'Approved';
            }else{
                $approver = $nextApprover;
                $statusRequest = $statusRequest;
                $submit_status = 'Submitted';
            }
        }

        // Start transaction
        DB::beginTransaction();
        try {
            // Get existing goal
            $goal = Goal::findOrFail($request->id);
            
            // Check ownership
            if ($goal->employee_id != $request->employee_id) {
                throw new Exception("You don't have permission to update this goal");
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
                ];
            }

            // Update goal record
            $goal->form_data = json_encode($kpiData);
            $goal->form_status = $submit_status;
            
            if (!$goal->save()) {
                throw new Exception("Failed to update goal data");
            }

            // Update approval request
            $approvalRequest = ApprovalRequest::where('form_id', $goal->id)->firstOrFail();
            $approvalRequest->status = $statusRequest;
            if($approver){
                $approvalRequest->current_approval_id = $approver;
            }
            $approvalRequest->sendback_messages = null;
            $approvalRequest->sendback_to = null;
            
            if (!$approvalRequest->save()) {
                throw new Exception("Failed to update approval request");
            }

            // Update/create approval snapshot
            $snapshot = ApprovalSnapshots::firstOrNew([
                'form_id' => $goal->id
            ]);
            
            $snapshot->id = $snapshot->id ?? Str::uuid();
            $snapshot->form_data = $goal->form_data;
            $snapshot->employee_id = $request->employee_id;
            $snapshot->updated_by = Auth::id();
            $snapshot->created_by = $snapshot->created_by ?? Auth::id();
            
            if (!$snapshot->save()) {
                throw new Exception("Failed to update approval snapshot");
            }

            DB::commit();

            return $user != $request->employee_id 
                ? redirect('team-goals')->with('success', 'Goal updated successfully')
                : redirect('goals')->with('success', 'Goal updated successfully');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Goal update failed: ' . $e->getMessage(), [
                'user' => Auth::id(),
                'goal_id' => $request->id ?? 'N/A',
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Update failed: ' . $e->getMessage()]);
        }
    }

}
