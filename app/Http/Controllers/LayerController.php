<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApprovalLayer; 
use App\Models\ApprovalRequest;
use App\Models\Approval;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ApprovalLayerImport;
use Illuminate\Support\Facades\Log;
use App\Models\ApprovalLayerBackup;
use App\Models\Goal;
use Illuminate\Support\Facades\Auth;

class LayerController extends Controller
{
    protected $category;

    public function __construct()
    {
        $this->category = 'Goals';
    }

    function layer() {

        $roles = Auth()->user()->roles;

        $restrictionData = [];
        if(!is_null($roles)){
            $restrictionData = json_decode($roles->first()->restriction, true);
        }
        
        $permissionGroupCompanies = $restrictionData['group_company'] ?? [];
        $permissionCompanies = $restrictionData['contribution_level_code'] ?? [];
        $permissionLocations = $restrictionData['work_area_code'] ?? [];

        $criteria = [
            'work_area_code' => $permissionLocations,
            'group_company' => $permissionGroupCompanies,
            'contribution_level_code' => $permissionCompanies,
        ];

        $parentLink = 'Settings';
        $link = 'Layers';

        foreach ($criteria as $key => $value) {
            if (!is_array($value)) {
                $criteria[$key] = (array) $value;
            }
        }
        
        $approvalLayers = DB::table('approval_layers as al')
        ->select('al.employee_id', 'emp.fullname', 'emp.job_level', 'emp.contribution_level_code', 'emp.group_company', 'emp.office_area')
        ->selectRaw("GROUP_CONCAT(al.layer ORDER BY al.layer ASC SEPARATOR '|') AS layers")
        ->selectRaw("GROUP_CONCAT(al.approver_id ORDER BY al.layer ASC SEPARATOR '|') AS approver_ids")
        ->selectRaw("GROUP_CONCAT(emp1.fullname ORDER BY al.layer ASC SEPARATOR '|') AS approver_names")
        ->selectRaw("GROUP_CONCAT(emp1.job_level ORDER BY al.layer ASC SEPARATOR '|') AS approver_job_levels")
        ->leftJoin('employees as emp', 'emp.employee_id', '=', 'al.employee_id')
        ->leftJoin('employees as emp1', 'emp1.employee_id', '=', 'al.approver_id')
        ->groupBy('al.employee_id', 'emp.fullname', 'emp.job_level', 'emp.contribution_level_code', 'emp.group_company', 'emp.office_area')
        ->orderBy('emp.fullname')
        ->when(!empty($criteria), function ($query) use ($criteria) {
            $query->where(function ($query) use ($criteria) {
                foreach ($criteria as $key => $values) {
                    if (!empty($values)) {
                        $query->whereIn("emp.$key", $values);
                    }
                }
            });
        })
        ->get();

        $employees = Employee::select('employee_id', 'fullname')
        ->whereNotIn('job_level', ['2A', '2B', '2C', '2D', '3A', '3B','4A'])
        ->orderBy('fullname', 'asc')
        ->get();

    $employeeCount = $approvalLayers->unique('employee_id')->count();
        return view('pages.layers.layer', [
            'parentLink' => $parentLink,
            'link' => $link,
            'approvalLayers' => $approvalLayers,
            'employeeCount' => $employeeCount,
            'employees' => $employees,
        ]);
    }

