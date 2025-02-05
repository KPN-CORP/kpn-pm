<?php

namespace App\Http\Controllers;

use App\Models\ApprovalLayer;
use App\Models\ApprovalRequest;
use App\Models\ApprovalSnapshots;
use App\Models\Employee;
use App\Models\Goal;
use App\Models\Schedule;
use App\Models\User;
use App\Services\AppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use RealRashid\SweetAlert\Facades\Alert;
use stdClass;

class TeamGoalController extends Controller
{
    protected $category;
    protected $user;
    protected $appService;
    protected $period;

    public function __construct(AppService $appService)
    {
        $this->category = 'Goals';
        $this->appService = $appService;
        $this->user = Auth::user()->employee_id;
        $this->period = $this->appService->goalPeriod();
    }

    function index(Request $request) {
        
        $user = Auth::user()->employee_id;

        // Retrieve the selected year from the request
        $filterYear = $request->input('filterYear');
        
        $datas = ApprovalLayer::with(['employee','subordinates' => function ($query) use ($user){
            $query->with(['goal', 'updatedBy', 'approval' => function ($query) {
                $query->with('approverName');
            }])->whereHas('goal', function ($query) {
                $query->whereNull('deleted_at');
            })->whereHas('approvalLayer', function ($query) use ($user) {
                $query->where('employee_id', $user)->orWhere('approver_id', $user);
            })->where('category', $this->category);
        }])->where('approver_id', $user)->get();
        
        $tasks = ApprovalLayer::with(['employee', 'subordinates' => function ($query) use ($user, $filterYear) {
            $query->with(['goal', 'updatedBy', 'approval' => function ($query) {
                $query->with('approverName');
            }])->whereHas('goal', function ($query) {
                $query->whereNull('deleted_at');
            })->whereHas('approvalLayer', function ($query) use ($user) {
                $query->where('employee_id', $user)->orWhere('approver_id', $user);
            })->when($filterYear, function ($query) use ($filterYear) {
                $query->where('period', $filterYear);
            }, function ($query) {
                $query->where('period', $this->period);
            });
        }])
        ->whereHas('subordinates', function ($query) use ($user, $filterYear) {
            $query->with(['goal', 'updatedBy', 'approval' => function ($query) {
                $query->with('approverName');
            }])->whereHas('goal', function ($query) {
                $query->whereNull('deleted_at');
            })->whereHas('approvalLayer', function ($query) use ($user) {
                $query->where('employee_id', $user)->orWhere('approver_id', $user);
            })->when($filterYear, function ($query) use ($filterYear) {
                $query->where('period', $filterYear);
            }, function ($query) {
                $query->where('period', $this->period);
            });
        })
        ->where('approver_id', $user)
        ->get();    
        
        $tasks->each(function($item) {
            $item->subordinates->map(function($subordinate) {
                // Format created_at
                $createdDate = Carbon::parse($subordinate->created_at);
                if ($createdDate->isToday()) {
                    $subordinate->formatted_created_at = 'Today ' . $createdDate->format('g:i A');
                } else {
                    $subordinate->formatted_created_at = $createdDate->format('d M Y');
                }
    
                // Format updated_at
                $updatedDate = Carbon::parse($subordinate->updated_at);
                if ($updatedDate->isToday()) {
                    $subordinate->formatted_updated_at = 'Today ' . $updatedDate->format('g:i A');
                } else {
                    $subordinate->formatted_updated_at = $updatedDate->format('d M Y');
                }
                
                // Determine name and approval layer
                if ($subordinate->sendback_to == $subordinate->employee->employee_id) {
                    $subordinate->name = $subordinate->employee->fullname . ' (' . $subordinate->employee->employee_id . ')';
                    $subordinate->approvalLayer = '';
                } else {
                    $subordinate->name = $subordinate->manager->fullname . ' (' . $subordinate->manager->employee_id . ')';
                    $subordinate->approvalLayer = ApprovalLayer::where('employee_id', $subordinate->employee_id)
                                                            ->where('approver_id', $subordinate->current_approval_id)
                                                            ->value('layer');
                }

                return $subordinate;
            });
        });

        $notasks = ApprovalLayer::with([
            'employee'
        ])
        ->where('approver_id', $user)
        ->whereHas('employee', fn($q) => $q->where('access_menu->doj', 1))
        ->whereDoesntHave('subordinates', function ($q) use ($user, $filterYear) {
            $q->where('period', $filterYear ?? $this->period)
              ->where('category', $this->category);
        })
        ->get();    

        $notasks->map(function($item) {
            // Format created_at
            $doj = Carbon::parse($item->employee->date_of_joining);

                $item->formatted_doj = $doj->format('d M Y');
                
            return $item;
        });
        
        $data = [];
        $formData = [];

        foreach ($datas as $request) {
            // Check if subordinates is not empty and has elements
            if ($request->subordinates->isNotEmpty()) {
                $firstSubordinate = $request->subordinates->first();
        
                // Check form status and created_by conditions
                if ($firstSubordinate->created_by != Auth::user()->id) {
                    
                    // Check if approval relation exists and has elements
                    if ($firstSubordinate->approval->isNotEmpty()) {
                        $approverName = $firstSubordinate->approval->first();
                        $dataApprover = $approverName->approverName->fullname;
                    } else {
                        $dataApprover = '';
                    }
        
                    // Create object to store request and approver fullname
                    $dataItem = new stdClass();
                    $dataItem->request = $request;
                    $dataItem->approver_name = $dataApprover;
        
                    // Add object to array $data
                    $data[] = $dataItem;

                    $formData = json_decode($firstSubordinate->goal->form_data, true);
                }
            } else {
                // Handle case when subordinates is empty
                // Create object with empty or default values
                $dataItem = new stdClass();
                $dataItem->request = $request;
                $dataItem->approver_name = ''; // or some default value
        
                // Add object to array $data
                $data[] = $dataItem;

                $formData = '';
            }
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

        $parentLink = __('Goal');
        $link = __('Task Box');

        $period = $this->period;
        $selectYear = Schedule::withTrashed()
        ->where('event_type', 'goals')
        ->where('schedule_periode', '!=', $period)
        ->selectRaw('DISTINCT schedule_periode as period')
        ->orderBy('period', 'ASC')
        ->get();

        return view('pages.goals.team-goal', compact('data', 'tasks', 'notasks', 'link', 'parentLink', 'formData', 'uomOption', 'typeOption', 'selectYear', 'period'));
       
    }
    
    function create($id) {

        $goal = Goal::where('employee_id', $id)->get(); 
        
        if ($goal->isNotEmpty()) {
            // User ID doesn't match the condition, show error message
            Alert::error('You already initiated Goals.')->autoClose(2000);
            return redirect()->back(); // Redirect back with error message
        }

        $layer = ApprovalLayer::where('employee_id', $id)->where('layer', 1)->get();  
        if (!$layer->first()) {
            Alert::error("Cannot create goals", "Theres no direct manager assigned in your position!")->showConfirmButton('OK');
            return redirect()->back();
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
        
        $Link = __('Goal');

        return view('pages.goals.form', compact('layer', 'link', 'uomOption'));

    }

    function edit($id) {

        $goals = Goal::with(['approvalRequest'])->where('id', $id)->get();
        $goal =  $goals->first();

        $Link = __('Goal');

        $path = base_path('resources/goal.json');

        // Check if the JSON file exists
        if (!File::exists($path)) {
            // Handle the situation where the JSON file doesn't exist
            abort(500, 'JSON file does not exist.');
        }

        if(!$goal){
            return redirect()->route('goals');
            // $goal = Goal::where('id', $data->goal->id)->get();  
        }else{
            // Read the contents of the JSON file
            $formData = json_decode($goal->form_data, true);

            $formCount = count($formData);

            $options = json_decode(File::get($path), true);
            $uomOption = $options['UoM'];
            $typeOption = $options['Type'];

            $selectedUoM = [];
            $selectedType = [];
            
            foreach ($formData as $index => $row) {
                $selectedUoM[$index] = $row['uom'] ?? '';
                $selectedType[$index] = $row['type'] ?? '';
            }

            $data = json_decode($goal->form_data, true);

            return view('pages.goals.edit', compact('goal', 'formCount', 'link', 'data', 'uomOption', 'selectedUoM', 'typeOption', 'selectedType'));
        }

    }

    function approval($id) {

        // Mengambil data pengajuan berdasarkan employee_id atau manager_id
        $datas = ApprovalRequest::with(['employee', 'goal', 'manager', 'approval' => function ($query) {
            $query->with('approverName'); // Load nested relationship
        }])->where('form_id', $id)->get();

        $data = [];
        
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

                $dataItem->request = $request;
                $dataItem->approver_name = $dataApprover;
              

                // Tambahkan objek $dataItem ke dalam array $data
                $data[] = $dataItem;
                
            }
        }
        
        $formData = [];
        if($datas->isNotEmpty()){
            $formData = json_decode($datas->first()->goal->form_data, true);
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

        $parentLink = __('Goal');
        $link = 'Approval';

        return view('pages.goals.approval', compact('data', 'link', 'parentLink', 'formData', 'uomOption', 'typeOption'));

    }

    function store(Request $request)
    {
        if ($request->submit_type === 'save_draft') {
            // Tangani logika penyimpanan sebagai draft
            $submit_status = 'Draft';
        } else {
            $submit_status = 'Submitted';
        }
        // Inisialisasi array untuk menyimpan pesan validasi kustom
        $customMessages = [];

        $kpis = $request->input('kpi', []);
        $targets = $request->input('target', []);
        $uoms = $request->input('uom', []);
        $weightages = $request->input('weightage', []);
        $types = $request->input('type', []);
        $status = $submit_status;
        $custom_uoms = $request->input('custom_uom', []);
        
        // Menyiapkan aturan validasi
        $rules = [
            'kpi.*' => 'required|string',
            'target.*' => 'required|string',
            'uom.*' => 'required|string',
            'weightage.*' => 'required|integer|min:5|max:100',
            'type.*' => 'required|string',
        ];

        // Pesan validasi kustom
        $customMessages = [
            'weightage.*.integer' => 'Weightage harus berupa angka.',
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
        $approval->current_approval_id = $request->approver_id;
        $approval->created_by = Auth::user()->id;
        // Set other attributes as needed
        $approval->save();

        // Beri respon bahwa data berhasil disimpan
        // return response()->json(['message' => 'Data saved successfully'], 200);
            return redirect('team-goals');
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
        $targets = $request->input('target', []);
        $uoms = $request->input('uom', []);
        $weightages = $request->input('weightage', []);
        $types = $request->input('type', []);
        $status = $submit_status;
        $custom_uoms = $request->input('custom_uom', []);

        // Menyiapkan aturan validasi
        $rules = [
            'kpi.*' => 'required|string',
            'target.*' => 'required|string',
            'uom.*' => 'required|string',
            'weightage.*' => 'required|integer|min:5|max:100',
            'type.*' => 'required|string',
        ];

        // Pesan validasi kustom
        $customMessages = [
            'weightage.*.integer' => 'Weightage harus berupa angka.',
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

        $snapshot =  ApprovalSnapshots::where('form_id', $request->id)->where('employee_id', $request->employee_id)->first();
        $snapshot->form_data = $jsonData;
        $snapshot->updated_by = Auth::user()->id;
        
        $snapshot->save();

            return redirect('team-goals');

    }

    public function getTooltipContent(Request $request)
    {
        $approvalRequest = ApprovalRequest::with(['manager', 'employee'])->where('employee_id', $request->id)->where('category', $this->category)->first();

        if($approvalRequest){
            if ($approvalRequest->sendback_to == $approvalRequest->employee->employee_id) {
                $name = $approvalRequest->employee->fullname.' ('.$approvalRequest->employee->employee_id.')';
                $approvalLayer = '';
            }else{
                $name = $approvalRequest->manager->fullname.' ('.$approvalRequest->manager->employee_id.')';
                $approvalLayer = ApprovalLayer::where('employee_id', $approvalRequest->employee_id)->where('approver_id', $approvalRequest->current_approval_id)->value('layer');
            }
        }
        return response()->json(['name' => $name, 'layer' => $approvalLayer]);

    }

    public function unitOfMeasurement()
    {
        $uom = file_get_contents(base_path('resources/goal.json'));
        return response()->json(json_decode($uom, true));
    }

}
