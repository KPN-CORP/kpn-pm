<?php

namespace App\Http\Controllers;

use App\Models\EmployeeAppraisal;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RealRashid\SweetAlert\Facades\Alert;


class EmployeePAController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $parentLink = 'Settings';
        $link = 'Employee';
        
        $companies = Company::orderBy('contribution_level_code', 'asc')->get();
        $employees = EmployeeAppraisal::whereNull('deleted_at')
        ->where('work_area_code','OU001')
        ->orderBy('office_area', 'asc')
        ->orderBy('fullname', 'asc')
        ->orderBy('contribution_level_code', 'asc')
        ->get();
        
        return view('pages.employeepa.app', [
            'link' => $link,
            'parentLink' => $parentLink,
            'userId' => $userId,
            'employees' => $employees,
            'companies' => $companies,
            'userId' => $userId,
        ]);
    }
    public function destroy($id)
    {
        $userId = Auth::id();
        $employees = EmployeeAppraisal::where('employee_id', $id);
        // dd($calibrations);
        if ($employees->exists()) {
            $employees->update(['deleted_by' => $userId]);
            $employees->delete();
        }

        return redirect()->route('admemployee')->with('success', 'Employee deleted successfully.');
    }
    public function update(Request $request)
    {
        $employee = EmployeeAppraisal::where('employee_id',$request->employee_id)->first();
        $employee->update([
            'fullname' => $request->fullname,
            'date_of_joining' => $request->date_of_joining,
            'contribution_level_code' => $request->contribution_level_code,
            'unit' => $request->unit,
            'designation_name' => $request->designation_name,
            'job_level' => $request->job_level,
            'office_area' => $request->office_area,
        ]);

        return redirect()->back()->with('success', 'Employee updated successfully');
    }
}