    function updatelayer(Request $req) {
        $employeeId = $req->input('employee_id');
        $nikApps = $req->input('nik_app');
        $jumlahNikApp = count($nikApps);
        $userId = Auth::id();

        $approvalRequest = Goal::select('id')->where('employee_id', $employeeId)->first();

        $approvalLayersToDelete = ApprovalLayer::where('employee_id', $employeeId)->get();

        foreach ($approvalLayersToDelete as $layer) {
            ApprovalLayerBackup::create([
                'employee_id' => $layer->employee_id,
                'approver_id' => $layer->approver_id,
                'layer' => $layer->layer,
                'updated_by' => $layer->updated_by,
                'created_at' => $layer->created_at,
                'updated_at' => $layer->updated_at,
            ]);
        }

        ApprovalLayer::where('employee_id', $employeeId)->delete();

        //$cek="";
        $layer=1;
        for ($jml=0; $jml < $jumlahNikApp; $jml++) {
            $approverId = $nikApps[$jml];

            if($approverId<>''){
                ApprovalLayer::create([
                    'employee_id' => $employeeId,
                    'approver_id' => $approverId,
                    'layer' => $layer,
                    'updated_by' => $userId
                ]);    

                if($layer===1){
                    $cekApprovalRequest = ApprovalRequest::where('category', $this->category)
                                         ->where('employee_id', $employeeId)
                                         ->where('status', ['pending', 'sendback'])
                                         ->get();

                    if ($cekApprovalRequest->isNotEmpty()) {
                        $approvalRequestIds = $cekApprovalRequest->pluck('id');
                
                        DB::transaction(function() use ($employeeId, $approverId, $approvalRequestIds) {
                            ApprovalRequest::where('category', $this->category)
                                           ->where('employee_id', $employeeId)
                                           ->where('status', ['pending', 'sendback'])
                                           ->update([
                                               'current_approval_id' => $approverId,
                                               'updated_by' => null,
                                               'updated_at' => null
                                           ]);
                
                            Approval::whereIn('request_id', $approvalRequestIds)
                                    ->delete();
                        });
                    }
                }
            }
            $layer++;
        }

        Alert::success('Success');
        return redirect()->intended(route('layer', absolute: false));
    }

    public function importLayer(Request $request)
    {
        $request->validate([
            'excelFile' => 'required|mimes:xlsx,xls,csv'
        ]);
        
        // Muat file Excel ke dalam array
        $rows = Excel::toArray([], $request->file('excelFile'));
        $data = $rows[0]; // Ambil sheet pertama
        $employeeIds = [];

        // Mulai dari indeks 1 untuk mengabaikan header
        for ($i = 1; $i < count($data); $i++) {
            $employeeIds[] = $data[$i][0];
        }

        $employeeIds = array_unique($employeeIds);

        // Ambil employee_ids dari data
        //$employeeIds = array_unique(array_column($data, 'employee_id'));

        if (!empty($employeeIds)) {
            // Backup data sebelum menghapus
            $approvalLayersToDelete = ApprovalLayer::whereIn('employee_id', $employeeIds)->get();

            foreach ($approvalLayersToDelete as $layer) {
                ApprovalLayerBackup::create([
                    'employee_id' => $layer->employee_id,
                    'approver_id' => $layer->approver_id,
                    'layer' => $layer->layer,
                    'updated_by' => $layer->updated_by,
                    'created_at' => $layer->created_at,
                    'updated_at' => $layer->updated_at,
                ]);
            }
            // Hapus data lama
            ApprovalLayer::whereIn('employee_id', $employeeIds)->delete();
        }
        $userId = Auth::id();
        // Import data baru
        //Excel::import(new ApprovalLayerImport, $request->file('excelFile'));
        // Excel::import(new ApprovalLayerImport($userId), $request->file('excelFile'));

        // return back()->with('success', 'Data imported successfully.');
        $import = new ApprovalLayerImport($userId);
        Excel::import($import, $request->file('excelFile'));

        // Ambil ID karyawan yang memiliki layer lebih dari 6
        $invalidEmployees = $import->getInvalidEmployees();

        // Format pesan umpan balik
        $message = 'Data imported successfully.';
        if (!empty($invalidEmployees)) {
            $message .= '\nThe following employee IDs have layers greater than 6 and were not imported: \n' . implode(', ', $invalidEmployees);
        }

        return back()->with('success', $message);
    }

