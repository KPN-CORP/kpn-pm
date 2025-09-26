<?php

namespace App\Http\Controllers;

use App\Models\PaReminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Location;
use App\Models\Company;
use App\Models\Employee;

class PaReminderController extends Controller
{
    protected $permissionGroupCompanies;
    protected $permissionCompanies;
    protected $permissionLocations;
    protected $roles;
    
    public function __construct()
    {
        $this->roles = Auth()->user()->roles;
        
        $restrictionData = [];

        if(!$this->roles->isEmpty()){
            $restrictionData = json_decode($this->roles->first()->restriction, true);
        }
        
        $this->permissionGroupCompanies = $restrictionData['group_company'] ?? [];
        $this->permissionCompanies = $restrictionData['contribution_level_code'] ?? [];
        $this->permissionLocations = $restrictionData['work_area_code'] ?? [];

    }
    public function index()
    {
        $userId = Auth::id();
        $user   = Auth::user();
        $parentLink = 'Settings';
        $link = 'Reminder PA';
        
        $schedules = PaReminder::orderBy('created_at', 'desc')->get();

        return view('pages.pa_reminders.schedule', [
            'link' => $link,
            'parentLink' => $parentLink,
            'userId' => $userId,
            'schedules' => $schedules,
        ]);
    }

    public function create()
    {
        $userId = Auth::id();
        $parentLink = 'Settings';
        $link = 'Reminder PA';
        $sublink  = 'Create';

        $listJobLevel = collect(Employee::getUniqueJobLevel());
        
        $allowedGroupCompanies = collect($this->permissionGroupCompanies);
        if ($allowedGroupCompanies->isEmpty()) {
            $allowedGroupCompanies = collect(Employee::getUniqueGroupCompanies());
        }

        $pcompanies = $this->permissionCompanies;
        
        if (empty($pcompanies)) {
            $companies = Company::orderBy('contribution_level_code')->get();
        }else{
            $companies = Company::orderBy('contribution_level_code')->whereIn('contribution_level_code',$pcompanies)->get();
        }
        
        $plocations = $this->permissionLocations;
        
        if (empty($plocations)) {
            $locations = Location::orderBy('area')->get();
        }else{
            $locations = Location::orderBy('area')->whereIn('work_area',$plocations)->get();
        }

        return view('pages.pa_reminders.create', [
            'link' => $link,
            'sublink ' => $sublink ,
            'parentLink' => $parentLink,
            'userId' => $userId,
            'locations' => $locations,
            'companies' => $companies,
            'listJobLevels' => $listJobLevel,
            'allowedGroupCompanies' => $allowedGroupCompanies
        ]);
    }

    public function store(Request $request)
    {
        $userId = Auth::id();
        
        $validated = $request->validate([
            'reminder_name'   => 'required|string|max:255',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after_or_equal:start_date',
            'includeList'     => 'nullable|boolean',
            'repeat_days_selected' => 'nullable|string', // hidden input hasil klik button hari
            'messages'        => 'nullable|string',
        ]);

        // ubah repeat_days string "Mon,Tue" → array
        // $repeatDays = $validated['repeat_days_selected']
        //     ? explode(',', $validated['repeat_days_selected'])
        //     : [];

        // simpan ke DB (pakai string, karena kolom text)
        $reminder = PaReminder::create([
            'reminder_name'   => $validated['reminder_name'],
            'bisnis_unit'     => $request->filled('bisnis_unit') ? implode(',', $request->input('bisnis_unit')) : '',
            'company_filter'  => $request->filled('company_filter') ? implode(',', $request->input('company_filter')) : '',
            'location_filter' => $request->filled('location_filter') ? implode(',', $request->input('location_filter')) : '',
            'grade'           => $request->filled('grade') ? implode(',', $request->input('grade')) : '',
            'start_date'      => $validated['start_date'],
            'end_date'        => $validated['end_date'],
            'includeList'     => $request->has('includeList') ? 1 : 0,
            'repeat_days'     => $request->filled('repeat_days_selected') ? implode(',', $request->input('repeat_days_selected')) : '',
            'messages'        => $validated['messages'] ?? null,
            'created_by'      => $userId,
            'created_at'      => now(),
        ]);

        return redirect()->route('reminderpaindex')
            ->with('success', 'Reminder PA berhasil dibuat.');
    }

    public function show(string $id)
    {
        
    }

    public function edit(string $id)
    {
        
    }

    public function update(Request $request, string $id)
    {
        
    }

    public function destroy(string $id)
    {
        
    }
}
