<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\Location;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\RoleHasPermission;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use RealRashid\SweetAlert\Facades\Alert;
use App\Jobs\SendReminderScheduleEmailJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Spatie\Permission\PermissionRegistrar;

class ScheduleController extends Controller
{
    function schedule() {

        $userId = Auth::id();
        $parentLink = 'Settings';
        $link = 'Schedule';
        $today = Carbon::today();
        $schedules = Schedule::with('createdBy')->get();
        $schedulemasterpa = schedule::where('event_type','masterschedulepa')
                            ->whereDate('start_date', '<=', $today)
                            ->whereDate('end_date', '>=', $today)
                            ->orderBy('created_at')
                            ->first();
                            
        return view('pages.schedules.schedule', [
            'link' => $link,
            'parentLink' => $parentLink,
            'schedules' => $schedules,
            'userId' => $userId,
            'schedulemasterpa' => $schedulemasterpa,
        ]);
    }
    function form() {
        $parentLink = 'Setting';
        $link = 'Schedules';
        $sublink = 'Create Schedules';
        
        $allowedGroupCompanies = Employee::getUniqueGroupCompanies();
        $locations = Location::orderBy('area')->get();
        $companies = Company::orderBy('contribution_level_code')->get();
        $today = Carbon::today();
        $schedulemasterpa = schedule::where('event_type','masterschedulepa')
                            ->whereDate('start_date', '<=', $today)
                            ->whereDate('end_date', '>=', $today)
                            ->orderBy('created_at')
                            ->first();
        
        return view('pages.schedules.form', [
            'sublink' => $sublink,
            'link' => $link,
            'parentLink' => $parentLink,
            'locations' => $locations,
            'companies' => $companies,
            'allowedGroupCompanies' => $allowedGroupCompanies,
            'schedulemasterpa' => $schedulemasterpa,
        ]);
    }
    function save(Request $req) {
        $link = 'schedule';
        //dd($req);
        //$model = schedule::find($req->id);
        $today = date('Y-m-d');
        $model = new schedule;
        $userId = Auth::id();
        $review360 = isset($req->review_360) ? $req->review_360 : 0;

        $model->schedule_name       = $req->schedule_name;
        $model->event_type          = $req->event_type;
        $model->schedule_periode    = $req->schedule_periode;
        $model->employee_type       = $req->input('employee_type') ? implode(',', $req->input('employee_type')) : '';
        $model->bisnis_unit         = $req->input('bisnis_unit') ? implode(',', $req->input('bisnis_unit')) : '';
        $model->company_filter      = $req->input('company_filter') ? implode(',', $req->input('company_filter')) : '';
        $model->location_filter     = $req->input('location_filter') ? implode(',', $req->input('location_filter')) : '';
        $model->review_360          = $review360;
        $model->last_join_date      = $req->last_join_date;
        $model->start_date          = $req->start_date;
        $model->end_date            = $req->end_date;
        $model->checkbox_reminder   = isset($req->checkbox_reminder) ? $req->checkbox_reminder : 0;
        $model->created_by          = $userId;
        
        if ($req->checkbox_reminder == 1) {
            
            $model->inputState = $req->inputState;
            
            if ($req->inputState == 'repeaton') {
                $model->repeat_days = $req->repeat_days_selected;
                $model->before_end_date = null;
            } elseif ($req->inputState == 'beforeenddate') {
                $model->repeat_days = null;
                $model->before_end_date = $req->before_end_date;
            }
            
            $model->messages = $req->messages;
        } else {
            $model->messages = null;
            $model->repeat_days = null;
            $model->inputState = null;
            $model->before_end_date = null;
        }

        $model->save();

        if($req->event_type=="masterschedulepa"){

            //cek id di permission untuk schedulepa & masterschedulepa
            $idpermissions = Permission::where('name','schedulepa')->first();
            $idschedulepa = $idpermissions->id;

            //cek di role has permission yg memiliki akses ke id 6 schedule
            $idroles = RoleHasPermission::where('permission_id', '6')->whereNotIn('role_id',['1','8'])->get();
            
            //cek tanggal sesudah dan sebelum
            if($req->start_date <= $today && $req->end_date >= $today){

                foreach($idroles as $idrole){
                    //input data di RoleHasPermission dengan permission_id $idschedulepa untuk user yg memiliki akses permission_id=6
                    $existingPermission = RoleHasPermission::where('role_id', $idrole->role_id)
                                               ->where('permission_id', $idschedulepa)
                                               ->first();

                    if (!$existingPermission) {
                        RoleHasPermission::create([
                            'role_id' => $idrole->role_id,
                            'permission_id' => $idschedulepa,
                        ]);
                    }
                }
                app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
            }
        }else{
            $query = Employee::query();

            if ($model->location_filter) {
                $query->whereIn('work_area_code', explode(',', $model->location_filter));
            }

            if ($model->company_filter) {
                $query->whereIn('contribution_level_code', explode(',', $model->company_filter));
            }

            if ($model->bisnis_unit) {
                $query->whereIn('group_company', explode(',', $model->bisnis_unit));
            }

            if ($model->employee_type) {
                $query->whereIn('employee_type', explode(',', $model->employee_type));
            }

            $employeesToUpdate = $query->get();
            // $employeesToUpdate = $query->where('date_of_joining', '<=', $model->last_join_date)->get();

            
            if($model->start_date <= $today && $model->end_date >= $today){
                $access_menu = 1;
                $accesspa = 1;
            }else{
                $accesspa = 0;
                $accesspa = 0;
            }

            foreach ($employeesToUpdate as $employee) {
                if($employee->date_of_joining <= $model->last_join_date){
                    $doj=1;
                    $createpa=1;
                }else{
                    $doj=0;
                    $createpa=0;
                }

                $accessMenuJson = json_decode($employee->access_menu, true);

                if($req->event_type=="goals"){
                    $accessMenuJson['goals'] = $access_menu;
                    $accessMenuJson['doj'] = $doj;
                }else if($req->event_type=="schedulepa"){
                    $accessMenuJson['accesspa'] = $accesspa;
                    $accessMenuJson['createpa'] = $createpa;
                    $accessMenuJson['review360'] = $review360;
                }
                
                
                $updatedAccessMenu = json_encode($accessMenuJson);
                
                $employee->access_menu = $updatedAccessMenu;
                $employee->save();
            }
        }
        
        Alert::success('Success');
        return redirect()->intended(route('schedules', absolute: false));
    }
    function edit($encryptedId)
    {
        $id = Crypt::decrypt($encryptedId);
        $parentLink = 'Setting';
        $link = 'Schedules';
        $sublink = 'Update';
        $model = Schedule::find($id);
        $today = Carbon::today();
        $schedulemasterpa = Schedule::where('event_type','masterschedulepa')
                            ->whereDate('start_date', '<=', $today)
                            ->whereDate('end_date', '>=', $today)
                            ->orderBy('created_at')
                            ->first();
 
        if(!$model)
            return redirect("schedules");

            return view('pages.schedules.edit', [
                'link' => $link,
                'sublink' => $sublink,
                'parentLink' => $parentLink,
                'model' => $model,
                'schedulemasterpa' => $schedulemasterpa,
            ]);
    }
    function update(Request $req) {
        $link = 'schedule';
        $model = Schedule::find($req->id_schedule);
        $review360 = isset($req->review_360) ? $req->review_360 : 0;

        $model->schedule_name       = $req->schedule_name;
        $model->employee_type       = !empty($req->employee_type) ? $req->employee_type : '';
        $model->bisnis_unit         = !empty($req->bisnis_unit) ? $req->bisnis_unit : '';
        $model->company_filter      = !empty($req->company_filter) ? $req->company_filter : '';
        $model->location_filter     = !empty($req->location_filter) ? $req->location_filter : '';
        $model->schedule_periode    = $req->schedule_periode;
        $model->review_360          = $review360;
        $model->last_join_date      = $req->last_join_date;
        $model->start_date          = $req->start_date;
        $model->end_date            = $req->end_date;
        $model->checkbox_reminder   = isset($req->checkbox_reminder) ? $req->checkbox_reminder : 0;

        if ($req->checkbox_reminder == 1) {
            
            $model->inputState = $req->inputState;
            
            if ($req->inputState == 'repeaton') {
                $model->repeat_days = $req->repeat_days_selected;
                $model->before_end_date = null;
            } elseif ($req->inputState == 'beforeenddate') {
                $model->repeat_days = null;
                $model->before_end_date = $req->before_end_date;
            }
            
            $model->messages = $req->messages;
        } else {
            $model->messages = null;
            $model->repeat_days = null;
            $model->inputState = null;
            $model->before_end_date = null;
        }

        $model->save();
        
        $today = date('Y-m-d');
        if($req->event_type=="Master Schedule PA"){

            //cek id di permission untuk schedulepa & masterschedulepa
            $idpermissions = Permission::where('name','schedulepa')->first();
            $idschedulepa = $idpermissions->id;

            //cek di role has permission yg memiliki akses ke id 6 schedule
            $idroles = RoleHasPermission::where('permission_id', '6')->whereNotIn('role_id',['1','8'])->get();

            //cek tanggal sesudah dan sebelum
            if($req->start_date <= $today && $req->end_date >= $today){

                foreach($idroles as $idrole){
                    //input data di RoleHasPermission dengan permission_id $idschedulepa untuk user yg memiliki akses permission_id=6
                    $existingPermission = RoleHasPermission::where('role_id', $idrole->role_id)
                                               ->where('permission_id', $idschedulepa)
                                               ->first();

                    if (!$existingPermission) {
                        RoleHasPermission::create([
                            'role_id' => $idrole->role_id,
                            'permission_id' => $idschedulepa,
                        ]);
                    }
                }
                app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
            }else{
                foreach ($idroles as $idrole) {
                    RoleHasPermission::where('role_id', $idrole->role_id)
                                     ->where('permission_id', $idschedulepa)
                                     ->delete();
                }
                
                app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
            }
        }else{
            // dd('test');
            $query = Employee::query();

            if ($model->location_filter) {
                $query->whereIn('work_area_code', explode(',', $model->location_filter));
            }

            if ($model->company_filter) {
                $query->whereIn('contribution_level_code', explode(',', $model->company_filter));
            }

            if ($model->bisnis_unit) {
                $query->whereIn('group_company', explode(',', $model->bisnis_unit));
            }

            if ($model->employee_type) {
                $query->whereIn('employee_type', explode(',', $model->employee_type));
            }

            //$employeesToUpdate = $query->where('date_of_joining', '<=', $model->last_join_date)->get();
            $employeesToUpdate = $query->get();

            
            if($model->start_date <= $today && $model->end_date >= $today){
                $access_menu = 1;
                $accesspa = 1;
            }else{
                $access_menu = 0;
                $accesspa = 0;
            }

            foreach ($employeesToUpdate as $employee) {
                if($employee->date_of_joining <= $model->last_join_date){
                    $doj=1;
                    $createpa=1;
                }else{
                    $doj=0;
                    $createpa=0;
                }

                $accessMenuJson = json_decode($employee->access_menu, true);

                if($req->event_type=="Goals"){
                    $accessMenuJson['goals'] = $access_menu;
                    $accessMenuJson['doj'] = $doj;
                }else if($req->event_type=="Schedule PA"){
                    $accessMenuJson['accesspa'] = $accesspa;
                    $accessMenuJson['createpa'] = $createpa;
                    $accessMenuJson['review360'] = $review360;
                }

                $updatedAccessMenu = json_encode($accessMenuJson);
                
                $employee->access_menu = $updatedAccessMenu;
                $employee->save();
            }
        }

        Alert::success('Success');
        return redirect()->intended(route('schedules', absolute: false));
    }
    public function softDelete($id)
    {
        $today = date('Y-m-d');
        $schedule = Schedule::findOrFail($id);

        if ($schedule->event_type == "masterschedulepa") {
            // Handle master schedule deletion
            $idpermissions = Permission::where('name', 'schedulepa')->first();
            $idschedulepa = $idpermissions->id;

            $idroles = RoleHasPermission::where('permission_id', '6')
                                        ->whereNotIn('role_id', ['1', '8'])->get();

            foreach ($idroles as $idrole) {
                RoleHasPermission::where('role_id', $idrole->role_id)
                                ->where('permission_id', $idschedulepa)
                                ->delete();
            }

            app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
        } else {
            if ($schedule->start_date <= $today && $schedule->end_date >= $today) {
                $query = Employee::query();

                if ($schedule->location_filter) {
                    $query->whereIn('work_area_code', explode(',', $schedule->location_filter));
                }

                if ($schedule->company_filter) {
                    $query->whereIn('contribution_level_code', explode(',', $schedule->company_filter));
                }

                if ($schedule->bisnis_unit) {
                    $query->whereIn('group_company', explode(',', $schedule->bisnis_unit));
                }

                if ($schedule->employee_type) {
                    $query->whereIn('employee_type', explode(',', $schedule->employee_type));
                }

                $employeesToUpdate = $query->where('date_of_joining', '<=', $schedule->last_join_date)->get();

                foreach ($employeesToUpdate as $employee) {
                    $accessMenuJson = json_decode($employee->access_menu, true);

                    if (empty($accessMenuJson) || $accessMenuJson === null) {
                        $accessMenuJson = ['goals' => 0];
                    } else {
                        $accessMenuJson['goals'] = 0;
                    }

                    $updatedAccessMenu = json_encode($accessMenuJson);

                    $employee->access_menu = $updatedAccessMenu;
                    $employee->save();
                }
            }
        }

        // Soft delete the schedule
        $schedule->delete();

        // Redirect back with a success message
        return redirect()->route('schedules')->with('success', 'Schedule has been successfully deleted.');
    }

