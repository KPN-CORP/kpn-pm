<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\EmployeeAppraisal;
use App\Models\User;
use App\Models\ApprovalLayer;
use App\Models\Location;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use App\Jobs\ProcessEmployeeData;

class EmployeeController extends Controller
{
    function employee() {
        $link = 'employee';

        $employees = employee::all();
        $locations = Location::orderBy('area')->get();

        return view('pages.employees.employee', [
            'link' => $link,
            'employees' => $employees,
            'locations' => $locations,
        ]);        
    }
    public function EmployeeInactive()
    {
        Log::info('EmployeeInactive method started.'); // Logging start

        // URL API
        $url = 'https://kpncorporation.darwinbox.com/masterapi/employee';

        // Data untuk request
        $data = [
            "api_key" => "08250fed4ef60d6c22fe007afd929c0f98ba0da2a73554921f8569a93ec25970e032fd4616f9d934251cba0489f868448c35017d84f7f6e80096610590d0e406",
            "datasetKey" => "11825c66855343b39a819a78eefc7bfb93d9ede4ca6f632e6f24a22295e24169f04eb795018eba86b448f35c886f866329b9acaac1b5e814814ca471a2a9460c"
        ];

        // Header
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ZGFyd2luYm94c3R1ZGlvOkRCc3R1ZGlvMTIzNDUh'
        ];

        try {
            Log::info('Sending request to API', ['url' => $url, 'data' => $data]); // Logging request details

            // Request ke API menggunakan Laravel Http Client
            $response = Http::withHeaders($headers)->post($url, $data);

            // Check response status
            if ($response->failed()) {
                Log::error('API request failed', ['status' => $response->status(), 'response' => $response->body()]);
                return response()->json(['message' => 'Failed to fetch employees data'], 500);
            }

            // Parse response
            $employees = $response->json('employee_data');

            $number_data = 0;

            Log::info('API response received', ['employee_count' => count($employees)]);

            // Simpan data ke database
            foreach ($employees as $employee) {
                $existingEmployee = Employee::where('employee_id', $employee['employee_id'])->first();
    
                if ($existingEmployee) {
                    // Convert the `deleted_at` to date format
                    $deletedAtDate = $existingEmployee->deleted_at ? date('Y-m-d', strtotime($existingEmployee->deleted_at)) : null;
                    
                    if ($deletedAtDate !== $employee['date_of_exit']) {
                        
                        DB::table('employees')
                        ->where('employee_id', $employee['employee_id'])
                        ->update([
                            'deleted_at' => $employee['date_of_exit'] . ' 00:00:00',
                            'email' => $existingEmployee->email . '_terminate',
                        ]);
                        DB::table('users')
                        ->where('employee_id', $employee['employee_id'])
                        ->update([
                            'email' => $existingEmployee->email . '_terminate',
                        ]);
                        $number_data++;
                    }
                }
            }

            Log::info('Inactive Employees data successfully saved', ['saved_count' => $number_data]);

            return response()->json(['message' => $number_data.' Inactive Employees data successfully saved']);
        } catch (\Exception $e) {
            Log::error('Exception occurred in EmployeeInactive method', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred: '.$e->getMessage()], 500);
        }
    }
    public function fetchAndStoreEmployees()
    {
        ProcessEmployeeData::dispatch([0, 1801, 3601, 5401]);
        // ProcessEmployeeData::dispatch([3601, 5401]);

        return response()->json(['message' => 'Jobs dispatched successfully']);
    }
    public function updateEmployeeAccessMenu()
    {
        $today = Carbon::today()->format('Y-m-d');

        // Get schedules with start_date or end_date equals today
        $schedules = DB::table('schedules')
            ->where(function($query) use ($today) {
                $query->where('start_date', $today)
                      //->orWhere('end_date', $today);
                      ->orWhere(DB::raw('DATE_ADD(end_date, INTERVAL 1 DAY)'), $today);
            })
            ->whereNull('deleted_at')
            ->get();
            // dd($schedules);

            foreach ($schedules as $schedule) {
                if($schedule->event_type<>'masterschedulepa'){
                    if ($schedule->start_date == $today) {
                        // Update employees' access_menu to {"goals":1}
                        $this->updateEmployees($schedule, '1');
                    }
                    
                    //if ($schedule->end_date == $today) {
                    if (Carbon::parse($schedule->end_date)->addDay()->format('Y-m-d') == $today) {
                        // Update employees' access_menu to {"goals":0}
                        $this->updateEmployees($schedule, '0');
                    }
                }
            }

        return 'Employee access menu updated successfully.';
    }
    protected function updateEmployees($schedule, $accessMenu)
    {
        // $query = DB::table('employees');
        $query = Employee::query();
        $querypa = EmployeeAppraisal::query();

        if ($schedule->employee_type) {
            $query->where('employee_type', $schedule->employee_type);
            $querypa->where('employee_type', $schedule->employee_type);
        }

        if ($schedule->bisnis_unit) {
            $query->whereIn('group_company', explode(',', $schedule->bisnis_unit));
            $querypa->whereIn('group_company', explode(',', $schedule->bisnis_unit));
        }

        if ($schedule->company_filter) {
            $query->whereIn('contribution_level_code', explode(',', $schedule->company_filter));
            $querypa->whereIn('contribution_level_code', explode(',', $schedule->company_filter));
        }

        if ($schedule->location_filter) {
            $query->whereIn('work_area_code', explode(',', $schedule->location_filter));
            $querypa->whereIn('work_area_code', explode(',', $schedule->location_filter));
        }

        if ($schedule->last_join_date) {
            $query->where('date_of_joining', '<=', $schedule->last_join_date);
            $querypa->where('date_of_joining', '<=', $schedule->last_join_date);
        }

        $employees = $query->get();
        $employeePA = $querypa->get();

        if($schedule->event_type=='goals'){
            foreach ($employees as $employee) {

                $accessMenuJson = json_decode($employee->access_menu, true);
    
                $accessMenuJson['goals'] = $accessMenu;
                $accessMenuJson['doj'] = 1;
    
                DB::table('employees')
                    ->where('id', $employee->id)
                    ->update([
                        'access_menu' => json_encode($accessMenuJson),
                        'updated_at' => Carbon::now()  // Update the updated_at column
                    ]);
            }
        }else if($schedule->event_type=='schedulepa'){
            foreach ($employeePA as $employee) {

                $accessMenuJson = json_decode($employee->access_menu, true);
    
                $accessMenuJson['accesspa'] = 1;
                $accessMenuJson['createpa'] = $accessMenu;
                $accessMenuJson['review360'] = $schedule->review_360;
    
                DB::table('employees_pa')
                    ->where('id', $employee->id)
                    ->update([
                        'access_menu' => json_encode($accessMenuJson),
                        'updated_at' => Carbon::now()  // Update the updated_at column
                    ]);
            }
        }
    }
}
