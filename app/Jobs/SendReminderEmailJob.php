<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReminderMail;
use App\Models\User;
use App\Models\Employee;
use App\Models\PaReminder;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReminderExport;

class SendReminderEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $employee;
    protected $reminder;

    public function __construct(Employee $employee, PaReminder $reminder)
    {
        $this->employee = $employee;
        $this->reminder = $reminder;
    }

    public function handle()
    {
        // generate excel untuk lampiran
        $fileName = 'Reminder_' . $this->employee->id . '.xlsx';
        $filePath = storage_path("app/temp/{$fileName}");

        Excel::store(new ReminderExport($this->employee, $this->reminder), "temp/{$fileName}");

        $trialEmail = "eriton.dewa@kpn-corp.com"; 

        Mail::to($trialEmail)->send(
            new ReminderMail($this->employee, $this->reminder, $filePath)
        );

        // === Kalau sudah mau produksi, tinggal aktifkan ini ===
        // Mail::to($this->employee->email)->send(
        //     new ReminderMail($this->employee, $this->reminder, $filePath)
        // );

        // hapus file setelah dikirim
        unlink($filePath);
    }
}