    public function show(Request $request)
    {
        $employeeId = $request->input('employee_id');
        
        $approvalLayers1 = DB::table('approval_layer_backups as al')
        ->select('al.employee_id', 'emp.fullname', 'emp.job_level', 'emp.contribution_level_code', 'emp.group_company', 'emp.office_area', 'al.updated_by', 'al.updated_at', 'usr.name')
        ->selectRaw("GROUP_CONCAT(al.layer ORDER BY al.layer ASC SEPARATOR '|') AS layers")
        ->selectRaw("GROUP_CONCAT(al.approver_id ORDER BY al.layer ASC SEPARATOR '|') AS approver_ids")
        ->selectRaw("GROUP_CONCAT(emp1.fullname ORDER BY al.layer ASC SEPARATOR '|') AS approver_names")
        ->selectRaw("GROUP_CONCAT(emp1.job_level ORDER BY al.layer ASC SEPARATOR '|') AS approver_job_levels")
        ->leftJoin('employees as emp', 'emp.employee_id', '=', 'al.employee_id')
        ->leftJoin('employees as emp1', 'emp1.employee_id', '=', 'al.approver_id')
        ->leftJoin('users as usr', 'usr.id', '=', 'al.updated_by')
        ->groupBy('al.employee_id', 'emp.fullname', 'emp.job_level', 'emp.contribution_level_code', 'emp.group_company', 'emp.office_area', 'al.updated_by', 'al.updated_at', 'usr.name')
        ->orderBy('al.updated_at', 'desc')
        ->where('al.employee_id', $employeeId)
        ->get();

        $approvalLayers2 = DB::table('approval_layers as al')
        ->select('al.employee_id', 'emp.fullname', 'emp.job_level', 'emp.contribution_level_code', 'emp.group_company', 'emp.office_area', 'al.updated_by', 'al.updated_at', 'usr.name')
        ->selectRaw("GROUP_CONCAT(al.layer ORDER BY al.layer ASC SEPARATOR '|') AS layers")
        ->selectRaw("GROUP_CONCAT(al.approver_id ORDER BY al.layer ASC SEPARATOR '|') AS approver_ids")
        ->selectRaw("GROUP_CONCAT(emp1.fullname ORDER BY al.layer ASC SEPARATOR '|') AS approver_names")
        ->selectRaw("GROUP_CONCAT(emp1.job_level ORDER BY al.layer ASC SEPARATOR '|') AS approver_job_levels")
        ->leftJoin('employees as emp', 'emp.employee_id', '=', 'al.employee_id')
        ->leftJoin('employees as emp1', 'emp1.employee_id', '=', 'al.approver_id')
        ->leftJoin('users as usr', 'usr.id', '=', 'al.updated_by')
        ->groupBy('al.employee_id', 'emp.fullname', 'emp.job_level', 'emp.contribution_level_code', 'emp.group_company', 'emp.office_area', 'al.updated_by', 'al.updated_at', 'usr.name')
        ->orderBy('al.updated_at', 'desc')
        ->where('al.employee_id', $employeeId)
        ->get();

        $approvalLayers = $approvalLayers2->merge($approvalLayers1);

        return response()->json($approvalLayers);
    }

    function layerAppraisal() {

        $roles = Auth()->user()->roles;

        $restrictionData = [];
        if(!is_null($roles)){
            $restrictionData = json_decode($roles->first()->restriction, true);
        }
        
        $permissionGroupCompanies = $restrictionData['group_company'] ?? [];
        $permissionCompanies = $restrictionData['contribution_level_code'] ?? [];
        $permissionLocations = $restrictionData['work_area_code'] ?? [];

        $criteria = [
            'work_area_code' => $permissionLocations,
            'group_company' => $permissionGroupCompanies,
            'contribution_level_code' => $permissionCompanies,
        ];

        $parentLink = 'Settings';
        $link = 'Layers';

        foreach ($criteria as $key => $value) {
            if (!is_array($value)) {
                $criteria[$key] = (array) $value;
            }
        }
        
        $approvalLayers = DB::table('approval_layers as al')
        ->select('al.employee_id', 'emp.fullname', 'emp.job_level', 'emp.contribution_level_code', 'emp.group_company', 'emp.office_area')
        ->selectRaw("GROUP_CONCAT(al.layer ORDER BY al.layer ASC SEPARATOR '|') AS layers")
        ->selectRaw("GROUP_CONCAT(al.approver_id ORDER BY al.layer ASC SEPARATOR '|') AS approver_ids")
        ->selectRaw("GROUP_CONCAT(emp1.fullname ORDER BY al.layer ASC SEPARATOR '|') AS approver_names")
        ->selectRaw("GROUP_CONCAT(emp1.job_level ORDER BY al.layer ASC SEPARATOR '|') AS approver_job_levels")
        ->leftJoin('employees as emp', 'emp.employee_id', '=', 'al.employee_id')
        ->leftJoin('employees as emp1', 'emp1.employee_id', '=', 'al.approver_id')
        ->groupBy('al.employee_id', 'emp.fullname', 'emp.job_level', 'emp.contribution_level_code', 'emp.group_company', 'emp.office_area')
        ->orderBy('emp.fullname')
        ->when(!empty($criteria), function ($query) use ($criteria) {
            $query->where(function ($query) use ($criteria) {
                foreach ($criteria as $key => $values) {
                    if (!empty($values)) {
                        $query->whereIn("emp.$key", $values);
                    }
                }
            });
        })
        ->get();

        $employees = Employee::select('employee_id', 'fullname')
        ->whereNotIn('job_level', ['2A', '2B', '2C', '2D', '3A', '3B','4A'])
        ->orderBy('fullname', 'asc')
        ->get();

    $employeeCount = $approvalLayers->unique('employee_id')->count();
        return view('pages.layers.layer-appraisal', [
            'parentLink' => $parentLink,
            'link' => $link,
            'approvalLayers' => $approvalLayers,
            'employeeCount' => $employeeCount,
            'employees' => $employees,
        ]);
    }

