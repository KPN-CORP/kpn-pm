<?php

namespace App\Http\Controllers\Admin;

use App\Exports\UserExport;
use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\ApprovalLayer;
use App\Models\ApprovalRequest;
use App\Models\ApprovalSnapshots;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Goal;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
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
    
    public function __construct()
    {
        $this->category = 'Goals';
        $this->roles = Auth()->user()->roles;
        
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

        $filterCategory = $request->input('filter_category');

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
        
        if ($category || $filterCategory) {
            // Mengambil data pengajuan berdasarkan employee_id atau manager_id
            $datas = ApprovalRequest::with(['employee', 'goal', 'updatedBy', 'approval' => function ($query) {
                $query->with('approverName'); // Load nested relationship
            }])->where('category', $this->category);

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
        }

        $locations = $this->locations;
        $companies = $this->companies;
        $groupCompanies = $this->groupCompanies;

        if ($category == 'Goals' || $filterCategory == 'Goals') {
            return view('pages.onbehalfs.goal', compact('data', 'link', 'parentLink', 'locations', 'companies', 'groupCompanies'));
        } elseif ($category == 'Performance' || $filterCategory == 'Performance') {
            return view('pages.onbehalfs.performance', compact('data', 'link', 'parentLink', 'locations', 'companies', 'groupCompanies'));
        } else {
            return view('pages.onbehalfs.empty');
        }
    }

    function create($id) {

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

        $path = storage_path('../resources/goal.json');

        // Check if the JSON file exists
        if (!File::exists($path)) {
            // Handle the situation where the JSON file doesn't exist
            abort(500, 'JSON file does not exist.');
        }

        // Read the contents of the JSON file
        $options = json_decode(File::get($path), true);

        $uomOption = $options['UoM'];
        $typeOption = $options['Type'];

        $parentLink = 'On Behalf';
        $link = 'Approval';

        return view('pages.onbehalfs.approval', compact('data', 'link', 'parentLink', 'formData', 'uomOption', 'typeOption'));

    }
    
    public function store(Request $request): RedirectResponse

    {
        // Inisialisasi array untuk menyimpan pesan validasi kustom

        $nextLayer = ApprovalLayer::where('approver_id', $request->current_approver_id)
                                    ->where('employee_id', $request->employee_id)->max('layer');

        // Cari approver_id pada layer selanjutnya
        $nextApprover = ApprovalLayer::where('layer', $nextLayer + 1)->where('employee_id', $request->employee_id)->value('approver_id');

        if (!$nextApprover) {
            $approver = $request->current_approver_id;
            $statusRequest = 'Approved';
            $statusForm = 'Approved';
        }else{
            $approver = $nextApprover;
            $statusRequest = 'Pending';
            $statusForm = 'Submitted';
        }

        $status = 'Approved';

        $customMessages = [];

        $kpis = $request->input('kpi', []);
        $targets = $request->input('target', []);
        $uoms = $request->input('uom', []);
        $weightages = $request->input('weightage', []);
        $types = $request->input('type', []);
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
            if (isset($targets[$index], $uoms[$index], $weightages[$index], $types[$index])) {
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

        $checkApprovalSnapshots = ApprovalSnapshots::where('form_id', $request->id)->where('employee_id', $request->current_approver_id)->first();

        if (!empty($checkApprovalSnapshots)) {
            $snapshot = ApprovalSnapshots::find($checkApprovalSnapshots->id);
            $snapshot->form_data = $jsonData;
            $snapshot->updated_by = Auth::user()->id;
        } else {
            $snapshot = new ApprovalSnapshots;
            $snapshot->id = Str::uuid();
            $snapshot->form_data = $jsonData;
            $snapshot->form_id = $request->id;
            $snapshot->employee_id = $request->current_approver_id;
            $snapshot->created_by = Auth::user()->id;

        }
        $snapshot->save();

        $model = Goal::find($request->id);
        $model->form_data = $jsonData;
        $model->form_status = $statusForm;
        
        $model->save();

        $approvalRequest = ApprovalRequest::where('form_id', $request->id)->first();
        $approvalRequest->current_approval_id = $approver;
        $approvalRequest->status = $statusRequest;
        $approvalRequest->updated_by = Auth::user()->id;
        $approvalRequest->messages = $request->messages;
        $approvalRequest->sendback_messages = "";
        // Set other attributes as needed
        $approvalRequest->save();

        $checkApproval = Approval::where('request_id', $approvalRequest->id)->where('approver_id', $request->current_approver_id)->first();

        if ($checkApproval) {
            $approval = $checkApproval;
            $approval->messages = $request->messages;

        } else {
            $approval = new Approval;
            $approval->request_id = $approvalRequest->id;
            $approval->approver_id = $request->current_approver_id;
            $approval->created_by = Auth::user()->id;
            $approval->status = $status;
            $approval->messages = $request->messages;
            // Set other attributes as needed
        }
        $approval->save();
            
        return redirect()->route('onbehalf');
    }

    public function unitOfMeasurement()
    {
        $uom = file_get_contents(storage_path('../resources/goal.json'));

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

        $path = storage_path('../resources/goal.json');

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

}
