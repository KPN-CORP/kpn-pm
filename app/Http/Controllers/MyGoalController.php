<?php

namespace App\Http\Controllers;

use App\Exports\UserExport;
use App\Models\ApprovalLayer;
use App\Models\ApprovalRequest;
use App\Models\ApprovalSnapshots;
use App\Models\Employee;
use App\Models\Goal;
use App\Models\User;
use App\Services\AppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
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
        $goals = $access_menu['goals'] ?? null;
    
        $selectYear = ApprovalRequest::where('employee_id', $user)->where('category', $this->category)->select('created_at')->get();
        $selectYear->transform(function ($req) {
            $req->year = Carbon::parse($req->created_at)->format('Y');
            return $req;
        });
    
        return view('pages.goals.my-goal', compact('data', 'link', 'parentLink', 'uomOption', 'typeOption', 'goals', 'selectYear', 'adjustByManager'));
    }

    function show($id) {
        $data = Goal::find($id);
        
        return view('pages.goals.modal', compact('data')); //modal body hilang ketika modal show bentrok dengan view goal
    }
    
    function create($id) {

        $period = $this->appService->goalPeriod();

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

    function store(Request $request)
    {
        $user = $this->user;
        $period = $this->appService->goalPeriod();

        $layer = ApprovalLayer::select('approver_id')->where('employee_id', $request->employee_id)->where('layer', 1)->first();

        if ($request->submit_type === 'save_draft') {
            // Tangani logika penyimpanan sebagai draft
            $submit_status = 'Draft';
        } else {
            $submit_status = 'Submitted';
        }
        // Inisialisasi array untuk menyimpan pesan validasi kustom
        $customMessages = [];

        $kpis = $request->input('kpi', []);
        $descriptions = $request->input('description', []);
        $targets = $request->input('target', []);
        $uoms = $request->input('uom', []);
        $weightages = $request->input('weightage', []);
        $types = $request->input('type', []);
        $status = $submit_status;
        $custom_uoms = $request->input('custom_uom', []);
        
        // Menyiapkan aturan validasi
        $rules = [
            'kpi.*' => 'required|string',
            'target.*' => 'required|numeric',
            'uom.*' => 'required|string',
            'weightage.*' => 'required|numeric|min:5|max:100',
            'type.*' => 'required|string',
        ];

        // Pesan validasi kustom
        $customMessages = [
            'weightage.*.numeric' => 'Weightage harus berupa angka bulat (8) / desimal (8.5)',
            'weightage.*.min' => 'Weightage harus lebih besar atau sama dengan :min %.',
            'weightage.*.max' => 'Weightage harus kurang dari atau sama dengan :max %.',
        ];

        // Membuat Validator instance
        if ($request->submit_type === 'submit_form') {
            $validator = Validator::make($request->all(), $rules, $customMessages);
    
            // Jika validasi gagal
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
        }

        // Check for duplicate data
        $existingGoal = Goal::where('employee_id', $request->employee_id)
            ->where('category', $request->category)
            ->where('period', $period)
            ->first();

        if ($existingGoal) {
            Session::flash('error', "You already initiated Goals for $period.");
            return redirect('goals');
        }

        // Inisialisasi array untuk menyimpan data KPI
        
        $kpiData = [];
        // Reset nomor indeks untuk penggunaan berikutnya
        $index = 1;

        // Iterasi melalui input untuk mendapatkan data KPI
        foreach ($kpis as $index => $kpi) {
            // Memastikan ada nilai untuk semua input terkait
            if ($submit_status=='Draft' || isset($targets[$index], $uoms[$index], $weightages[$index], $types[$index])) {
                // Simpan data KPI ke dalam array dengan nomor indeks sebagai kunci
                if($custom_uoms[$index]){
                    $customuom = $custom_uoms[$index];
                }else{
                    $customuom = null;
                }

                $kpiData[$index] = [
                    'kpi' => $kpi,
                    'description' => $descriptions[$index],
                    'target' => $targets[$index],
                    'uom' => $uoms[$index],
                    'weightage' => $weightages[$index],
                    'type' => $types[$index],
                    'custom_uom' => $customuom
                ];

                $index++;
            }
        }

        // Simpan data KPI ke dalam file JSON
        $jsonData = json_encode($kpiData);

        $model =  new Goal;
        $model->id = Str::uuid();
        $model->employee_id = $request->employee_id;
        $model->category = $request->category;
        $model->form_data = $jsonData;
        $model->form_status = $status;
        $model->period = $period;
        
        $model->save();

        $snapshot =  new ApprovalSnapshots;
        $snapshot->id = Str::uuid();
        $snapshot->form_id = $model->id;
        $snapshot->form_data = $jsonData;
        $snapshot->employee_id = $request->employee_id;
        $snapshot->created_by = Auth::user()->id;
        
        $snapshot->save();

        $approval = new ApprovalRequest();
        $approval->form_id = $model->id;
        $approval->category = $this->category;
        $approval->employee_id = $request->employee_id;
        $approval->current_approval_id = $layer->approver_id;
        $approval->period = $period;
        $approval->created_by = Auth::user()->id;
        // Set other attributes as needed
        $approval->save();

        // Beri respon bahwa data berhasil disimpan
        // return response()->json(['message' => 'Data saved successfully'], 200);
        if ($user != $request->employee_id) {
            return redirect('team-goals');
        }
        return redirect('goals');
    }

    function update(Request $request) {

        if ($request->submit_type === 'save_draft') {
            // Tangani logika penyimpanan sebagai draft
            $submit_status = 'Draft';
        } else {
            $submit_status = 'Submitted';
        }
        // Inisialisasi array untuk menyimpan pesan validasi kustom
        $customMessages = [];

        $kpis = $request->input('kpi', []);
        $descriptions = $request->input('description', []);
        $targets = $request->input('target', []);
        $uoms = $request->input('uom', []);
        $weightages = $request->input('weightage', []);
        $types = $request->input('type', []);
        $status = $submit_status;
        $custom_uoms = $request->input('custom_uom', []);

        // Menyiapkan aturan validasi
        $rules = [
            'kpi.*' => 'required|string',
            'target.*' => 'required|numeric',
            'uom.*' => 'required|string',
            'weightage.*' => 'required|numeric|min:5|max:100',
            'type.*' => 'required|string',
        ];

        // Pesan validasi kustom
        $customMessages = [
            'weightage.*.numeric' => 'Weightage harus berupa angka bulat (8) / desimal (8.5)',
            'weightage.*.min' => 'Weightage harus lebih besar atau sama dengan :min %.',
            'weightage.*.max' => 'Weightage harus kurang dari atau sama dengan :max %.',
        ];

        // Membuat Validator instance
        if ($request->submit_type === 'submit_form') {
            $validator = Validator::make($request->all(), $rules, $customMessages);
    
            // Jika validasi gagal
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
        }

        $kpiData = [];
        // Reset nomor indeks untuk penggunaan berikutnya
        $index = 1;

        // Iterasi melalui input untuk mendapatkan data KPI
        foreach ($kpis as $index => $kpi) {
            // Memastikan ada nilai untuk semua input terkait
            if ($submit_status=='Draft' || isset($targets[$index], $uoms[$index], $weightages[$index], $types[$index])) {
                // Simpan data KPI ke dalam array dengan nomor indeks sebagai kunci
                if($custom_uoms[$index]){
                    $customuom = $custom_uoms[$index];
                }else{
                    $customuom = null;
                }

                $kpiData[$index] = [
                    'kpi' => $kpi,
                    'description' => $descriptions[$index],
                    'target' => $targets[$index],
                    'uom' => $uoms[$index],
                    'weightage' => $weightages[$index],
                    'type' => $types[$index],
                    'custom_uom' => $customuom
                ];

                $index++;
            }
        }

        // Simpan data KPI ke dalam file JSON
        $jsonData = json_encode($kpiData);

        $goal = Goal::find($request->id);
        $goal->form_data = $jsonData;
        $goal->form_status = $status;
        
        $goal->save();

        $approval = ApprovalRequest::where('form_id', $request->id)->first();
        $approval->status = 'Pending';
        $approval->sendback_messages = null;
        $approval->sendback_to = null;
        // Set other attributes as needed
        $approval->save();
        
        $snapshot =  ApprovalSnapshots::where('form_id', $request->id)->where('employee_id', $request->employee_id)->first();
        
        if ($snapshot) {
            $snapshot->form_data = $jsonData;
            $snapshot->updated_by = Auth::user()->id;
        } else {
            $snapshot =  new ApprovalSnapshots;
            $snapshot->id = Str::uuid();
            $snapshot->form_id = $request->id;
            $snapshot->form_data = $jsonData;
            $snapshot->employee_id = $request->employee_id;
            $snapshot->created_by = Auth::user()->id;
        }
        
        $snapshot->save();

        if ($this->user != $request->employee_id) {
            return redirect('team-goals');
        }
        return redirect('goals');       

    }

}