    function layerAppraisalEdit() {

        $roles = Auth()->user()->roles;

        $restrictionData = [];
        if(!is_null($roles)){
            $restrictionData = json_decode($roles->first()->restriction, true);
        }
        
        $permissionGroupCompanies = $restrictionData['group_company'] ?? [];
        $permissionCompanies = $restrictionData['contribution_level_code'] ?? [];
        $permissionLocations = $restrictionData['work_area_code'] ?? [];

        $criteria = [
            'work_area_code' => $permissionLocations,
            'group_company' => $permissionGroupCompanies,
            'contribution_level_code' => $permissionCompanies,
        ];

        $parentLink = 'Settings';
        $link = 'Layers';

        foreach ($criteria as $key => $value) {
            if (!is_array($value)) {
                $criteria[$key] = (array) $value;
            }
        }
        
        $approvalLayers = DB::table('approval_layers as al')
        ->select('al.employee_id', 'emp.fullname', 'emp.job_level', 'emp.contribution_level_code', 'emp.group_company', 'emp.office_area')
        ->selectRaw("GROUP_CONCAT(al.layer ORDER BY al.layer ASC SEPARATOR '|') AS layers")
        ->selectRaw("GROUP_CONCAT(al.approver_id ORDER BY al.layer ASC SEPARATOR '|') AS approver_ids")
        ->selectRaw("GROUP_CONCAT(emp1.fullname ORDER BY al.layer ASC SEPARATOR '|') AS approver_names")
        ->selectRaw("GROUP_CONCAT(emp1.job_level ORDER BY al.layer ASC SEPARATOR '|') AS approver_job_levels")
        ->leftJoin('employees as emp', 'emp.employee_id', '=', 'al.employee_id')
        ->leftJoin('employees as emp1', 'emp1.employee_id', '=', 'al.approver_id')
        ->groupBy('al.employee_id', 'emp.fullname', 'emp.job_level', 'emp.contribution_level_code', 'emp.group_company', 'emp.office_area')
        ->orderBy('emp.fullname')
        ->when(!empty($criteria), function ($query) use ($criteria) {
            $query->where(function ($query) use ($criteria) {
                foreach ($criteria as $key => $values) {
                    if (!empty($values)) {
                        $query->whereIn("emp.$key", $values);
                    }
                }
            });
        })
        ->get();

        $employees = Employee::select('employee_id', 'fullname')
        ->whereNotIn('job_level', ['2A', '2B', '2C', '2D', '3A', '3B','4A'])
        ->orderBy('fullname', 'asc')
        ->get();

    $employeeCount = $approvalLayers->unique('employee_id')->count();
        return view('pages.layers.layer-appraisal-edit', [
            'parentLink' => $parentLink,
            'link' => $link,
            'approvalLayers' => $approvalLayers,
            'employeeCount' => $employeeCount,
            'employees' => $employees,
        ]);
    }
}