<?php

namespace App\Http\Controllers;

use App\Models\EmployeeAppraisal;
use App\Models\EmployeePaPeriodExclusion;
use App\Services\AppService;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        // Karyawan aktif + karyawan yang sudah resign di HR tapi masih ikut
        // periode goal/appraisal berjalan (punya data di periode itu), supaya
        // admin bisa men-terminate mereka dari appraisal (dan kuota Rating).
        $appService = app(AppService::class);
        $periods = array_values(array_unique(array_filter([
            (int) $appService->appraisalPeriod(),
            (int) $appService->goalPeriod(),
        ])));

        $excludePeriod = (int) $appService->appraisalPeriod();

        // Karyawan yang dikeluarkan dari periode appraisal berjalan tetap
        // tampil (dengan tombol Restore), kecuali yang di-terminate semua periode.
        $excludedIds = EmployeePaPeriodExclusion::where('period', $excludePeriod)->pluck('employee_id');

        $query = EmployeeAppraisal::withTrashed()
        ->where(function ($q) use ($periods, $excludedIds) {
            foreach ($periods as $period) {
                $q->orWhere(fn ($w) => $w->periodMember($period));
            }
            if ($excludedIds->isNotEmpty()) {
                $q->orWhere(fn ($w) => $w->whereIn('employee_id', $excludedIds)->notPaTerminated());
            }
        })
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
            'periods' => $periods,
            'excludePeriod' => $excludePeriod,
            'excludedIds' => $excludedIds->flip(),
            'link' => $link,
            'parentLink' => $parentLink,
            'userId' => $userId,
            'employees' => $employees,
        ]);
    }
    /**
     * Terminate dari appraisal.
     *  - scope=period : hanya periode appraisal berjalan; periode lain tetap
     *                   menampilkan karyawan ini (employee_pa_period_exclusions).
     *  - scope=all    : semua periode (default, dipakai juga oleh tombol di
     *                   Report > EmployeePA lewat resources/js/report.js).
     */
    public function destroy(Request $request)
    {
        $id = $request->input('id');
        $scope = $request->input('scope', 'all');
        // withTrashed: karyawan yang sudah resign di HR juga bisa di-terminate
        // dari appraisal (lihat index()).
        $employees = $id ? EmployeeAppraisal::withTrashed()->where('employee_id', $id)->get() : collect();
        if ($employees->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }

        try {
            if ($scope === 'period') {
                $period = (int) app(AppService::class)->appraisalPeriod();

                EmployeePaPeriodExclusion::updateOrCreate(
                    ['employee_id' => $id, 'period' => $period],
                    ['reason' => $request->input('reason'), 'created_by' => Auth::id()]
                );

                Log::channel('audit')->info('PA period exclusion added', [
                    'employee_id' => $id, 'period' => $period, 'by' => Auth::id(),
                ]);

                return response()->json(['success' => true, 'message' => "Employee removed from appraisal period $period."]);
            }

            // Per model (bukan query builder delete): employees_pa adalah view,
            // soft delete-nya ditulis ke employee_pa_profiles oleh model.
            foreach ($employees as $employee) {
                $employee->delete();
            }

            Log::channel('audit')->info('PA terminate (all periods)', ['employee_id' => $id, 'by' => Auth::id()]);

            return response()->json(['success' => true, 'message' => 'Employee terminated successfully.']);
        } catch (\Exception $e) {
            Log::error('Terminate employee PA failed: '.$e->getMessage(), ['employee_id' => $id, 'scope' => $scope]);

            return response()->json(['success' => false, 'message' => 'Failed to terminate Employee.']);
        }
    }

    /**
     * Batalkan "hanya periode ini": karyawan kembali tampil di periode itu.
     */
    public function restorePeriod(Request $request)
    {
        $id = $request->input('id');
        $period = (int) app(AppService::class)->appraisalPeriod();

        $deleted = $id ? EmployeePaPeriodExclusion::where('employee_id', $id)->where('period', $period)->delete() : 0;
        if (! $deleted) {
            return response()->json(['success' => false, 'message' => 'Employee is not excluded from this period.'], 404);
        }

        Log::channel('audit')->info('PA period exclusion removed', [
            'employee_id' => $id, 'period' => $period, 'by' => Auth::id(),
        ]);

        return response()->json(['success' => true, 'message' => "Employee restored to appraisal period $period."]);
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
