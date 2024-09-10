<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\Location;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Support\Facades\Log;
use RealRashid\SweetAlert\Facades\Alert;
use App\Jobs\SendReminderScheduleEmailJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;


class ScheduleController extends Controller
{
    function schedule() {

        $userId = Auth::id();
        $parentLink = 'Settings';
        $link = 'Schedule';
        $schedules = Schedule::with('createdBy')->get();

        return view('pages.schedules.schedule', [
            'link' => $link,
            'parentLink' => $parentLink,
            'schedules' => $schedules,
            'userId' => $userId,
        ]);
    }
    function form() {
        $parentLink = 'Schedules';
        $link = 'Create';
        $allowedGroupCompanies = Employee::getUniqueGroupCompanies();
        $locations = Location::orderBy('area')->get();
        $companies = Company::orderBy('contribution_level_code')->get();
        
        return view('pages.schedules.form', [
            'link' => $link,
            'parentLink' => $parentLink,
            'locations' => $locations,
            'companies' => $companies,
            'allowedGroupCompanies' => $allowedGroupCompanies,
        ]);
    }
    function save(Request $req) {
        $link = 'schedule';
        //dd($req);
        //$model = schedule::find($req->id);
        $model = new schedule;
        $userId = Auth::id();
        $model->schedule_name       = $req->schedule_name;
        $model->event_type          = $req->event_type;
        //$model->employee_type       = $req->employee_type;
        $model->employee_type = $req->input('employee_type') ? implode(',', $req->input('employee_type')) : '';
        $model->bisnis_unit = $req->input('bisnis_unit') ? implode(',', $req->input('bisnis_unit')) : '';
        $model->company_filter = $req->input('company_filter') ? implode(',', $req->input('company_filter')) : '';
        $model->location_filter = $req->input('location_filter') ? implode(',', $req->input('location_filter')) : '';
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

        $today = date('Y-m-d');
        if($model->start_date <= $today && $model->end_date >= $today){
            $access_menu = 1;
        }else{
            $access_menu = 0;
        }

        foreach ($employeesToUpdate as $employee) {
            if($employee->date_of_joining <= $model->last_join_date){
                $doj=1;
            }else{
                $doj=0;
            }

            $accessMenuJson = json_decode($employee->access_menu, true);

            if (!isset($accessMenuJson['goals'])) {
                $accessMenuJson['goals'] = $access_menu;
            }else{
                $accessMenuJson = ['goals' => $access_menu];
            }

            if (!isset($accessMenuJson['doj'])) {
                $accessMenuJson['doj'] = $doj;
            }else{
                $accessMenuJson = ['doj' => $doj];
            }
            
            $updatedAccessMenu = json_encode($accessMenuJson);
            
            $employee->access_menu = $updatedAccessMenu;
            $employee->save();
        }
        
        //tes email
        $email = "eriton.dewa@kpn-corp.com";
        $name = "Eriton";
        $message = $req->messages;

        //dispatch(new SendReminderScheduleEmailJob($email, $name, $message));
        
        Alert::success('Success');
        return redirect()->intended(route('schedules', absolute: false));
    }
    function edit($id)
    {
        $parentLink = 'Schedule';
        $link = __('Edit');
        $model = Schedule::find($id);
 
        if(!$model)
            return redirect("schedules");

            return view('pages.schedules.edit', [
                'link' => $link,
                'parentLink' => $parentLink,
                'model' => $model,
            ]);
    }
    function update(Request $req) {
        $link = 'schedule';
        $model = Schedule::find($req->id_schedule);

        $model->schedule_name       = $req->schedule_name;
        $model->employee_type       = !empty($req->employee_type) ? $req->employee_type : '';
        $model->bisnis_unit         = !empty($req->bisnis_unit) ? $req->bisnis_unit : '';
        $model->company_filter      = !empty($req->company_filter) ? $req->company_filter : '';
        $model->location_filter     = !empty($req->location_filter) ? $req->location_filter : '';

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

        $today = date('Y-m-d');
        if($model->start_date <= $today && $model->end_date >= $today){
            $access_menu = 1;
        }else{
            $access_menu = 0;
        }

        foreach ($employeesToUpdate as $employee) {
            if($employee->date_of_joining <= $model->last_join_date){
                $doj=1;
            }else{
                $doj=0;
            }

            $accessMenuJson = json_decode($employee->access_menu, true);

            if (!isset($accessMenuJson['goals'])) {
                $accessMenuJson['goals'] = $access_menu;
            }else{
                $accessMenuJson = ['goals' => $access_menu];
            }

            if (!isset($accessMenuJson['doj'])) {
                $accessMenuJson['doj'] = $doj;
            }else{
                $accessMenuJson = ['doj' => $doj];
            }

            $updatedAccessMenu = json_encode($accessMenuJson);
            
            $employee->access_menu = $updatedAccessMenu;
            $employee->save();
        }
        //tes email
        $email = "eriton.dewa@kpn-corp.com";
        $name = "Eriton";
        $message = $req->messages;

        //dispatch(new SendReminderScheduleEmailJob($email, $name, $message));

        Alert::success('Success');
        return redirect()->intended(route('schedules', absolute: false));
    }
    public function softDelete(Request $request, $id)
    {
        $today = date('Y-m-d');
        $schedule = Schedule::findOrFail($id);
        
        if($schedule->start_date <= $today && $schedule->end_date >= $today){
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

                if (empty($accessMenuJson) || $accessMenuJson===null) {
                    $accessMenuJson = ['goals' => 0];
                } else {
                    $accessMenuJson['goals'] = 0;
                }

                $updatedAccessMenu = json_encode($accessMenuJson);
                
                $employee->access_menu = $updatedAccessMenu;
                $employee->save();
            }
        }
        // Memanggil metode delete() untuk soft delete
        $schedule->delete();
    }
    function reminderDailySchedules() {
        $today = date('Y-m-d');
        $dayOfWeek = now()->format('D');

        //disini berisi variabel $schedules untuk get data didalamnya
        $schedules = DB::table('schedules')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->whereNull('deleted_at')
            ->get();
        
        foreach ($schedules as $schedule) {
            // Ambil data dari tabel employees yang sesuai dengan filter di tabel schedules
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
                $employees = DB::table('employees')
                    ->leftJoin('goals', 'employees.employee_id', '=', 'goals.employee_id')
                    ->whereNull('goals.employee_id')
                    ->where('employees.employee_type', $schedule->employee_type)
                    ->where('employees.group_company', $schedule->bisnis_unit)
                    ->where('employees.contribution_level_code', $schedule->company_filter)
                    ->where('employees.work_area_code', $schedule->location_filter)
                    ->where('employees.date_of_joining', '<=', $schedule->last_join_date)
                    ->whereNotIn('employees.job_level', ['9B', '10A', '10B'])
                    ->select('employees.*')
                    ->get();

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