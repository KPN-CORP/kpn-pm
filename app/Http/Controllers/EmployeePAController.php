<?php

namespace App\Http\Controllers;

use App\Models\EmployeeAppraisal;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RealRashid\SweetAlert\Facades\Alert;
use App\Exports\EmployeepaExport;
use Maatwebsite\Excel\Facades\Excel;


class EmployeePAController extends Controller
{
    protected $groupCompanies;
    protected $companies;
    protected $locations;
    protected $permissionGroupCompanies;
    protected $permissionCompanies;
    protected $permissionLocations;
    protected $roles;

    public function __construct()
    {
        // $this->category = 'Goals';
        $this->roles = Auth()->user()->roles;

        $restrictionData = [];
        if (!is_null($this->roles) && $this->roles->isNotEmpty()) {
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
    
    public function index()
    {
        $userId = Auth::id();
        $parentLink = 'Settings';
        $link = 'Employee';

        // Data karyawan tidak diubah dari PA (employees_pa adalah view atas
        // employees), jadi halaman ini hanya daftar + terminate.
        $query = EmployeeAppraisal::whereNull('deleted_at')
        ->orderBy('office_area', 'asc')
        ->orderBy('fullname', 'asc')
        ->orderBy('contribution_level_code', 'asc');

        $permissionLocations = $this->permissionLocations;
        $permissionCompanies = $this->permissionCompanies;
        $permissionGroupCompanies = $this->permissionGroupCompanies;

        if (!empty($permissionLocations)) {
            $query->whereIn('work_area_code', $permissionLocations);
        }

        if (!empty($permissionCompanies)) {
            $query->whereIn('contribution_level_code', $permissionCompanies);
        }

        if (!empty($permissionGroupCompanies)) {
            $query->whereIn('group_company', $permissionGroupCompanies);
        }

        $employees = $query->get();
        
        return view('pages.employeepa.app', [
            'link' => $link,
            'parentLink' => $parentLink,
            'userId' => $userId,
            'employees' => $employees,
        ]);
    }
    public function destroy(Request $request)
    {
        $id = $request->input('id');
        $userId = Auth::id();
        // Per model (bukan query builder delete): employees_pa adalah view,
        // soft delete-nya ditulis ke employee_pa_profiles oleh model.
        $employees = EmployeeAppraisal::where('employee_id', $id)->get();
        // dd($calibrations);
        try{

        foreach ($employees as $employee) {
            $employee->delete();
        }
            return response()->json(['success' => true, 'message' => 'Employee terminated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to terminate Employee.']);
        }

    }
    public function exportEmployeepa()
    {
        $permissionLocations = $this->permissionLocations;
        $permissionCompanies = $this->permissionCompanies;
        $permissionGroupCompanies = $this->permissionGroupCompanies;

        // Pass permissions to the EmployeesExport instance
        return Excel::download(
            new EmployeepaExport($permissionLocations, $permissionCompanies, $permissionGroupCompanies),
            'employees.xlsx'
        );
    }
}