    function reminderDailySchedules() {
        $today = date('Y-m-d');
        $dayOfWeek = now()->format('D');

        //disini berisi variabel $schedules untuk get data didalamnya
        $schedules = DB::table('schedules')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where('checkbox_reminder', '=', 1)
            ->whereNull('deleted_at')
            ->get();
        
        foreach ($schedules as $schedule) {
            // Ambil data dari tabel employees yang sesuai dengan filter di tabel schedules
            if($schedule->checkbox_reminder=='1'){
                $sendReminder = false;

                if ($schedule->inputState == 'beforeenddate') {
                    $reminderStartDate = Carbon::parse($schedule->end_date)->subDays($schedule->before_end_date);
                    if (Carbon::today()->between($reminderStartDate, Carbon::parse($schedule->end_date))) {
                        $sendReminder = true;
                    }
                } elseif ($schedule->inputState == 'repeaton') {
                    $repeatDays = explode(',', $schedule->repeat_days);
                    if (in_array($dayOfWeek, $repeatDays)) {
                        $sendReminder = true;
                    }
                }

                if ($sendReminder) {
                    // $employees = DB::table('employees')
                    //     ->leftJoin('goals', 'employees.employee_id', '=', 'goals.employee_id')
                    //     ->whereNull('goals.employee_id')
                    //     ->where('employees.employee_type', $schedule->employee_type)
                    //     ->where('employees.group_company', $schedule->bisnis_unit)
                    //     ->where('employees.contribution_level_code', $schedule->company_filter)
                    //     ->where('employees.work_area_code', $schedule->location_filter)
                    //     ->where('employees.date_of_joining', '<=', $schedule->last_join_date)
                    //     ->whereNotIn('employees.job_level', ['9B', '10A', '10B'])
                    //     ->select('employees.*')
                    //     ->get();

                    $query = Employee::query();

                    // Memastikan employees yang tidak memiliki goals
                    $query->doesntHave('goal');

                    // Filter berdasarkan employee_type jika ada
                    if ($schedule->employee_type) {
                        $query->whereIn('employee_type', explode(',', $schedule->employee_type));
                    }

                    // Filter berdasarkan group_company jika ada
                    if ($schedule->bisnis_unit) {
                        $query->whereIn('group_company', explode(',', $schedule->bisnis_unit));
                    }

                    // Filter berdasarkan contribution_level_code jika ada
                    if ($schedule->company_filter) {
                        $query->whereIn('contribution_level_code', explode(',', $schedule->company_filter));
                    }

                    // Filter berdasarkan work_area_code jika ada
                    if ($schedule->location_filter) {
                        $query->whereIn('work_area_code', explode(',', $schedule->location_filter));
                    }

                    // Filter berdasarkan date_of_joining
                    $query->where('date_of_joining', '<=', $schedule->last_join_date);

                    // Exclude job levels
                    $query->whereNotIn('job_level', ['9B', '10A', '10B']);

                    // Get employees
                    $employees = $query->get();
                    // dd($employees);

                    // Kirim email
                    foreach ($employees as $employee) {
                        //$email = $employee->email;
                        $email = 'eriton.dewa@kpn-corp.com';
                        $name = $employee->fullname;
                        $message = $schedule->messages;

                        dispatch(new SendReminderScheduleEmailJob($email, $name, $message));
                        //echo "penerima : $email <br>nama : $name <br>isi email : $message <br>";
                    }
                }

            }
        }
    }
    function DailyUpdateSchedulePA() {
        $today = date('Y-m-d');
        $scheduledatas = schedule::where('event_type','masterschedulepa')->get();

        foreach($scheduledatas as $scheduledata){

            $idpermissions = Permission::where('name','schedulepa')->first();
            $idschedulepa = $idpermissions->id;

            //cek di role has permission yg memiliki akses ke id 6 schedule
            $idroles = RoleHasPermission::where('permission_id', '6')->whereNotIn('role_id',['1','8'])->get();

            if($scheduledata->start_date==$today){
                foreach($idroles as $idrole){
                    //input data di RoleHasPermission dengan permission_id $idschedulepa untuk user yg memiliki akses permission_id=6
                    $existingPermission = RoleHasPermission::where('role_id', $idrole->role_id)
                                            ->where('permission_id', $idschedulepa)
                                            ->first();
                    if (!$existingPermission) {
                        RoleHasPermission::create([
                            'role_id' => $idrole->role_id,
                            'permission_id' => $idschedulepa,
                        ]);
                    }
                }
                app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
            }

            $scheduledata->end_date = Carbon::parse($scheduledata->end_date)->addDay()->format('Y-m-d');
            if($scheduledata->end_date==$today){                
                foreach ($idroles as $idrole) {
                    RoleHasPermission::where('role_id', $idrole->role_id)
                                    ->where('permission_id', $idschedulepa)
                                    ->delete();
                }
                app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
            }
        }
    }
}