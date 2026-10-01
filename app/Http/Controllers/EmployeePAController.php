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

        // ORDER BY parent_company_id sementara kolomnya tidak ada di GROUP BY
        // membuat query ini gagal di MySQL dengan sql_mode default
        // (only_full_group_by) — halaman ini error 500 karenanya. MIN()
        // mempertahankan maksud urutannya (per parent company dulu) tanpa
        // mengubah pengelompokan.
        $designations = Designation::select('designation_name','job_code')
        ->orderByRaw('MIN(parent_company_id) asc')
        ->orderBy('designation_name', 'asc')
        ->orderBy('job_code', 'asc')
        ->groupBy('job_code','designation_name')
        ->get();
        $departments = Department::select('department_name')
        ->orderBy('department_name', 'asc')
        ->groupBy('department_name')
        ->get();
        $companies = Company::orderBy('contribution_level_code', 'asc')->get();
        $locations = Location::orderBy('area', 'asc')->get();
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
            'companies' => $companies,
            'locations' => $locations,
            'userId' => $userId,
            'departments' => $departments,
            'designations' => $designations,
        ]);
    }
    public function destroy(Request $request)
    {
        $id = $request->input('id');
        $userId = Auth::id();
        $employees = EmployeeAppraisal::where('employee_id', $id);
        // dd($calibrations);
        try{

        if ($employees->exists()) {
            $employees->delete();
        }
            return response()->json(['success' => true, 'message' => 'Employee terminated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to terminate Employee.']);
        }

    }
    /**
     * Data satu karyawan untuk mengisi modal Edit, diambil saat tombol Edit
     * diklik.
     *
     * Sebelumnya SELURUH record di-embed ke dalam atribut onclick setiap
     * baris:
     *
     *     onclick="showEditModal({{ json_encode($row) }})"
     *
     * Itu ~1,5 KB JSON per baris x 6.399 baris (~9 MB mentah, sekitar dua
     * kali lipat setelah di-escape ke atribut HTML) — penyebab utama halaman
     * report EmployeePA membengkak jadi 24,5 MB. Yang lebih penting: record
     * lengkap ikut terbawa, termasuk ktp, kk, npwp, nomor rekening, alamat,
     * tanggal lahir dan nama ibu kandung — semuanya terbaca di source
     * halaman dan tersimpan di cache browser.
     *
     * Endpoint ini hanya mengembalikan 8 field yang benar-benar dipakai form
     * Edit (lihat showEditModal di resources/js/report.js).
     */
    public function editData($employeeId)
    {
        $user = Auth::user();

        // Modal Edit dipakai dari dua layar dengan permission berbeda:
        // Settings > Employee (employeepa) dan Report > EmployeePA
        // (viewreport). Jadi dicek di sini, bukan lewat middleware route.
        abort_unless($user->can('employeepa') || $user->can('viewreport'), 403);

        $employee = EmployeeAppraisal::select([
            'employee_id',
            'fullname',
            'date_of_joining',
            'contribution_level_code',
            'unit',
            'designation_code',
            'job_level',
            'work_area_code',
            'group_company',
        ])->where('employee_id', $employeeId)->firstOrFail();

        // Batasan role yang sama dengan daftarnya, supaya endpoint ini tidak
        // bisa dipakai membaca karyawan di luar cakupan user. Kedua layar
        // (EmployeePAController::index dan report EmployeePA) memakai AND
        // untuk ketiga field ini, jadi dicocokkan persis seperti itu.
        $criteria = [
            'work_area_code' => $this->permissionLocations,
            'contribution_level_code' => $this->permissionCompanies,
            'group_company' => $this->permissionGroupCompanies,
        ];

        foreach (array_filter($criteria, fn ($values) => !empty($values)) as $field => $values) {
            abort_unless(in_array($employee->{$field}, $values), 403);
        }

        return response()->json(['status' => 'success', 'data' => $employee]);
    }

    public function update(Request $request)
    {
        $userId = Auth::id();
        $employee = EmployeeAppraisal::where('employee_id',$request->employee_id)->first();
        $companies = Company::where('contribution_level_code',$request->contribution_level_code)->first();
        $locations = Location::where('work_area',$request->office_area)->first();
        $designations = Designation::where('job_code',$request->designation_name)->first();
        $value_desg = $designations->designation_name." (".$designations->job_code.")";

        // Data before and after update as JSON
        $beforeData = [
            'fullname' => $employee->fullname,
            'date_of_joining' => $employee->date_of_joining,
            'company_name' => $employee->company_name,
            'contribution_level_code' => $employee->contribution_level_code,
            'unit' => $employee->unit,
            'designation' => $employee->designation,
            'designation_code' => $employee->designation_code,
            'designation_name' => $employee->designation_name,
            'job_level' => $employee->job_level,
            'work_area_code' => $employee->work_area_code,
            'office_area' => $employee->office_area,
        ];

        $afterData = [
            'fullname' => $request->fullname,
            'date_of_joining' => $request->date_of_joining,
            'company_name' => $companies->contribution_level,
            'contribution_level_code' => $request->contribution_level_code,
            'unit' => $request->unit,
            'designation' => $value_desg,
            'designation_code' => $designations->job_code,
            'designation_name' => $designations->designation_name,
            'job_level' => $request->job_level,
            'work_area_code' => $locations->work_area,
            'office_area' => $locations->area,
        ];

        // Insert data before and after as JSON in employee_pa_histories
        DB::table('employee_pa_histories')->insert([
            'employee_id' => $employee->employee_id,
            'before' => json_encode($beforeData),
            'after' => json_encode($afterData),
            'updated_by' => $userId,
            'updated_at' => now(),
        ]);

        $employee->update([
            'fullname' => $request->fullname,
            'date_of_joining' => $request->date_of_joining,
            'company_name' => $companies->contribution_level,
            'contribution_level_code' => $request->contribution_level_code,
            'unit' => $request->unit,
            'designation' => $value_desg,
            'designation_code' => $designations->job_code,
            'designation_name' => $designations->designation_name,
            'job_level' => $request->job_level,
            'work_area_code' => $locations->work_area,
            'office_area' => $locations->area,
            'updated_by' => $userId,
        ]);

        // return redirect()->back()->with('success', 'Employee updated successfully');
        return redirect()->back()->with('success', 'Employee deleted successfully.')->with('triggerFunction', 'EmployeePA');
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
