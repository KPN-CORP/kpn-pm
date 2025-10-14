<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;
use App\Models\PaReminder;
use App\Models\User;
use App\Jobs\SendReminderEmailJob;
use Carbon\Carbon;

class SendPaReminders extends Command
{
    protected $signature = 'reminders:send';
    protected $description = 'Send PA reminders to employees';

    public function handle()
    {
        $today = Carbon::now();
        $dayName = $today->format('D'); // Mon, Tue, Wed...a

        // Cari reminder aktif
        $reminders = PaReminder::whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->get();

        foreach ($reminders as $reminder) {
            // cek apakah hari ini ada di repeat_days
            $repeatDays = explode(',', $reminder->repeat_days);
            if (!in_array($dayName, $repeatDays)) {
                continue;
            }

            // Ambil karyawan sesuai filter 
            // $employees = Employee::query()
            //     ->when($reminder->bisnis_unit, fn($q) => $q->where('group_company', $reminder->bisnis_unit))
            //     ->when($reminder->company_filter, fn($q) => $q->where('contribution_level_code', $reminder->company_filter))
            //     ->when($reminder->location_filter, fn($q) => $q->where('work_area_code', $reminder->location_filter))
            //     ->when($reminder->grade, fn($q) => $q->where('job_level', $reminder->grade))
            //     ->get();
            $employees = Employee::query()
                ->when($reminder->bisnis_unit, fn($q) => $q->where('group_company', $reminder->bisnis_unit))
                ->when($reminder->company_filter, fn($q) => $q->where('contribution_level_code', $reminder->company_filter))
                ->when($reminder->location_filter, fn($q) => $q->where('work_area_code', $reminder->location_filter))
                ->when($reminder->grade, fn($q) => $q->where('job_level', $reminder->grade))
                ->whereIn('employee_id', function ($q) {
                    $q->select('approver_id')
                    ->from('approval_layer_appraisals')
                    ->whereIn('layer_type', ['manager', 'calibrator']);
                })
                ->get();

            // dd($employees->toArray());
            foreach ($employees as $employee) {
                SendReminderEmailJob::dispatch($employee, $reminder);
            }
        }

        $this->info('Reminders processed successfully.');
    }
}
